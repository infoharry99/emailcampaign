<?php

namespace Tests\Feature;

use App\Jobs\ProcessCampaignEmailsJob;
use App\Models\MarketingCampaign;
use App\Models\MarketingContact;
use App\Models\MarketingContactList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class MarketingContactListTest extends TestCase
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

    public function test_can_create_and_view_contact_lists()
    {
        $response = $this->actingAs($this->user)->post('/admin/marketing/lists', [
            'name' => 'VIP Clients',
            'description' => 'High value potential clients',
        ]);

        $list = MarketingContactList::where('name', 'VIP Clients')->first();
        $this->assertNotNull($list);
        $response->assertRedirect("/admin/marketing/lists/{$list->id}");

        $indexResponse = $this->actingAs($this->user)->get('/admin/marketing/lists');
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('VIP Clients');
    }

    public function test_can_add_and_remove_contact_to_list()
    {
        $list = MarketingContactList::create(['name' => 'Tech CEOs']);
        $contact = MarketingContact::create([
            'email' => 'ceo@techcorp.com',
            'name' => 'Elon Tech',
            'consent_status' => 'granted',
            'subscription_status' => 'subscribed',
            'verification_status' => 'valid',
        ]);

        // Add contact
        $addResponse = $this->actingAs($this->user)->post(route('admin.marketing.lists.add-contact', $list), [
            'contact_id' => $contact->id,
        ]);
        $addResponse->assertSessionHas('success');
        $this->assertTrue($list->contacts()->where('marketing_contacts.id', $contact->id)->exists());

        // Remove contact
        $removeResponse = $this->actingAs($this->user)->delete(route('admin.marketing.lists.remove-contact', [$list, $contact]));
        $removeResponse->assertSessionHas('success');
        $this->assertFalse($list->contacts()->where('marketing_contacts.id', $contact->id)->exists());
    }

    public function test_csv_import_into_new_list()
    {
        $csvContent = "First Name,Last Name,Work Email,Company,Industries\n"
            . "Harry,Sharma,harry@example.com,Nexteck,Technology\n"
            . "Vikram,Singhania,vikram@example.com,Singhania Mining,Mining";

        $file = UploadedFile::fake()->createWithContent('leads.csv', $csvContent);

        $response = $this->actingAs($this->user)->post('/admin/marketing/contacts/import', [
            'csv_file' => $file,
            'consent_confirmation' => '1',
            'consent_source' => 'Mining Conference Nov 2026',
            'new_list_name' => 'Mining Transport Leads',
        ]);

        $response->assertRedirect('/admin/marketing/contacts');
        $response->assertSessionHas('success');

        $list = MarketingContactList::where('name', 'Mining Transport Leads')->first();
        $this->assertNotNull($list);
        $this->assertEquals(2, $list->contacts()->count());

        $this->assertDatabaseHas('marketing_contacts', [
            'email' => 'harry@example.com',
            'name' => 'Harry Sharma',
            'company' => 'Nexteck',
        ]);
    }

    public function test_campaign_targets_selected_list_recipients()
    {
        $targetList = MarketingContactList::create(['name' => 'Target Audience']);
        $otherList = MarketingContactList::create(['name' => 'Other Audience']);

        // Contact 1 in target list
        $c1 = MarketingContact::create([
            'email' => 'member1@example.com',
            'name' => 'Member One',
            'consent_status' => 'granted',
            'subscription_status' => 'subscribed',
            'verification_status' => 'valid',
        ]);
        $targetList->contacts()->attach($c1->id);

        // Contact 2 in target list
        $c2 = MarketingContact::create([
            'email' => 'member2@example.com',
            'name' => 'Member Two',
            'consent_status' => 'granted',
            'subscription_status' => 'subscribed',
            'verification_status' => 'valid',
        ]);
        $targetList->contacts()->attach($c2->id);

        // Contact 3 in other list only
        $c3 = MarketingContact::create([
            'email' => 'other@example.com',
            'name' => 'Other User',
            'consent_status' => 'granted',
            'subscription_status' => 'subscribed',
            'verification_status' => 'valid',
        ]);
        $otherList->contacts()->attach($c3->id);

        // Create campaign targeting targetList
        $campaign = MarketingCampaign::create([
            'name' => 'List Specific Blast',
            'purpose' => 'Promote to selected list only',
            'target_criteria' => 'list:' . $targetList->id,
            'sending_mode' => 'template',
            'subject' => 'Special offer for list',
            'content' => '<p>Hello {{name}}</p>',
            'status' => 'draft',
        ]);

        $this->assertEquals(2, $campaign->calculateTargetRecipientsCount());

        // Dispatch instant
        ProcessCampaignEmailsJob::dispatchSync($campaign, true);

        // Verify only 2 emails sent (c1 and c2), c3 did not receive
        $this->assertEquals(2, $campaign->emails()->count());
        $this->assertDatabaseHas('marketing_emails', [
            'campaign_id' => $campaign->id,
            'contact_id' => $c1->id,
        ]);
        $this->assertDatabaseHas('marketing_emails', [
            'campaign_id' => $campaign->id,
            'contact_id' => $c2->id,
        ]);
        $this->assertDatabaseMissing('marketing_emails', [
            'campaign_id' => $campaign->id,
            'contact_id' => $c3->id,
        ]);
    }
}
