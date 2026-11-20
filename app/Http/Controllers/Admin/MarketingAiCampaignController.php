<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateAiCampaignEmailsJob;
use App\Jobs\ProcessCampaignEmailsJob;
use App\Models\MarketingCampaign;
use App\Models\MarketingContact;
use App\Models\MarketingContactList;
use App\Models\MarketingEmail;
use App\Services\AwsSesService;
use App\Services\OpenAIService;
use Illuminate\Http\Request;

class MarketingAiCampaignController extends Controller
{
    public function index()
    {
        $campaigns = MarketingCampaign::where('campaign_type', 'ai_personalized')
            ->latest()
            ->paginate(15);

        foreach ($campaigns as $camp) {
            $camp->recalculateStats();
        }

        return view('marketing.ai_campaigns.index', compact('campaigns'));
    }

    public function create()
    {
        $lists = MarketingContactList::withCount('contacts')->orderBy('name')->get();
        $totalContacts = MarketingContact::where('subscription_status', 'subscribed')
            ->where('consent_status', 'granted')
            ->count();

        return view('marketing.ai_campaigns.create', compact('lists', 'totalContacts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_list_ids' => 'required|array|min:1',
            'contact_list_ids.*' => 'exists:marketing_contact_lists,id',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        $listIds = array_map('intval', $validated['contact_list_ids']);

        $campaign = MarketingCampaign::create([
            'name' => $validated['name'],
            'campaign_type' => 'ai_personalized',
            'subject' => $validated['subject'],
            'description' => $validated['description'],
            'purpose' => $validated['subject'],
            'product_service_details' => $validated['description'],
            'contact_list_ids' => $listIds,
            'target_criteria' => 'lists:' . implode(',', $listIds),
            'sending_mode' => 'ai',
            'status' => 'draft',
        ]);

        $campaign->recalculateStats();

        return redirect()->route('admin.marketing.ai-campaigns.show', $campaign)
            ->with('success', 'AI Personalized Campaign created! Now generate customized emails for each prospect.');
    }

    public function show(MarketingCampaign $campaign, Request $request)
    {
        $campaign->recalculateStats();

        $statusFilter = $request->query('status');
        $search = $request->query('search');

        // Target contacts query
        $targetContacts = $campaign->targetContactsQuery()->get();

        // Emails query
        $emailsQuery = $campaign->emails()->with('contact');

        if ($search) {
            $emailsQuery->whereHas('contact', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('company', 'like', "%{$search}%");
            });
        }

        if ($statusFilter === 'approved') {
            $emailsQuery->where('is_approved', true);
        } elseif ($statusFilter === 'pending_approval') {
            $emailsQuery->where('is_approved', false)->where('status', 'generated');
        } elseif ($statusFilter === 'sent') {
            $emailsQuery->whereIn('status', ['sent', 'delivered', 'opened', 'clicked']);
        } elseif ($statusFilter === 'failed') {
            $emailsQuery->whereIn('status', ['failed', 'bounced']);
        }

        $emails = $emailsQuery->latest()->paginate(20)->withQueryString();

        $lists = MarketingContactList::whereIn('id', $campaign->contact_list_ids ?? [])->get();
        $approvedCount = $campaign->emails()->where('is_approved', true)->count();
        $generatedCount = $campaign->emails()->whereNotNull('generated_at')->count();
        $targetCount = $targetContacts->count();

        return view('marketing.ai_campaigns.show', compact(
            'campaign',
            'emails',
            'targetContacts',
            'lists',
            'approvedCount',
            'generatedCount',
            'targetCount',
            'statusFilter',
            'search'
        ));
    }

    public function edit(MarketingCampaign $campaign)
    {
        $lists = MarketingContactList::withCount('contacts')->orderBy('name')->get();
        return view('marketing.ai_campaigns.edit', compact('campaign', 'lists'));
    }

    public function update(Request $request, MarketingCampaign $campaign)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_list_ids' => 'required|array|min:1',
            'contact_list_ids.*' => 'exists:marketing_contact_lists,id',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        $listIds = array_map('intval', $validated['contact_list_ids']);

        $campaign->update([
            'name' => $validated['name'],
            'subject' => $validated['subject'],
            'description' => $validated['description'],
            'purpose' => $validated['subject'],
            'product_service_details' => $validated['description'],
            'contact_list_ids' => $listIds,
            'target_criteria' => 'lists:' . implode(',', $listIds),
        ]);

        $campaign->recalculateStats();

        return redirect()->route('admin.marketing.ai-campaigns.show', $campaign)
            ->with('success', 'Campaign details updated successfully.');
    }

