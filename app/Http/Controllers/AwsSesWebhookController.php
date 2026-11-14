<?php

namespace App\Http\Controllers;

use App\Models\MarketingEmail;
use App\Models\MarketingEmailEvent;
use App\Models\MarketingSuppression;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AwsSesWebhookController extends Controller
{
    /**
     * Handle incoming Amazon SNS / SES Event Webhook
     */
    public function handle(Request $request)
    {
        $payload = json_decode($request->getContent(), true);

        if (!$payload) {
            return response()->json(['error' => 'Invalid JSON'], 400);
        }

        // Handle AWS SNS Subscription Confirmation
        if (isset($payload['Type']) && $payload['Type'] === 'SubscriptionConfirmation') {
            if (!empty($payload['SubscribeURL'])) {
                Http::get($payload['SubscribeURL']);
                Log::info('AWS SNS Subscription auto-confirmed: ' . $payload['TopicArn']);
                return response('Subscription confirmed', 200);
            }
        }

        // Handle SES Notification via SNS Message
        $sesData = [];
        if (isset($payload['Message'])) {
            $sesData = json_decode($payload['Message'], true) ?: [];
        } elseif (isset($payload['eventType']) || isset($payload['notificationType'])) {
            $sesData = $payload;
        }

        $eventType = $sesData['eventType'] ?? $sesData['notificationType'] ?? null;
        $mail = $sesData['mail'] ?? [];
        $messageId = $mail['messageId'] ?? null;
        $destination = $mail['destination'][0] ?? ($sesData['bounce']['bouncedRecipients'][0]['emailAddress'] ?? null);

        if (!$eventType) {
            return response()->json(['message' => 'No eventType recognized'], 200);
        }

        // Find email record by SES Message ID
        $emailRecord = null;
        if ($messageId) {
            $emailRecord = MarketingEmail::where('ses_message_id', $messageId)->first();
        }
        if (!$emailRecord && $destination) {
            $emailRecord = MarketingEmail::where('recipient_email', $destination)->latest()->first();
        }

        // Process Event idempotently
        switch (strtolower($eventType)) {
            case 'delivery':
                if ($emailRecord && !in_array($emailRecord->status, ['opened', 'clicked', 'bounced'])) {
                    $emailRecord->update([
                        'status' => 'delivered',
                        'delivered_at' => now(),
                    ]);
                }
                break;

            case 'bounce':
                $bounceType = $sesData['bounce']['bounceType'] ?? 'Permanent';
                $bounceSubType = $sesData['bounce']['bounceSubType'] ?? 'General';
                $recipient = $destination ?? ($sesData['bounce']['bouncedRecipients'][0]['emailAddress'] ?? null);

                if ($recipient && ($bounceType === 'Permanent' || str_contains(strtolower($bounceSubType), 'suppression'))) {
                    // Suppress permanently on hard bounces
                    MarketingSuppression::suppress($recipient, 'hard_bounce', "{$bounceType} - {$bounceSubType}", 'ses_webhook');
                }

                if ($emailRecord) {
                    $emailRecord->update([
                        'status' => 'bounced',
                        'bounced_at' => now(),
                        'error_message' => "Bounce: {$bounceType} - {$bounceSubType}",
                    ]);
                }
                break;

            case 'complaint':
                $recipient = $destination ?? ($sesData['complaint']['complainedRecipients'][0]['emailAddress'] ?? null);
                $feedbackType = $sesData['complaint']['complaintFeedbackType'] ?? 'abuse';

                if ($recipient) {
                    MarketingSuppression::suppress($recipient, 'complaint', "Spam Complaint: {$feedbackType}", 'ses_webhook');
                }

                if ($emailRecord) {
                    $emailRecord->update([
                        'status' => 'complained',
                        'complained_at' => now(),
                        'error_message' => "Spam Complaint registered by recipient mailbox",
                    ]);
                }
                break;

            case 'open':
                if ($emailRecord) {
                    $emailRecord->update([
                        'status' => 'opened',
                        'opened_at' => $emailRecord->opened_at ?? now(),
                    ]);
                }
                break;

            case 'click':
                if ($emailRecord) {
                    $emailRecord->update([
                        'status' => 'clicked',
                        'clicked_at' => now(),
                    ]);
                }
                break;
        }

        // Log granular event
        MarketingEmailEvent::create([
            'campaign_id' => $emailRecord?->campaign_id,
            'contact_id' => $emailRecord?->contact_id,
            'marketing_email_id' => $emailRecord?->id,
            'ses_message_id' => $messageId,
            'recipient_email' => $destination ?: ($emailRecord?->recipient_email ?? 'unknown'),
            'event_type' => ucfirst($eventType),
            'event_timestamp' => now(),
            'payload' => $sesData,
        ]);

        $emailRecord?->campaign?->recalculateStats();

        return response()->json(['status' => 'success', 'event' => $eventType]);
    }
}
