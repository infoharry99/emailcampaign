<?php

namespace Tests\Feature;

use App\Models\MarketingCampaign;
use App\Models\MarketingContact;
use App\Models\MarketingEmail;
use App\Models\User;
use App\Services\AwsSesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CampaignAttachmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->user = User::factory()->create([
            'email' => 'admin@thenexteck.com',
        ]);
    }

    public function test_can_create_campaign_with_multiple_attachments()
    {
        $file1 = UploadedFile::fake()->create('brochure.pdf', 100, 'application/pdf');
        $file2 = UploadedFile::fake()->create('pricing.xlsx', 50, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $response = $this->actingAs($this->user)->post('/admin/marketing/campaigns', [
            'name' => 'Q4 Enterprise Promo',
            'purpose' => 'Promote enterprise cloud services',
            'target_criteria' => 'all',
            'sending_mode' => 'template',
            'subject' => 'Exclusive Cloud Offer for {{company}}',
            'content' => '<p>Hello {{name}}, please find attached our brochure.</p>',
            'attachments' => [$file1, $file2],
        ]);

        $campaign = MarketingCampaign::where('name', 'Q4 Enterprise Promo')->first();
        $this->assertNotNull($campaign);
        $response->assertRedirect("/admin/marketing/campaigns/{$campaign->id}");

        $this->assertIsArray($campaign->attachments);
        $this->assertCount(2, $campaign->attachments);
        $this->assertEquals('brochure.pdf', $campaign->attachments[0]['name']);
        $this->assertEquals('pricing.xlsx', $campaign->attachments[1]['name']);

        Storage::disk('public')->assertExists($campaign->attachments[0]['path']);
        Storage::disk('public')->assertExists($campaign->attachments[1]['path']);
    }

    public function test_can_update_campaign_and_remove_attachment()
    {
        $file1 = UploadedFile::fake()->create('catalog.pdf', 120);
        $path1 = $file1->store('marketing/attachments', 'public');

        $campaign = MarketingCampaign::create([
            'name' => 'Brochure Campaign',
            'purpose' => 'Send catalog',
            'target_criteria' => 'all',
            'sending_mode' => 'template',
            'subject' => 'Catalog for {{name}}',
            'content' => '<p>Check out our catalog</p>',
            'attachments' => [
                [
                    'name' => 'catalog.pdf',
                    'path' => $path1,
                    'size' => 120 * 1024,
                    'mime' => 'application/pdf',
                ]
            ],
        ]);

        $file2 = UploadedFile::fake()->create('new_proposal.pdf', 80);

        $response = $this->actingAs($this->user)->put("/admin/marketing/campaigns/{$campaign->id}", [
            'name' => 'Brochure Campaign Updated',
            'purpose' => 'Send new proposal',
            'target_criteria' => 'all',
            'sending_mode' => 'template',
            'subject' => 'Updated Catalog',
            'content' => '<p>Updated content</p>',
            'remove_attachments' => [0], // remove first
            'attachments' => [$file2], // add second
        ]);

        $response->assertRedirect("/admin/marketing/campaigns/{$campaign->id}");
        $campaign->refresh();

        $this->assertCount(1, $campaign->attachments);
        $this->assertEquals('new_proposal.pdf', $campaign->attachments[0]['name']);
    }

    public function test_ses_service_handles_email_with_attachments()
    {
        $file = UploadedFile::fake()->create('spec_sheet.pdf', 50, 'application/pdf');
        $path = $file->store('marketing/attachments', 'public');

        $campaign = MarketingCampaign::create([
            'name' => 'Spec Sheet Campaign',
            'purpose' => 'Send specs',
            'target_criteria' => 'all',
            'sending_mode' => 'template',
            'subject' => 'Spec Sheet',
            'content' => '<p>Specs attached</p>',
            'attachments' => [
                [
                    'name' => 'spec_sheet.pdf',
                    'path' => $path,
                    'size' => 50 * 1024,
                    'mime' => 'application/pdf',
                ]
            ],
        ]);

        $contact = MarketingContact::create([
            'email' => 'client@enterprise.com',
            'name' => 'Client Executive',
            'consent_status' => 'granted',
            'subscription_status' => 'subscribed',
            'verification_status' => 'valid',
        ]);

        $email = MarketingEmail::create([
            'campaign_id' => $campaign->id,
            'contact_id' => $contact->id,
            'recipient_email' => $contact->email,
            'recipient_name' => $contact->name,
            'subject' => 'Your requested spec sheet',
            'body_html' => '<p>Hello, please find attached the spec sheet.</p>',
            'body_text' => 'Hello, please find attached the spec sheet.',
            'status' => 'pending',
            'generation_mode' => 'template',
        ]);

        $sesService = new AwsSesService();
        $result = $sesService->sendMarketingEmail($email);

        $this->assertTrue($result['success']);
        $this->assertEquals('sent', $email->fresh()->status);
    }
}