    /**
     * Generate or regenerate AI personalized emails for all or ungenerated contacts.
     */
    public function generate(MarketingCampaign $campaign, Request $request, OpenAIService $aiService)
    {
        $force = $request->boolean('force');
        $contactId = $request->input('contact_id');

        if ($contactId) {
            $contact = MarketingContact::findOrFail($contactId);
            $draft = $aiService->generatePersonalizedContactEmail($contact, $campaign);

            MarketingEmail::updateOrCreate(
                [
                    'campaign_id' => $campaign->id,
                    'contact_id' => $contact->id,
                ],
                [
                    'recipient_email' => $contact->email,
                    'recipient_name' => $contact->name,
                    'subject' => $draft['subject'],
                    'body_html' => $draft['body_html'],
                    'body_text' => $draft['body_text'],
                    'ai_rationale' => $draft['ai_rationale'] ?? null,
                    'is_ai_generated' => true,
                    'status' => 'generated',
                    'generated_at' => now(),
                    'ai_metadata' => $draft['metadata'] ?? null,
                    'is_approved' => false,
                ]
            );

            $campaign->recalculateStats();

            return back()->with('success', "Personalized email regenerated successfully for {$contact->name} ({$contact->company}).");
        }

        // Process in batch synchronously (or async if large)
        $contacts = $campaign->targetContactsQuery()->get();

        $processed = 0;
        foreach ($contacts as $contact) {
            $existing = MarketingEmail::where('campaign_id', $campaign->id)
                ->where('contact_id', $contact->id)
                ->first();

            // If already sent or delivered, don't overwrite
            if ($existing && in_array($existing->status, ['sent', 'delivered', 'queued', 'bounced'])) {
                continue;
            }

            // If already generated and not forcing, skip
            if ($existing && $existing->status === 'generated' && !$force) {
                continue;
            }

            $draft = $aiService->generatePersonalizedContactEmail($contact, $campaign);

            MarketingEmail::updateOrCreate(
                [
                    'campaign_id' => $campaign->id,
                    'contact_id' => $contact->id,
                ],
                [
                    'recipient_email' => $contact->email,
                    'recipient_name' => $contact->name,
                    'subject' => $draft['subject'],
                    'body_html' => $draft['body_html'],
                    'body_text' => $draft['body_text'],
                    'ai_rationale' => $draft['ai_rationale'] ?? null,
                    'is_ai_generated' => true,
                    'status' => 'generated',
                    'generated_at' => now(),
                    'ai_metadata' => $draft['metadata'] ?? null,
                    'is_approved' => false,
                ]
            );

            $processed++;
        }

        $campaign->recalculateStats();

        return back()->with('success', "AI successfully processed {$processed} contacts! Each prospect now has a tailored email ready for review.");
    }

    /**
     * Toggle approval state or approve all.
     */
    public function approve(MarketingCampaign $campaign, Request $request)
    {
        if ($request->has('email_id')) {
            $email = MarketingEmail::where('campaign_id', $campaign->id)
                ->where('id', $request->input('email_id'))
                ->firstOrFail();

            $email->update(['is_approved' => !$email->is_approved]);

            return back()->with('success', "Approval status updated for {$email->recipient_email}.");
        }

        if ($request->boolean('approve_all')) {
            $campaign->emails()->where('status', 'generated')->update(['is_approved' => true]);
            return back()->with('success', 'All generated emails have been approved and are ready for dispatch.');
        }

        return back();
    }

