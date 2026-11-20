<?php

namespace Tests\Feature;

use App\Models\MarketingCampaign;
use App\Models\MarketingContact;
use App\Models\MarketingContactList;
use App\Models\MarketingEmail;
use App\Models\MarketingSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiPersonalizedCampaignTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'email' => 'admin@thenexteck.com',
        ]);
    }

    public function test_can_create_ai_personalized_campaign_with_4_required_fields()
    {
        $list = MarketingContactList::create(['name' => 'Transport Partners']);

        $response = $this->actingAs($this->user)->post(route('admin.marketing.ai-campaigns.store'), [
            'name' => 'Maritime Tech Outreach',
            'contact_list_ids' => [$list->id],
            'subject' => 'Optimizing Vessel Logistics with NextGen AI',
            'description' => 'Introduce our autonomous supply chain algorithms to maritime shipping and port logistics companies.',
        ]);

        $campaign = MarketingCampaign::where('name', 'Maritime Tech Outreach')->first();
        $this->assertNotNull($campaign);
        $this->assertEquals('ai_personalized', $campaign->campaign_type);
        $this->assertEquals([$list->id], $campaign->contact_list_ids);
        $this->assertEquals('Optimizing Vessel Logistics with NextGen AI', $campaign->subject);
        $this->assertEquals('Introduce our autonomous supply chain algorithms to maritime shipping and port logistics companies.', $campaign->description);
        $this->assertEquals('draft', $campaign->status);

        $response->assertRedirect(route('admin.marketing.ai-campaigns.show', $campaign));
    }

    public function test_ai_email_generation_creates_unique_personalized_emails_for_contacts()
    {
        $list = MarketingContactList::create(['name' => 'B2B Enterprise Leads']);

        $contact1 = MarketingContact::create([
            'name' => 'Cathy Hodge',
            'email' => 'cathy.hodge@smartmaritimenetwork.com',
            'company' => 'Smart Maritime Network',
            'website' => 'https://smartmaritimenetwork.com',
            'business_type' => 'Maritime Transport',
            'business_description' => 'Global forum and intelligence platform for vessel digitalization and green shipping technology.',
            'consent_status' => 'granted',
            'subscription_status' => 'subscribed',
            'verification_status' => 'valid',
        ]);

        $contact2 = MarketingContact::create([
            'name' => 'William Dawes',
            'email' => 'will@mkango.ca',
            'company' => 'Mkango Resources Ltd.',
            'website' => 'https://www.mkango.ca',
            'business_type' => 'Mineral Resources & Mining',
            'business_description' => 'Rare earth exploration and sustainable mining enterprise developing clean energy minerals.',
            'consent_status' => 'granted',
            'subscription_status' => 'subscribed',
            'verification_status' => 'valid',
        ]);

        $list->contacts()->attach([$contact1->id, $contact2->id]);

        $campaign = MarketingCampaign::create([
            'name' => 'Enterprise Services Intro',
            'campaign_type' => 'ai_personalized',
            'subject' => 'Strategic AI Partnerships',
            'description' => 'Introduce our AI analytics services and show how we help enterprise operations scale.',
            'contact_list_ids' => [$list->id],
            'status' => 'draft',
        ]);

        // Trigger AI Generation
        $generateResponse = $this->actingAs($this->user)->post(route('admin.marketing.ai-campaigns.generate', $campaign));
        $generateResponse->assertSessionHas('success');

        $email1 = MarketingEmail::where('campaign_id', $campaign->id)
            ->where('contact_id', $contact1->id)
            ->first();

        $email2 = MarketingEmail::where('campaign_id', $campaign->id)
            ->where('contact_id', $contact2->id)
            ->first();

        $this->assertNotNull($email1, 'Contact 1 should have a generated marketing email');
        $this->assertNotNull($email2, 'Contact 2 should have a generated marketing email');

        // Verify uniqueness
        $this->assertNotEquals($email1->subject, $email2->subject, 'Subjects must be unique per prospect industry');
        $this->assertNotEquals($email1->body_html, $email2->body_html, 'Body content must be personalized per company');

        // Check company name inclusion
        $this->assertStringContainsString('Smart Maritime Network', $email1->body_html);
        $this->assertStringContainsString('Mkango Resources Ltd.', $email2->body_html);

        // Verify initial approval state is false (requires Admin review)
        $this->assertFalse($email1->is_approved);
        $this->assertFalse($email2->is_approved);
    }

    public function test_approve_individual_and_approve_all_emails()
    {
        $list = MarketingContactList::create(['name' => 'Approval Test List']);

        $contact1 = MarketingContact::create([
            'name' => 'Lead One',
            'email' => 'one@test.com',
            'consent_status' => 'granted',
            'subscription_status' => 'subscribed',
            'verification_status' => 'valid',
        ]);

        $contact2 = MarketingContact::create([
            'name' => 'Lead Two',
            'email' => 'two@test.com',
            'consent_status' => 'granted',
            'subscription_status' => 'subscribed',
            'verification_status' => 'valid',
        ]);

        $list->contacts()->attach([$contact1->id, $contact2->id]);

        $campaign = MarketingCampaign::create([
            'name' => 'Approval Campaign',
            'campaign_type' => 'ai_personalized',
            'subject' => 'Testing Approval',
            'description' => 'Brief description',
            'contact_list_ids' => [$list->id],
            'status' => 'draft',
        ]);

        $email1 = MarketingEmail::create([
            'campaign_id' => $campaign->id,
            'contact_id' => $contact1->id,
            'recipient_email' => $contact1->email,
            'subject' => 'Draft 1',
            'body_html' => '<p>Content 1</p>',
            'status' => 'generated',
            'is_approved' => false,
        ]);

        $email2 = MarketingEmail::create([
            'campaign_id' => $campaign->id,
            'contact_id' => $contact2->id,
            'recipient_email' => $contact2->email,
            'subject' => 'Draft 2',
            'body_html' => '<p>Content 2</p>',
            'status' => 'generated',
            'is_approved' => false,
        ]);

        // Toggle approve email 1
        $this->actingAs($this->user)->post(route('admin.marketing.ai-campaigns.approve', $campaign), [
            'email_id' => $email1->id,
        ]);

        $email1->refresh();
        $this->assertTrue($email1->is_approved);
        $this->assertFalse($email2->fresh()->is_approved);

        // Approve all
        $this->actingAs($this->user)->post(route('admin.marketing.ai-campaigns.approve', $campaign), [
            'approve_all' => 1,
        ]);

        $this->assertTrue($email1->fresh()->is_approved);
        $this->assertTrue($email2->fresh()->is_approved);
    }

    public function test_edit_and_update_ai_email_draft()
    {
        $contact = MarketingContact::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'consent_status' => 'granted',
            'subscription_status' => 'subscribed',
            'verification_status' => 'valid',
        ]);

        $campaign = MarketingCampaign::create([
            'name' => 'Edit Draft Campaign',
            'campaign_type' => 'ai_personalized',
            'subject' => 'Initial Subject',
            'description' => 'Initial Description',
            'status' => 'draft',
        ]);

        $email = MarketingEmail::create([
            'campaign_id' => $campaign->id,
            'contact_id' => $contact->id,
            'recipient_email' => $contact->email,
            'subject' => 'AI Generated Subject',
            'body_html' => '<p>Original AI draft text</p>',
            'status' => 'generated',
            'is_approved' => false,
        ]);

        $response = $this->actingAs($this->user)->put(
            route('admin.marketing.ai-campaigns.update-email', [$campaign, $email]),
            [
                'subject' => 'Admin Polished Custom Subject for Jane',
                'body_html' => '<p>Hello Jane, this is a refined custom pitch.</p>',
            ]
        );

        $response->assertSessionHas('success');

        $email->refresh();
        $this->assertEquals('Admin Polished Custom Subject for Jane', $email->subject);
        $this->assertEquals('<p>Hello Jane, this is a refined custom pitch.</p>', $email->body_html);
        $this->assertTrue($email->is_approved, 'Updating an email draft should automatically mark it approved');
    }

    public function test_dispatch_ai_personalized_campaign_instant()
    {
        MarketingSetting::set('aws_ses_region', 'ap-south-1');
        MarketingSetting::set('sender_email', 'noreply@thenexteck.com');

        $list = MarketingContactList::create(['name' => 'Dispatch List']);

        $contact = MarketingContact::create([
            'name' => 'Sam Contact',
            'email' => 'sam@dispatch-test.com',
            'company' => 'Apex Logistics',
            'consent_status' => 'granted',
            'subscription_status' => 'subscribed',
            'verification_status' => 'valid',
            'unsubscribe_token' => 'token-sam-123',
        ]);

        $list->contacts()->attach($contact->id);

        $campaign = MarketingCampaign::create([
            'name' => 'Live Instant AI Campaign',
            'campaign_type' => 'ai_personalized',
            'subject' => 'Next-Day Freight Operations',
            'description' => 'Freight logistics scaling proposal',
            'contact_list_ids' => [$list->id],
            'status' => 'draft',
        ]);

        $email = MarketingEmail::create([
            'campaign_id' => $campaign->id,
            'contact_id' => $contact->id,
            'recipient_email' => $contact->email,
            'recipient_name' => $contact->name,
            'subject' => 'Customized Logistics AI for Apex Logistics',
            'body_html' => '<p>Hi Sam, scaling freight for Apex Logistics.</p>',
            'body_text' => 'Hi Sam, scaling freight for Apex Logistics.',
            'status' => 'generated',
            'is_approved' => true,
        ]);

        // Start campaign in instant mode
        $response = $this->actingAs($this->user)->post(
            route('admin.marketing.ai-campaigns.start', $campaign),
            ['mode' => 'instant']
        );

        $response->assertSessionHas('success');

        $campaign->refresh();
        $this->assertContains($campaign->status, ['running', 'completed']);

        $email->refresh();
        // Since AWS credentials aren't set in testing, SES mock will mark as sent or failed depending on mock config
        $this->assertContains($email->status, ['sent', 'delivered', 'failed']);
    }
}
