<?php

namespace App\Services;

use App\Models\MarketingEmail;
use App\Models\MarketingSetting;
use App\Models\MarketingSuppression;
use Aws\Ses\SesClient;
use Aws\Exception\AwsException;
use Illuminate\Support\Facades\Log;
use Exception;

class AwsSesService
{
    protected ?SesClient $sesClient = null;
    protected string $region;
    protected string $configurationSet;
    protected string $fromAddress;
    protected string $fromName;

    public function __construct()
    {
        $this->region = MarketingSetting::get('aws_region', config('services.ses.region', 'ap-south-1'));
        $this->configurationSet = (string) MarketingSetting::get('aws_ses_configuration_set', config('services.ses.configuration_set', ''));
        $this->fromAddress = MarketingSetting::get('aws_ses_from_address', config('services.ses.from_address', 'noreply@marketing.thenexteck.com'));
        $this->fromName = MarketingSetting::get('aws_ses_from_name', config('services.ses.from_name', 'TheNexteck Marketing'));

        $key = MarketingSetting::get('aws_access_key_id', config('services.ses.key'));
        $secret = MarketingSetting::get('aws_secret_access_key', config('services.ses.secret'));

        if (!empty($key) && !empty($secret)) {
            $this->sesClient = new SesClient([
                'version' => 'latest',
                'region' => $this->region,
                'credentials' => [
                    'key' => $key,
                    'secret' => $secret,
                ],
            ]);
        }
    }