    /**
     * Edit generated subject and body before sending.
     */
    public function updateEmail(MarketingCampaign $campaign, MarketingEmail $email, Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'body_html' => 'required|string',
        ]);

        $email->update([
            'subject' => $validated['subject'],
            'body_html' => $validated['body_html'],
            'body_text' => strip_tags($validated['body_html']),
            'is_approved' => true, // Editing marks it approved
        ]);

        return back()->with('success', "Email draft for {$email->recipient_email} updated and approved.");
    }

    /**
     * Start/dispatch campaign (Approved only or all).
     */
    public function start(Request $request, MarketingCampaign $campaign)
    {
        $onlyApproved = $request->boolean('only_approved', true);

        // If only sending approved, ensure there are approved emails
        $approvedEmailsCount = $campaign->emails()->where('is_approved', true)->count();
        if ($onlyApproved && $approvedEmailsCount === 0) {
            // If none explicitly approved, check if admin wants to send all generated
            if ($campaign->emails()->where('status', 'generated')->count() === 0) {
                return back()->with('error', 'No emails have been generated yet. Click "Generate AI Emails" first.');
            }
            // Auto-approve generated if user confirmed send
            $campaign->emails()->where('status', 'generated')->update(['is_approved' => true]);
        }

        $campaign->update([
            'status' => 'running',
            'started_at' => $campaign->started_at ?? now(),
        ]);

        $instant = $request->has('instant') || $request->input('mode') === 'instant' || config('queue.default') === 'sync';

        if ($instant) {
            ProcessCampaignEmailsJob::dispatchSync($campaign, true);
            return back()->with('success', 'AI Personalized Campaign started! All emails processed and dispatched immediately.');
        }

        ProcessCampaignEmailsJob::dispatch($campaign);
        return back()->with('success', 'Campaign dispatched to queue workers successfully.');
    }

    public function testSend(Request $request, MarketingCampaign $campaign, AwsSesService $sesService, OpenAIService $aiService)
    {
        $request->validate([
            'test_email' => 'required|email',
            'contact_id' => 'nullable|exists:marketing_contacts,id',
        ]);

        $testContact = null;
        if ($request->filled('contact_id')) {
            $testContact = MarketingContact::find($request->input('contact_id'));
        }

        if (!$testContact) {
            $testContact = MarketingContact::firstOrCreate(
                ['email' => $request->input('test_email')],
                [
                    'name' => 'Sample Partner',
                    'company' => 'Smart Maritime Network',
                    'website' => 'https://smartmaritimenetwork.com',
                    'business_type' => 'Maritime Transport',
                    'business_description' => 'Leading platform for shipping, maritime logistics, vessel digitalization, and port operations.',
                    'consent_status' => 'granted',
                    'subscription_status' => 'subscribed',
                    'verification_status' => 'valid',
                    'unsubscribe_token' => 'test-preview-' . uniqid(),
                ]
            );
        }

        $draft = $aiService->generatePersonalizedContactEmail($testContact, $campaign);

        $testEmailRecord = MarketingEmail::updateOrCreate(
            [
                'campaign_id' => $campaign->id,
                'contact_id' => $testContact->id,
            ],
            [
                'recipient_email' => $request->input('test_email'),
                'recipient_name' => $testContact->name,
                'subject' => '[TEST AI] ' . $draft['subject'],
                'body_html' => $draft['body_html'],
                'body_text' => $draft['body_text'],
                'ai_rationale' => $draft['ai_rationale'] ?? null,
                'status' => 'pending',
                'error_message' => null,
                'is_ai_generated' => true,
            ]
        );

        $result = $sesService->sendMarketingEmail($testEmailRecord);

        if ($result['success']) {
            return back()->with('success', "Personalized test email successfully dispatched to {$request->input('test_email')}.");
        } else {
            $msg = $result['error'] ?? $result['reason'] ?? 'Unknown error';
            return back()->with('error', "Failed sending test email: {$msg}");
        }
    }

    public function destroy(MarketingCampaign $campaign)
    {
        $campaign->delete();
        return redirect()->route('admin.marketing.ai-campaigns.index')
            ->with('success', 'AI Personalized Campaign deleted successfully.');
    }
}
