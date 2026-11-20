<?php

namespace App\Jobs;

use App\Models\MarketingCampaign;
use App\Models\MarketingContact;
use App\Models\MarketingEmail;
use App\Models\MarketingSuppression;
use App\Services\OpenAIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateAiCampaignEmailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 600; // 10 minutes for multi-contact AI batch

    protected MarketingCampaign $campaign;
    protected ?array $contactIds;

    public function __construct(MarketingCampaign $campaign, ?array $contactIds = null)
    {
        $this->campaign = $campaign;
        $this->contactIds = $contactIds;
    }

    public function handle(OpenAIService $aiService): void
    {
        $freshCampaign = $this->campaign->fresh();
        if (!$freshCampaign) {
            return;
        }

        $query = $freshCampaign->targetContactsQuery();
        if (!empty($this->contactIds)) {
            $query->whereIn('id', $this->contactIds);
        }

        $contacts = $query->get();

        foreach ($contacts as $contact) {
            // Check campaign state
            if ($freshCampaign->fresh()->status === 'paused' || $freshCampaign->fresh()->status === 'cancelled') {
                Log::info("GenerateAiCampaignEmailsJob: Paused or cancelled for campaign {$freshCampaign->id}.");
                break;
            }

            // Central suppression & unsubscribe check
            if (MarketingSuppression::isSuppressed($contact->email) || $contact->subscription_status !== 'subscribed') {
                continue;
            }

            // Find or initialize email record
            $email = MarketingEmail::firstOrCreate(
                [
                    'campaign_id' => $freshCampaign->id,
                    'contact_id' => $contact->id,
                ],
                [
                    'recipient_email' => $contact->email,
                    'recipient_name' => $contact->name,
                    'subject' => $freshCampaign->subject ?: 'Personalized Outreach',
                    'body_html' => '',
                    'status' => 'pending',
                    'generation_mode' => 'ai',
                ]
            );

            // If already sent or queued, don't overwrite
            if (in_array($email->status, ['sent', 'delivered', 'queued', 'bounced'])) {
                continue;
            }

            // Generate personalized email content via OpenAI
            $draft = $aiService->generatePersonalizedContactEmail($contact, $freshCampaign);

            $email->update([
                'recipient_email' => $contact->email,
                'recipient_name' => $contact->name,
                'subject' => $draft['subject'],
                'body_html' => $draft['body_html'],
                'body_text' => $draft['body_text'],
                'ai_rationale' => $draft['ai_rationale'] ?? null,
                'generation_mode' => 'ai',
                'is_ai_generated' => true,
                'status' => 'generated',
                'generated_at' => now(),
                'ai_metadata' => $draft['metadata'] ?? null,
            ]);
        }

        $freshCampaign->recalculateStats();
    }
}
