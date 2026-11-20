<?php

namespace App\Services;

use App\Models\MarketingContact;
use App\Models\MarketingCampaign;
use App\Models\MarketingSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class OpenAIService
{
    protected ?string $apiKey;
    protected string $model;
    protected int $maxTokens;
    protected float $temperature;

    public function __construct()
    {
        $this->apiKey = MarketingSetting::get('openai_api_key', config('services.openai.api_key'));
        $this->model = MarketingSetting::get('openai_model', config('services.openai.model', 'gpt-4o-mini'));
        $this->maxTokens = (int) MarketingSetting::get('openai_max_tokens', config('services.openai.max_tokens', 450));
        $this->temperature = (float) MarketingSetting::get('openai_temperature', config('services.openai.temperature', 0.7));
    }

    /**
     * Generate personalized email content for a contact and campaign.
     * Guaranteed strictly around 100 words, truthful, business professional.
     */
    public function generateMarketingEmail(MarketingContact $contact, MarketingCampaign $campaign): array
    {
        if (empty($this->apiKey)) {
            // Provide a graceful fallback if API key is not configured yet
            return $this->generateFallbackDraft($contact, $campaign);
        }

        $systemPrompt = <<<EOT
You are an elite, highly effective B2B Marketing Copywriter for verified business prospects.
Generate a concise, compelling marketing email tailored directly to the recipient.

RULES:
1. Length: Approximately 100 words in the body (strictly 80-130 words).
2. Language: Professional, conversational, respectful business tone.
3. Personalization: Use the contact's name, company, business niche, or website naturally.
4. Truthfulness: Strictly NO fake claims, NO invented metrics, NO deceptive urgency.
5. Structure:
   - Compelling Subject Line (no emojis, professional).
   - Warm, personalized opening.
   - Clear value proposition addressing their business needs.
   - Distinct, low-friction Call To Action (CTA).
6. Format your response strictly as valid JSON with keys: "subject", "body_html", and "body_text".
   - "body_html" must contain clean paragraph tags <p> and a stylish CTA button or link. Do not include full <html><body> wrappers, just the inner content.
   - "body_text" must contain the plain text equivalent.
EOT;

        $userPrompt = "PROSPECT DETAILS:\n"
            . "- Name: " . ($contact->name ?: 'Business Leader') . "\n"
            . "- Company: " . ($contact->company ?: 'Your Firm') . "\n"
            . "- Website: " . ($contact->website ?: 'N/A') . "\n"
            . "- Business Type: " . ($contact->business_type ?: 'Business') . "\n\n"
            . "CAMPAIGN OBJECTIVE & PURPOSE:\n"
            . ($campaign->purpose ?: 'Introduce our high-value services and explore synergy.') . "\n\n"
            . "PRODUCT / SERVICE DETAILS:\n"
            . ($campaign->product_service_details ?: 'Tailored enterprise solutions designed for scalability.') . "\n\n"
            . "Produce the JSON output with subject, body_html, and body_text now.";

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(35)->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'response_format' => ['type' => 'json_object'],
                'max_tokens' => $this->maxTokens,
                'temperature' => $this->temperature,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $contentJson = json_decode($data['choices'][0]['message']['content'], true);
                $usage = $data['usage'] ?? [];

                // Record usage for dashboard reporting
                $this->recordUsage($usage);

                return [
                    'subject' => $contentJson['subject'] ?? ($campaign->subject ?: 'Opportunity for ' . ($contact->company ?: $contact->name)),
                    'body_html' => $contentJson['body_html'] ?? '<p>' . nl2br(e($contentJson['body_text'] ?? '')) . '</p>',
                    'body_text' => $contentJson['body_text'] ?? '',
                    'metadata' => [
                        'model' => $this->model,
                        'prompt_tokens' => $usage['prompt_tokens'] ?? 0,
                        'completion_tokens' => $usage['completion_tokens'] ?? 0,
                        'total_tokens' => $usage['total_tokens'] ?? 0,
                        'estimated_cost_usd' => $this->calculateCost($usage),
                    ],
                ];
            }

            Log::error('OpenAI API Request Failed: ' . $response->body());
            return $this->generateFallbackDraft($contact, $campaign);
        } catch (Exception $e) {
            Log::error('OpenAI Exception: ' . $e->getMessage());
            return $this->generateFallbackDraft($contact, $campaign);
        }
    }

    /**
     * Dedicated AI generator for AI Personalized Email Campaigns.
     * Thoroughly analyzes the individual company, industry, description, and website
     * to produce a uniquely tailored, highly non-generic B2B pitch for each prospect.
     */
    public function generatePersonalizedContactEmail(MarketingContact $contact, MarketingCampaign $campaign): array
    {
        if (empty($this->apiKey)) {
            return $this->generatePersonalizedFallbackDraft($contact, $campaign);
        }

        $campaignTopic = $campaign->subject ?: $campaign->name;
        $campaignDesc = $campaign->description ?: ($campaign->purpose ?: 'Introduce our enterprise services and discuss how we can assist your growth.');

        $systemPrompt = <<<EOT
You are an expert, elite B2B enterprise copywriter and strategic business consultant.
Your objective is to craft an exceptionally personalized, 1-to-1 outreach email addressed to a specific business executive, presenting our offer in the exact context of THEIR unique industry and business.

CRITICAL DIRECTIVES:
1. DEEP INDUSTRY PERSONALIZATION:
   - Carefully examine the prospect's company name, website, industry, and business description.
   - Tailor the messaging specifically to their domain. For instance:
     * If maritime transport or shipping: focus on fleet optimization, port compliance, route safety, or fuel/freight efficiency.
     * If mining or natural resources: focus on resource operations, supply chain resilience, safety standards, or commodity market scaling.
     * If IT, software, or SaaS: focus on cloud performance, engineering velocity, and cyber resilience.
     * If retail, health, or manufacturing: tailor the value proposition directly to their operational realities.
   - DO NOT write a boilerplate or generic pitch. Every recipient must receive a distinct, bespoke email that demonstrates familiarity with their specific sector.

2. CLEAR CAMPAIGN TOPIC & VALUE ALIGNMENT:
   - Campaign Topic: {$campaignTopic}
   - Campaign Details / Offer: {$campaignDesc}
   - Connect what we offer directly to the specific challenges or expansion goals of a company in their sector.

3. STRUCTURE & WRITING STYLE:
   - Tone: Executive, respectful, conversational, consultative, and zero fluff.
   - Word count: 90 - 140 words in the body.
   - Subject line: Highly engaging, tailored directly to their company/niche, clean (no emojis, no spam words, no ALL CAPS).
   - Opening: Greet the contact warmly using their name. Mention their organization.
   - Value Proposition: Crisp, factual explanation of how our offer translates to tangible value in their niche.
   - Call To Action: Low-friction, courteous closing (e.g. asking for a 10-minute discovery exchange next week).

4. STRICT JSON FORMAT:
   Return valid JSON with exactly four keys:
   - "subject": Tailored email subject line string.
   - "body_html": The formatted email HTML body using standard <p> tags, clean paragraph spacing, and an attractive closing CTA button or link. (No full <html><body> wrappers, just the message content).
   - "body_text": Clean plain text equivalent.
   - "ai_rationale": 1 concise sentence explaining the specific business angle used for this prospect.
EOT;

        $userPrompt = "PROSPECT DOSSIER:\n"
            . "- Full Name: " . ($contact->name ?: 'Business Leader') . "\n"
            . "- Email Address: " . $contact->email . "\n"
            . "- Company Name: " . ($contact->company ?: 'Your Organization') . "\n"
            . "- Website URL: " . ($contact->website ?: 'N/A') . "\n"
            . "- Industry / Business Type: " . ($contact->business_type ?: 'Commercial Enterprise') . "\n"
            . "- Business Description / Notes: " . ($contact->business_description ?: ($contact->company . ' operating within the ' . ($contact->business_type ?: 'commercial') . ' market.')) . "\n\n"
            . "CAMPAIGN INSTRUCTIONS:\n"
            . "- Topic / Subject: " . $campaignTopic . "\n"
            . "- Communication Goal & Offer Details: " . $campaignDesc . "\n\n"
            . "Generate the custom, non-generic JSON object for this prospect now.";

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(40)->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'response_format' => ['type' => 'json_object'],
                'max_tokens' => $this->maxTokens,
                'temperature' => $this->temperature,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $contentJson = json_decode($data['choices'][0]['message']['content'], true);
                $usage = $data['usage'] ?? [];

                $this->recordUsage($usage);

                $subject = $contentJson['subject'] ?? ($campaignTopic . ' for ' . ($contact->company ?: $contact->name));
                $bodyHtml = $contentJson['body_html'] ?? '<p>' . nl2br(e($contentJson['body_text'] ?? '')) . '</p>';
                $bodyText = $contentJson['body_text'] ?? strip_tags($bodyHtml);
                $rationale = $contentJson['ai_rationale'] ?? ("Tailored for " . ($contact->company ?: 'enterprise') . " within " . ($contact->business_type ?: 'their industry'));

                return [
                    'subject' => $subject,
                    'body_html' => $bodyHtml,
                    'body_text' => $bodyText,
                    'ai_rationale' => $rationale,
                    'metadata' => [
                        'model' => $this->model,
                        'prompt_tokens' => $usage['prompt_tokens'] ?? 0,
                        'completion_tokens' => $usage['completion_tokens'] ?? 0,
                        'total_tokens' => $usage['total_tokens'] ?? 0,
                        'estimated_cost_usd' => $this->calculateCost($usage),
                    ],
                ];
            }

            Log::error('OpenAI Personalized Campaign Request Failed: ' . $response->body());
            return $this->generatePersonalizedFallbackDraft($contact, $campaign);
        } catch (Exception $e) {
            Log::error('OpenAI Personalized Campaign Exception: ' . $e->getMessage());
            return $this->generatePersonalizedFallbackDraft($contact, $campaign);
        }
    }

    /**
     * Context-aware fallback generator for personalized campaigns when offline or in test environments.
     */
    protected function generatePersonalizedFallbackDraft(MarketingContact $contact, MarketingCampaign $campaign): array
    {
        $name = $contact->name ?: 'there';
        $company = $contact->company ?: 'your team';
        $industry = $contact->business_type ?: 'your sector';
        $topic = $campaign->subject ?: ($campaign->purpose ?: 'Business Introduction');
        $desc = $campaign->description ?: ($campaign->product_service_details ?: 'explore high-impact collaboration');

        $industrySpecificFocus = match(strtolower($industry)) {
            'technology', 'saas', 'software' => 'modernizing cloud software, optimizing engineering throughput, and tightening infrastructure security',
            'mining', 'resources' => 'strengthening resource logistics, operational safety protocols, and cross-border distribution efficiency',
            'maritime', 'transportation', 'logistics' => 'optimizing supply chain visibility, maritime regulatory reporting, and freight dispatch timelines',
            'retail', 'ecommerce' => 'scaling customer lifetime value, reducing checkout drop-offs, and optimizing real-time order processing',
            default => 'driving operational efficiency, reducing systemic overhead, and unlocking sustainable commercial growth'
        };

        $subject = "Collaborating with {$company} on {$industry} efficiency";

        $bodyHtml = "<p>Hi {$name},</p>"
            . "<p>I've been closely following {$company}'s presence in the {$industry} market and wanted to connect specifically regarding {$industrySpecificFocus}.</p>"
            . "<p>At TheNexteck, we help forward-thinking organizations address this exact challenge through specialized services designed to {$desc}.</p>"
            . "<p>Given your role at {$company}, would you be open to a brief 10-minute introductory conversation next Tuesday to explore whether this could create meaningful value for your team?</p>"
            . "<p>Best regards,<br><strong>Strategic Growth Team</strong><br>TheNexteck</p>";

        $bodyText = strip_tags($bodyHtml);

        return [
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'body_text' => $bodyText,
            'ai_rationale' => "Locally tailored around {$company}'s {$industry} operations with focus on {$industrySpecificFocus}.",
            'metadata' => [
                'model' => 'fallback-personalized-local',
                'prompt_tokens' => 0,
                'completion_tokens' => 0,
                'total_tokens' => 0,
                'estimated_cost_usd' => 0.00,
            ],
        ];
    }

    /**
     * Fallback template generator when OpenAI API is not yet populated or offline
     */
    protected function generateFallbackDraft(MarketingContact $contact, MarketingCampaign $campaign): array
    {
        $name = $contact->name ?: 'there';
        $company = $contact->company ?: 'your company';
        $subject = $campaign->subject ?: ("Strategic Partnership with " . $company);
        
        $bodyHtml = "<p>Hi {$name},</p>"
            . "<p>I've been following {$company}'s growth and wanted to reach out regarding our innovative solutions designed to streamline operations and drive predictable growth.</p>"
            . "<p>" . e($campaign->purpose ?: 'We specialize in scalable technology and modern business infrastructure.') . "</p>"
            . "<p>Would you be open to a brief 10-minute introductory conversation next Tuesday to see if there is a mutual fit?</p>"
            . "<p>Best regards,<br>The TheNexteck Team</p>";

        $bodyText = strip_tags($bodyHtml);

        return [
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'body_text' => $bodyText,
            'metadata' => [
                'model' => 'fallback-local',
                'prompt_tokens' => 0,
                'completion_tokens' => 0,
                'total_tokens' => 0,
                'estimated_cost_usd' => 0.00,
            ],
        ];
    }

    protected function calculateCost(array $usage): float
    {
        // Pricing estimates (e.g. gpt-4o-mini: ~$0.15/1M input, $0.60/1M output)
        $prompt = $usage['prompt_tokens'] ?? 0;
        $completion = $usage['completion_tokens'] ?? 0;
        
        $cost = ($prompt * 0.00000015) + ($completion * 0.00000060);
        return round($cost, 6);
    }

    protected function recordUsage(array $usage): void
    {
        $totalTokens = (int) MarketingSetting::get('openai_total_tokens_used', 0) + ($usage['total_tokens'] ?? 0);
        MarketingSetting::set('openai_total_tokens_used', (string) $totalTokens);

        $totalCost = (float) MarketingSetting::get('openai_total_cost_usd', 0) + $this->calculateCost($usage);
        MarketingSetting::set('openai_total_cost_usd', (string) round($totalCost, 4));
    }
}
