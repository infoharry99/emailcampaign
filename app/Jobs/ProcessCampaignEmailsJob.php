<?php

namespace App\Jobs;

use App\Models\MarketingCampaign;
use App\Models\MarketingContact;
use App\Models\MarketingEmail;
use App\Models\MarketingSetting;
use App\Models\MarketingSuppression;
use App\Services\OpenAIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessCampaignEmailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 600;
    public bool $syncMode = false;

    protected MarketingCampaign $campaign;

    public function __construct(MarketingCampaign $campaign, bool $syncMode = false)
    {
        $this->campaign = $campaign;
        $this->syncMode = $syncMode;
    }

    public function handle(OpenAIService $aiService): void
    {
        // Check if campaign is paused or cancelled
        $fresh = $this->campaign->fresh();
        if (!$fresh || in_array($fresh->status, ['paused', 'cancelled', 'completed'])) {
            Log::info("ProcessCampaignEmailsJob: Campaign {$this->campaign->id} is {$fresh->status}. Aborting execution.");
            return;
        }

        $fresh->update([
            'status' => 'running',
            'started_at' => $fresh->started_at ?? now(),
        ]);

        // Target contacts: strictly verified, subscribed, consented, non-suppressed
        $contactsQuery = $fresh->targetContactsQuery();

        $contacts = $contactsQuery->get();

        // Calculate rate throttling per minute
        $ratePerMinute = (float) MarketingSetting::get('aws_ses_sending_rate', 60);
        if ($ratePerMinute <= 0) {
            $ratePerMinute = 60;
        }
        $secondsPerEmail = 60.0 / $ratePerMinute;

        $index = 0;
        foreach ($contacts as $contact) {
            // Check campaign status during batch to support immediate pause
            if ($this->campaign->fresh()->status === 'paused') {
                Log::info("Campaign {$this->campaign->id} was paused mid-generation.");
                break;
            }

            // Central suppression check
            if (MarketingSuppression::isSuppressed($contact->email)) {
                continue;
            }

            // Atomically create or fetch the MarketingEmail record
            $emailRecord = MarketingEmail::firstOrCreate(
                [
                    'campaign_id' => $fresh->id,
                    'contact_id' => $contact->id,
                ],
                [
                    'recipient_email' => $contact->email,
                    'recipient_name' => $contact->name,
                    'subject' => $fresh->subject ?: 'Opportunity',
                    'body_html' => '',
                    'status' => 'pending',
                    'generation_mode' => $fresh->sending_mode,
                ]
            );

            // If the record exists from a previous test send, reset it to pending for the real campaign send
            if (str_starts_with($emailRecord->subject, '[TEST]')) {
                $emailRecord->update([
                    'status' => 'pending',
                    'body_html' => '',
                    'ses_message_id' => null,
                ]);
            } elseif (in_array($emailRecord->status, ['sent', 'delivered', 'queued', 'bounced'])) {
                continue;
            }

            // Generate Content based on mode
            if (empty($emailRecord->body_html) || $emailRecord->status === 'pending') {
                if ($fresh->campaign_type === 'ai_personalized') {
                    $aiDraft = $aiService->generatePersonalizedContactEmail($contact, $fresh);
                    $emailRecord->update([
                        'subject' => $aiDraft['subject'],
                        'body_html' => $aiDraft['body_html'],
                        'body_text' => $aiDraft['body_text'],
                        'ai_rationale' => $aiDraft['ai_rationale'] ?? null,
                        'is_ai_generated' => true,
                        'ai_metadata' => $aiDraft['metadata'] ?? null,
                        'status' => 'generated',
                        'generated_at' => now(),
                    ]);
                } elseif ($fresh->sending_mode === 'template' && !empty($fresh->content)) {
                    // Template mode
                    $rendered = $this->renderTemplate($fresh->content, $fresh->subject, $contact);
                    $emailRecord->update([
                        'subject' => $rendered['subject'],
                        'body_html' => $rendered['content'],
                        'body_text' => strip_tags($rendered['content']),
                        'is_ai_generated' => false,
                        'status' => 'generated',
                        'generated_at' => now(),
                    ]);
                } else {
                    // AI mode or Both mode
                    $aiDraft = $aiService->generateMarketingEmail($contact, $fresh);
                    $emailRecord->update([
                        'subject' => $aiDraft['subject'],
                        'body_html' => $aiDraft['body_html'],
                        'body_text' => $aiDraft['body_text'],
                        'is_ai_generated' => true,
                        'ai_metadata' => $aiDraft['metadata'],
                        'status' => 'generated',
                        'generated_at' => now(),
                    ]);
                }
            }

            // Deliver via SendMarketingEmailJob (sync with micro-throttle or queued with staggered delay)
            $emailRecord->update([
                'status' => 'queued',
                'queued_at' => now(),
            ]);

            if ($this->syncMode || config('queue.default') === 'sync') {
                SendMarketingEmailJob::dispatchSync($emailRecord);

                if ($secondsPerEmail > 0 && $index < count($contacts) - 1) {
                    // Micro-throttle in sync mode (capped at 1s to prevent HTTP timeouts)
                    usleep((int) min(1_000_000, $secondsPerEmail * 1_000_000));
                }
            } else {
                $delaySeconds = (int) round($index * $secondsPerEmail);
                SendMarketingEmailJob::dispatch($emailRecord)->delay(now()->addSeconds($delaySeconds));
            }

            $index++;
        }

        $fresh->recalculateStats();

        $remaining = $fresh->emails()->whereIn('status', ['pending', 'queued'])->count();
        if ($remaining === 0 && $fresh->emails()->count() > 0) {
            $fresh->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        }
    }

    protected function renderTemplate(string $content, ?string $subject, MarketingContact $contact): array
    {
        $vars = [
            'name' => $contact->name ?: 'Customer',
            'company' => $contact->company ?: 'Your Firm',
            'email' => $contact->email,
            'website' => $contact->website ?: '',
            'unsubscribe_url' => route('marketing.unsubscribe', ['token' => $contact->unsubscribe_token]),
        ];

        foreach ($vars as $k => $v) {
            $content = str_replace('{{' . $k . '}}', $v, $content);
            $subject = str_replace('{{' . $k . '}}', $v, (string) $subject);
        }

        return [
            'subject' => $subject,
            'content' => $content,
        ];
    }
}
