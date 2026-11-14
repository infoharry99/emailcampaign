<?php

namespace Tests\Feature;

use App\Models\MarketingCampaign;
use App\Models\MarketingContact;
use App\Models\MarketingSuppression;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingModuleTest extends TestCase
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

    public function test_guest_is_redirected_to_login()
    {
        $response = $this->get('/admin/marketing/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_admin_can_access_dashboard()
    {
        $response = $this->actingAs($this->user)->get('/admin/marketing/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Marketing Intelligence');
    }

    public function test_contact_creation_with_consent()
    {
        $response = $this->actingAs($this->user)->post('/admin/marketing/contacts', [
            'name' => 'Aditya Roy',
            'email' => 'aditya@royenterprises.in',
            'company' => 'Roy Enterprises',
            'website' => 'https://royenterprises.in',
            'phone' => '+91 99999 88888',
            'business_type' => 'Retail',
            'consent_status' => 'granted',
            'consent_source' => 'Inbound Webform Demo',
            'subscription_status' => 'subscribed',
            'verification_status' => 'valid',
        ]);

        $response->assertRedirect('/admin/marketing/contacts');
        $this->assertDatabaseHas('marketing_contacts', [
            'email' => 'aditya@royenterprises.in',
            'consent_status' => 'granted',
        ]);
    }

    public function test_suppression_prevents_unsubscribed_sending()
    {
        MarketingSuppression::suppress('blocked@spam.com', 'complaint', 'Spam report', 'unit_test');
        $this->assertTrue(MarketingSuppression::isSuppressed('blocked@spam.com'));
    }

    public function test_public_unsubscribe_link_and_suppression()
    {
        $contact = MarketingContact::create([
            'name' => 'Tester',
            'email' => 'testuser@optout.com',
            'consent_status' => 'granted',
            'subscription_status' => 'subscribed',
            'verification_status' => 'valid',
        ]);

        $this->assertNotNull($contact->unsubscribe_token);

        // GET unsubscribe page
        $response = $this->get('/marketing/unsubscribe/' . $contact->unsubscribe_token);
        $response->assertStatus(200);

        // POST unsubscribe action
        $postResponse = $this->post('/marketing/unsubscribe/' . $contact->unsubscribe_token);
        $postResponse->assertStatus(200);

        $contact->refresh();
        $this->assertEquals('unsubscribed', $contact->subscription_status);
        $this->assertTrue(MarketingSuppression::isSuppressed('testuser@optout.com'));
    }

    public function test_campaign_creation()
    {
        $response = $this->actingAs($this->user)->post('/admin/marketing/campaigns', [
            'name' => 'Test Email Blast',
            'purpose' => 'Promote new AI capabilities',
            'target_criteria' => 'all',
            'sending_mode' => 'ai',
        ]);

        $this->assertDatabaseHas('marketing_campaigns', [
            'name' => 'Test Email Blast',
            'sending_mode' => 'ai',
        ]);

        $campaign = MarketingCampaign::where('name', 'Test Email Blast')->first();
        $this->assertGreaterThan(0, $campaign->total_recipients);
    }

    public function test_campaign_test_send_can_be_retried_without_unique_constraint_violation()
    {
        $campaign = MarketingCampaign::create([
            'name' => 'Pilot Campaign',
            'purpose' => 'Testing test-send deduplication',
            'target_criteria' => 'all',
            'sending_mode' => 'ai',
            'status' => 'draft',
        ]);

        // First test send
        $res1 = $this->actingAs($this->user)->post("/admin/marketing/campaigns/{$campaign->id}/test-send", [
            'test_email' => 'preview@thenexteck.com',
        ]);
        $res1->assertSessionHasNoErrors();
        $res1->assertRedirect();

        // Second test send to the exact same recipient must update rather than crash on UNIQUE constraint
        $res2 = $this->actingAs($this->user)->post("/admin/marketing/campaigns/{$campaign->id}/test-send", [
            'test_email' => 'preview@thenexteck.com',
        ]);
        $res2->assertSessionHasNoErrors();
        $res2->assertRedirect();
    }
}
