<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessCampaignEmailsJob;
use App\Models\MarketingCampaign;
use App\Models\MarketingContact;
use App\Models\MarketingContactList;
use App\Models\MarketingEmail;
use App\Models\MarketingTemplate;
use App\Services\AwsSesService;
use App\Services\OpenAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MarketingCampaignController extends Controller
{
    public function index()
    {
        $campaigns = MarketingCampaign::latest()->paginate(15);
        foreach ($campaigns as $camp) {
            if ($camp->total_recipients === 0) {
                $camp->recalculateStats();
            }
        }
        return view('marketing.campaigns.index', compact('campaigns'));
    }

    public function create(Request $request)
    {
        $templates = MarketingTemplate::where('is_active', true)->get();
        $totalEligibleContacts = MarketingContact::where('subscription_status', 'subscribed')
            ->where('consent_status', 'granted')
            ->count();
        $lists = MarketingContactList::withCount('contacts')->orderBy('name')->get();
        $defaultTarget = $request->query('target_criteria', 'all');

        return view('marketing.campaigns.create', compact('templates', 'totalEligibleContacts', 'lists', 'defaultTarget'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'purpose' => 'required|string',
            'product_service_details' => 'nullable|string',
            'target_criteria' => 'required|string',
            'subject' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'sending_date' => 'nullable|date',
            'sending_time' => 'nullable',
            'sending_mode' => 'required|in:ai,template,both',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:20480',
        ]);

        if (!empty($validated['sending_date']) && !empty($validated['sending_time'])) {
            $validated['scheduled_at'] = $validated['sending_date'] . ' ' . $validated['sending_time'];
            $validated['status'] = 'scheduled';
        } else {
            $validated['status'] = 'draft';
        }

        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file->isValid()) {
                    $origName = $file->getClientOriginalName();
                    $storedPath = $file->store('marketing/attachments', 'public');
                    $attachments[] = [
                        'name' => $origName,
                        'path' => $storedPath,
                        'size' => $file->getSize(),
                        'mime' => $file->getMimeType(),
                    ];
                }
            }
        }
        $validated['attachments'] = !empty($attachments) ? $attachments : null;

        $campaign = MarketingCampaign::create($validated);
        $campaign->recalculateStats();

        return redirect()->route('admin.marketing.campaigns.show', $campaign)
            ->with('success', 'Campaign created successfully.');
    }

    public function show(MarketingCampaign $campaign)
    {
        $campaign->recalculateStats();
        $emails = $campaign->emails()->with('contact')->latest()->paginate(25);
        return view('marketing.campaigns.show', compact('campaign', 'emails'));
    }

    public function edit(MarketingCampaign $campaign)
    {
        $templates = MarketingTemplate::where('is_active', true)->get();
        $lists = MarketingContactList::withCount('contacts')->orderBy('name')->get();
        return view('marketing.campaigns.edit', compact('campaign', 'templates', 'lists'));
    }

    public function update(Request $request, MarketingCampaign $campaign)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'purpose' => 'required|string',
            'product_service_details' => 'nullable|string',
            'target_criteria' => 'required|string',
            'subject' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'sending_mode' => 'required|in:ai,template,both',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:20480',
            'remove_attachments' => 'nullable|array',
        ]);

        $existingAttachments = $campaign->attachments ?? [];
        if ($request->has('remove_attachments')) {
            $removeIndices = array_map('intval', (array) $request->input('remove_attachments'));
            $filtered = [];
            foreach ($existingAttachments as $idx => $att) {
                if (in_array((int)$idx, $removeIndices, true)) {
                    if (!empty($att['path'])) {
                        Storage::disk('public')->delete($att['path']);
                    }
                } else {
                    $filtered[] = $att;
                }
            }
            $existingAttachments = array_values($filtered);
        }

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file->isValid()) {
                    $origName = $file->getClientOriginalName();
                    $storedPath = $file->store('marketing/attachments', 'public');
                    $existingAttachments[] = [
                        'name' => $origName,
                        'path' => $storedPath,
                        'size' => $file->getSize(),
                        'mime' => $file->getMimeType(),
                    ];
                }
            }
        }
        $validated['attachments'] = !empty($existingAttachments) ? $existingAttachments : null;

        $campaign->update($validated);
        $campaign->recalculateStats();

        return redirect()->route('admin.marketing.campaigns.show', $campaign)
            ->with('success', 'Campaign updated successfully.');
    }

    public function start(Request $request, MarketingCampaign $campaign)
    {
        $campaign->update([
            'status' => 'running',
            'started_at' => $campaign->started_at ?? now(),
        ]);

        $instant = $request->has('instant') || $request->input('mode') === 'instant' || config('queue.default') === 'sync';

        if ($instant) {
            ProcessCampaignEmailsJob::dispatchSync($campaign, true);
            return back()->with('success', 'Campaign started! All emails processed and dispatched immediately.');
        }

        ProcessCampaignEmailsJob::dispatch($campaign);
        return back()->with('success', 'Campaign dispatched to queue workers successfully.');
    }

    public function pause(MarketingCampaign $campaign)
    {
        $campaign->update(['status' => 'paused']);
        return back()->with('success', 'Campaign has been paused. Any running jobs will halt.');
    }

    public function resume(Request $request, MarketingCampaign $campaign)
    {
        $campaign->update(['status' => 'running']);

        $instant = $request->has('instant') || $request->input('mode') === 'instant' || config('queue.default') === 'sync';

        if ($instant) {
            ProcessCampaignEmailsJob::dispatchSync($campaign, true);
            return back()->with('success', 'Campaign resumed and emails dispatched immediately.');
        }

        ProcessCampaignEmailsJob::dispatch($campaign);
        return back()->with('success', 'Campaign resumed successfully.');
    }

    public function cancel(MarketingCampaign $campaign)
    {
        $campaign->update(['status' => 'cancelled']);
        return back()->with('success', 'Campaign cancelled.');
    }

    public function duplicate(MarketingCampaign $campaign)
    {
        $newCampaign = $campaign->replicate([
            'total_recipients', 'generated_count', 'queued_count', 'sent_count',
            'delivered_count', 'opened_count', 'clicked_count', 'bounced_count',
            'complained_count', 'failed_count', 'started_at', 'completed_at'
        ]);
        $newCampaign->name = 'Copy of ' . $campaign->name;
        $newCampaign->status = 'draft';
        $newCampaign->save();
        $newCampaign->recalculateStats();

        return redirect()->route('admin.marketing.campaigns.show', $newCampaign)
            ->with('success', 'Campaign duplicated as draft.');
    }

    public function destroy(MarketingCampaign $campaign)
    {
        $campaign->delete();

        return redirect()->route('admin.marketing.campaigns.index')
            ->with('success', 'Campaign deleted successfully.');
    }

    public function testSend(Request $request, MarketingCampaign $campaign, AwsSesService $sesService, OpenAIService $aiService)
    {
        $request->validate([
            'test_email' => 'required|email',
        ]);

        $testContact = MarketingContact::firstOrCreate(
            ['email' => $request->input('test_email')],
            [
                'name' => 'Test Lead',
                'company' => 'Acme Corporation',
                'website' => 'https://example.com',
                'business_type' => 'Technology',
                'consent_status' => 'granted',
                'subscription_status' => 'subscribed',
                'verification_status' => 'valid',
                'unsubscribe_token' => 'test-preview-' . uniqid(),
            ]
        );

        if ($testContact->consent_status !== 'granted') {
            $testContact->update(['consent_status' => 'granted', 'subscription_status' => 'subscribed']);
        }

        if ($campaign->sending_mode === 'template' && !empty($campaign->content)) {
            $unsubUrl = route('marketing.unsubscribe', ['token' => $testContact->unsubscribe_token]);
            $bodyHtml = str_replace(
                ['{{name}}', '{{company}}', '{{email}}', '{{website}}', '{{unsubscribe_url}}'],
                [$testContact->name, $testContact->company, $testContact->email, $testContact->website, $unsubUrl],
                $campaign->content
            );
            $subject = str_replace(
                ['{{name}}', '{{company}}', '{{email}}', '{{website}}'],
                [$testContact->name, $testContact->company, $testContact->email, $testContact->website],
                $campaign->subject ?: 'Marketing Campaign Preview'
            );
            $bodyText = strip_tags($bodyHtml);
            $isAi = false;
        } else {
            $aiDraft = $aiService->generateMarketingEmail($testContact, $campaign);
            $subject = $aiDraft['subject'];
            $bodyHtml = $aiDraft['body_html'];
            $bodyText = $aiDraft['body_text'];
            $isAi = true;
        }

        $testEmailRecord = MarketingEmail::updateOrCreate(
            [
                'campaign_id' => $campaign->id,
                'contact_id' => $testContact->id,
            ],
            [
                'recipient_email' => $request->input('test_email'),
                'recipient_name' => 'Test Lead',
                'subject' => '[TEST] ' . $subject,
                'body_html' => $bodyHtml,
                'body_text' => $bodyText,
                'status' => 'pending',
                'error_message' => null,
                'is_ai_generated' => $isAi,
            ]
        );

        $result = $sesService->sendMarketingEmail($testEmailRecord);

        if ($result['success']) {
            return back()->with('success', "Test email dispatched successfully to {$request->input('test_email')}.");
        } else {
            $msg = $result['error'] ?? $result['reason'] ?? 'Unknown error';
            return back()->with('error', "Failed sending test email: {$msg}");
        }
    }
}
