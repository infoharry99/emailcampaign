<?php

namespace App\Jobs;

use App\Models\MarketingEmail;
use App\Models\MarketingSuppression;
use App\Services\AwsSesService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendMarketingEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;
    public array $backoff = [30, 120, 600]; // Exponential backoff for temporary failures
    public int $timeout = 60;

    protected MarketingEmail $marketingEmail;

    public function __construct(MarketingEmail $marketingEmail)
    {
        $this->marketingEmail = $marketingEmail;
    }

    public function handle(AwsSesService $sesService): void
    {
        $email = $this->marketingEmail->fresh();

        if (!$email) {
            return;
        }

        // Check if campaign is paused or cancelled
        $campaign = $email->campaign;
        if ($campaign && in_array($campaign->status, ['paused', 'cancelled'])) {
            Log::info("SendMarketingEmailJob: Paused or cancelled for email {$email->id}. Re-queuing with delay.");
            $this->release(60);
            return;
        }

        // Avoid re-sending if already sent or delivered
        if (in_array($email->status, ['sent', 'delivered', 'opened', 'clicked', 'bounced', 'complained', 'suppressed'])) {
            return;
        }

        // Check suppression list centrally
        if (MarketingSuppression::isSuppressed($email->recipient_email)) {
            $email->update([
                'status' => 'suppressed',
                'error_message' => 'Suppressed before sending: Found on active suppression list.',
            ]);
            $campaign?->recalculateStats();
            return;
        }

        // Send via AWS SES
        $result = $sesService->sendMarketingEmail($email);

        if (!$result['success']) {
            if (!empty($result['is_permanent'])) {
                // Permanent failure: Do not retry
                $email->update([
                    'status' => 'bounced',
                    'error_message' => $result['error'] ?? 'Permanent bounce',
                    'bounced_at' => now(),
                ]);
            } else {
                // Temporary failure: release back to queue if attempts remaining
                $email->update([
                    'status' => 'failed',
                    'error_message' => $result['error'] ?? 'Temporary sending failure',
                ]);

                if ($this->attempts() < $this->tries) {
                    $this->release($this->backoff[$this->attempts() - 1] ?? 60);
                }
            }
        }

        $campaign?->recalculateStats();
    }
}