    /**
     * Send marketing email via Amazon SES
     */
    public function sendMarketingEmail(MarketingEmail $email): array
    {
        $contact = $email->contact;

        // Central suppression verification before every single send
        if (MarketingSuppression::isSuppressed($email->recipient_email)) {
            $email->update([
                'status' => 'suppressed',
                'error_message' => 'Suppressed: Recipient email is present on central suppression list.',
            ]);
            return ['success' => false, 'status' => 'suppressed', 'reason' => 'Recipient suppressed'];
        }

        // Verify marketing consent
        if ($contact && $contact->consent_status !== 'granted') {
            $email->update([
                'status' => 'suppressed',
                'error_message' => 'Consent not granted: recipient does not have documented marketing permission.',
            ]);
            return ['success' => false, 'status' => 'suppressed', 'reason' => 'Consent missing'];
        }

        // Build unsubscribe and tracking markup
        $token = $contact ? $contact->unsubscribe_token : 'generic';
        $unsubUrl = route('marketing.unsubscribe', ['token' => $token]);
        $oneClickPostUrl = route('marketing.unsubscribe.post', ['token' => $token]);
        $trackingPixelUrl = $email->id ? route('marketing.track.open', ['id' => $email->id]) : '';

        // Wrap email body with standard high-deliverability marketing layout
        $fullHtml = $this->buildFullEmailHtml($email->body_html, $unsubUrl, $trackingPixelUrl);
        $fullText = ($email->body_text ?? strip_tags($email->body_html)) . "\n\nTo unsubscribe: " . $unsubUrl;

        // Check SES client availability
        if (!$this->sesClient) {
            // Simulated delivery when AWS credentials are not yet entered (staging/test mode)
            $mockMessageId = 'mock-ses-' . uniqid() . '@ap-south-1.amazonses.com';
            $email->update([
                'ses_message_id' => $mockMessageId,
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            return [
                'success' => true,
                'message_id' => $mockMessageId,
                'mode' => 'sandbox_simulated',
            ];
        }

        try {
            $params = [
                'Source' => "{$this->fromName} <{$this->fromAddress}>",
                'Destination' => [
                    'ToAddresses' => [$email->recipient_email],
                ],
                'Message' => [
                    'Subject' => [
                        'Data' => $email->subject,
                        'Charset' => 'UTF-8',
                    ],
                    'Body' => [
                        'Html' => [
                            'Data' => $fullHtml,
                            'Charset' => 'UTF-8',
                        ],
                        'Text' => [
                            'Data' => $fullText,
                            'Charset' => 'UTF-8',
                        ],
                    ],
                ],
                'Tags' => [
                    ['Name' => 'CampaignId', 'Value' => (string) $email->campaign_id],
                    ['Name' => 'EmailId', 'Value' => (string) $email->id],
                ],
            ];

            // Only attach ConfigurationSetName if configured and not empty
            if (!empty($this->configurationSet)) {
                $params['ConfigurationSetName'] = $this->configurationSet;
            }

            $result = $this->sesClient->sendEmail($params);
            $messageId = $result->get('MessageId');

            $email->update([
                'ses_message_id' => $messageId,
                'status' => 'sent',
                'sent_at' => now(),
                'error_message' => null,
            ]);

            if ($contact) {
                $contact->increment('emails_sent_count');
                $contact->update(['last_emailed_at' => now()]);
            }

            return [
                'success' => true,
                'message_id' => $messageId,
            ];
        } catch (AwsException $e) {
            $errorMessage = $e->getAwsErrorMessage() ?: $e->getMessage();
            Log::error("AWS SES Send Failure for {$email->recipient_email}: " . $errorMessage);

            $isConfigOrVerificationIssue = str_contains(strtolower($errorMessage), 'not verified')
                || str_contains(strtolower($errorMessage), 'does not exist')
                || str_contains(strtolower($errorMessage), 'sandbox');

            $isPermanent = !$isConfigOrVerificationIssue && ($e->getAwsErrorCode() === 'MessageRejected' || str_contains($errorMessage, 'suppression list'));

            if ($isPermanent) {
                MarketingSuppression::suppress($email->recipient_email, 'hard_bounce', $errorMessage, 'ses_send_failure');
                $email->update([
                    'status' => 'bounced',
                    'error_message' => $errorMessage,
                    'bounced_at' => now(),
                ]);
            } else {
                $email->increment('retry_count');
                $email->update([
                    'status' => 'failed',
                    'error_message' => $errorMessage,
                ]);
            }

            return [
                'success' => false,
                'error' => $errorMessage,
                'is_permanent' => $isPermanent,
            ];
        } catch (Exception $e) {
            Log::error("SES Send Exception: " . $e->getMessage());
            $email->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'is_permanent' => false,
            ];
        }
    }

    /**
     * Get SES sending quota and statistics
     */
    public function getSendStatistics(): array
    {
        if (!$this->sesClient) {
            return [
                'max_24_hour_send' => (int) MarketingSetting::get('aws_ses_daily_quota', 50000),
                'max_send_rate' => (float) MarketingSetting::get('aws_ses_sending_rate', 14.0),
                'sent_last_24_hours' => MarketingEmail::where('sent_at', '>=', now()->subDay())->count(),
                'connected' => false,
                'status_message' => 'AWS credentials not configured yet. Running in staging sandbox mode.',
            ];
        }

        try {
            $quota = $this->sesClient->getSendQuota();
            return [
                'max_24_hour_send' => $quota['Max24HourSend'] ?? 50000,
                'max_send_rate' => $quota['MaxSendRate'] ?? 14.0,
                'sent_last_24_hours' => $quota['SentLast24Hours'] ?? 0,
                'connected' => true,
                'status_message' => 'Connected to AWS SES (' . $this->region . ')',
            ];
        } catch (Exception $e) {
            return [
                'max_24_hour_send' => 50000,
                'max_send_rate' => 14.0,
                'sent_last_24_hours' => 0,
                'connected' => false,
                'status_message' => 'SES Error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Standard responsive email wrapper with RFC-compliant footer and tracking pixel
     */
    protected function buildFullEmailHtml(string $bodyHtml, string $unsubUrl, string $trackingPixelUrl): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #1e293b; line-height: 1.6; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #f8fafc; padding: 30px 10px; }
        .main-card { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0; overflow: hidden; }
        .content { padding: 36px 32px; font-size: 15px; }
        .footer { background: #f1f5f9; padding: 20px 32px; font-size: 12px; color: #64748b; text-align: center; border-top: 1px solid #e2e8f0; }
        .footer a { color: #0284c7; text-decoration: underline; }
        p { margin: 0 0 16px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="main-card">
            <div class="content">
                {$bodyHtml}
            </div>
            <div class="footer">
                <p>You received this message because you opted in to marketing updates from {$this->fromName}.</p>
                <p>If you no longer wish to receive these emails, you can <a href="{$unsubUrl}">unsubscribe immediately</a>.</p>
                <p>&copy; 2026 {$this->fromName}. All rights reserved.</p>
            </div>
        </div>
    </div>
    <img src="{$trackingPixelUrl}" width="1" height="1" alt="" style="display:none;width:1px;height:1px;border:0;">
</body>
</html>
HTML;
    }
}
