<?php

namespace Database\Seeders;

use App\Models\MarketingCampaign;
use App\Models\MarketingContact;
use App\Models\MarketingSetting;
use App\Models\MarketingTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Default Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@thenexteck.com'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // 2. Default Configuration Settings
        MarketingSetting::set('aws_region', 'eu-north-1');
        MarketingSetting::set('aws_ses_configuration_set', '');
        MarketingSetting::set('aws_ses_from_address', 'nasar@thenexteck.com');
        MarketingSetting::set('aws_ses_from_name', 'TheNexteck Marketing');
        MarketingSetting::set('aws_ses_sending_rate', '14');
        MarketingSetting::set('aws_ses_daily_quota', '50000');
        MarketingSetting::set('openai_model', 'gpt-4o-mini');
        MarketingSetting::set('openai_max_tokens', '450');
        MarketingSetting::set('openai_temperature', '0.7');

        // 3. Sample Reusable Template
        MarketingTemplate::firstOrCreate(
            ['name' => 'B2B Strategic Partnership Introduction'],
            [
                'subject' => 'Exploring synergy between {{company}} and TheNexteck',
                'content' => "<p>Hi {{name}},</p>\n<p>I hope this email finds you well. I've been following {{company}}'s recent developments and noticed your focus on scalable infrastructure.</p>\n<p>At TheNexteck, we specialize in high-deliverability marketing automation and cloud optimization powered by Amazon SES and AI. We typically help organizations reduce deliverability friction and drive predictable pipeline.</p>\n<p>Would you be open to a brief 10-minute discovery call next Tuesday?</p>\n<p>Best regards,<br>The TheNexteck Team</p>",
                'description' => 'Default cold reach template for B2B executives with merge tags.',
                'is_active' => true,
            ]
        );

        // 4. Sample Opt-in Permitted Contacts
        $contacts = [
            [
                'name' => 'Vikram Sharma',
                'email' => 'vikram@mumbaitech.in',
                'company' => 'Mumbai Tech Solutions',
                'website' => 'https://mumbaitech.in',
                'phone' => '+91 98200 12345',
                'business_type' => 'Technology',
                'consent_status' => 'granted',
                'consent_date' => now()->subDays(5),
                'consent_source' => 'Website Inbound Contact Form',
                'consent_proof' => 'Opted in via demo form, IP 103.21.12.44',
                'subscription_status' => 'subscribed',
                'verification_status' => 'valid',
            ],
            [
                'name' => 'Priya Patel',
                'email' => 'priya@apexfin.com',
                'company' => 'Apex Financial Services',
                'website' => 'https://apexfin.com',
                'phone' => '+91 98111 67890',
                'business_type' => 'Enterprise',
                'consent_status' => 'granted',
                'consent_date' => now()->subDays(10),
                'consent_source' => 'Webinar Opt-in Form',
                'consent_proof' => 'Attended Scaling Cloud Webinar 2026',
                'subscription_status' => 'subscribed',
                'verification_status' => 'valid',
            ],
            [
                'name' => 'Rahul Verma',
                'email' => 'rahul@delhilogistics.in',
                'company' => 'Delhi Express Logistics',
                'website' => 'https://delhilogistics.in',
                'phone' => '+91 99555 44321',
                'business_type' => 'Logistics',
                'consent_status' => 'granted',
                'consent_date' => now()->subDays(2),
                'consent_source' => 'Conference Lead Scan',
                'consent_proof' => 'TechSummit 2026 booth permission badge',
                'subscription_status' => 'subscribed',
                'verification_status' => 'valid',
            ],
        ];

        foreach ($contacts as $c) {
            MarketingContact::firstOrCreate(['email' => $c['email']], $c);
        }

        // 5. Initial Campaign
        MarketingCampaign::firstOrCreate(
            ['name' => 'Q4 Enterprise Cloud Infrastructure Intro'],
            [
                'description' => 'Targeting B2B technology and logistics companies across India.',
                'purpose' => 'Introduce high-throughput Amazon SES marketing automation and AI copy generation to boost deliverability and lead generation.',
                'product_service_details' => 'Full-stack Laravel + SES setup, sub-second queues, 100k email capacity, dedicated IP warming.',
                'target_criteria' => 'all',
                'sending_mode' => 'ai',
                'status' => 'draft',
                'subject' => 'Scalable Cloud Marketing Infrastructure for {{company}}',
                'total_recipients' => 3,
            ]
        );
    }
}
