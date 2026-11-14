<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketingContact;
use App\Models\MarketingConsentLog;
use App\Models\MarketingSuppression;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MarketingContactController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');
        $consent = $request->get('consent_status');
        $subscription = $request->get('subscription_status');
        $verification = $request->get('verification_status');

        $query = MarketingContact::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('company', 'like', "%{$search}%");
            });
        }

        if ($consent) {
            $query->where('consent_status', $consent);
        }

        if ($subscription) {
            $query->where('subscription_status', $subscription);
        }

        if ($verification) {
            $query->where('verification_status', $verification);
        }

        $contacts = $query->latest()->paginate(25)->withQueryString();

        return view('marketing.contacts.index', compact('contacts', 'search', 'consent', 'subscription', 'verification'));
    }

    public function create()
    {
        return view('marketing.contacts.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'required|email|max:255|unique:marketing_contacts,email',
            'company' => 'nullable|string|max:255',
            'website' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'business_type' => 'nullable|string|max:100',
            'consent_status' => 'required|in:granted,revoked,pending',
            'consent_source' => 'required|string|max:255',
            'consent_proof' => 'nullable|string',
            'subscription_status' => 'required|in:subscribed,unsubscribed',
            'verification_status' => 'required|in:unverified,valid,invalid,risky',
        ]);

        if ($validated['consent_status'] === 'granted') {
            $validated['consent_date'] = now();
        }

        $contact = MarketingContact::create($validated);

        MarketingConsentLog::create([
            'contact_id' => $contact->id,
            'email' => $contact->email,
            'action' => $contact->consent_status,
            'source' => $validated['consent_source'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'notes' => 'Contact manually created with documented consent in admin panel.',
            'logged_at' => now(),
        ]);

        return redirect()->route('admin.marketing.contacts.index')->with('success', 'Contact created successfully.');
    }

    public function edit(MarketingContact $contact)
    {
        return view('marketing.contacts.edit', compact('contact'));
    }

    public function update(Request $request, MarketingContact $contact)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'required|email|max:255|unique:marketing_contacts,email,' . $contact->id,
            'company' => 'nullable|string|max:255',
            'website' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'business_type' => 'nullable|string|max:100',
            'consent_status' => 'required|in:granted,revoked,pending',
            'consent_source' => 'nullable|string|max:255',
            'subscription_status' => 'required|in:subscribed,unsubscribed',
            'verification_status' => 'required|in:unverified,valid,invalid,risky',
        ]);

        $contact->update($validated);

        return redirect()->route('admin.marketing.contacts.index')->with('success', 'Contact updated successfully.');
    }

    public function destroy(MarketingContact $contact)
    {
        $contact->delete();
        return redirect()->route('admin.marketing.contacts.index')->with('success', 'Contact removed successfully.');
    }

    public function showImport()
    {
        return view('marketing.contacts.import');
    }

    public function processImport(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
            'consent_confirmation' => 'required|accepted',
            'consent_source' => 'required|string|max:255',
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');

        $header = fgetcsv($handle);
        if (!$header) {
            return back()->with('error', 'CSV file is empty.');
        }

        $header = array_map(fn($col) => strtolower(trim($col)), $header);
        $emailIdx = array_search('email', $header);

        if ($emailIdx === false) {
            return back()->with('error', 'CSV must have an "email" column.');
        }

        $nameIdx = array_search('name', $header);
        $companyIdx = array_search('company', $header);
        $websiteIdx = array_search('website', $header);
        $phoneIdx = array_search('phone', $header);
        $typeIdx = array_search('business_type', $header);

        $imported = 0;
        $skipped = 0;
        $suppressed = 0;

        DB::beginTransaction();

        try {
            while (($row = fgetcsv($handle)) !== false) {
                if (empty($row[$emailIdx])) {
                    continue;
                }

                $email = strtolower(trim($row[$emailIdx]));

                // Check suppression
                if (MarketingSuppression::isSuppressed($email)) {
                    $suppressed++;
                    continue;
                }

                // Check existing
                $existing = MarketingContact::where('email', $email)->first();
                if ($existing) {
                    // Update details but respect existing unsubscribe state
                    if ($existing->subscription_status === 'unsubscribed') {
                        $skipped++;
                        continue;
                    }

                    $existing->update([
                        'name' => $nameIdx !== false ? ($row[$nameIdx] ?? $existing->name) : $existing->name,
                        'company' => $companyIdx !== false ? ($row[$companyIdx] ?? $existing->company) : $existing->company,
                        'website' => $websiteIdx !== false ? ($row[$websiteIdx] ?? $existing->website) : $existing->website,
                        'phone' => $phoneIdx !== false ? ($row[$phoneIdx] ?? $existing->phone) : $existing->phone,
                    ]);
                    $imported++;
                    continue;
                }

                $contact = MarketingContact::create([
                    'email' => $email,
                    'name' => $nameIdx !== false ? ($row[$nameIdx] ?? null) : null,
                    'company' => $companyIdx !== false ? ($row[$companyIdx] ?? null) : null,
                    'website' => $websiteIdx !== false ? ($row[$websiteIdx] ?? null) : null,
                    'phone' => $phoneIdx !== false ? ($row[$phoneIdx] ?? null) : null,
                    'business_type' => $typeIdx !== false ? ($row[$typeIdx] ?? 'Business') : 'Business',
                    'consent_status' => 'granted',
                    'consent_date' => now(),
                    'consent_source' => $request->input('consent_source'),
                    'consent_proof' => 'Bulk CSV import with documented opt-in assertion.',
                    'subscription_status' => 'subscribed',
                    'verification_status' => 'valid',
                ]);

                MarketingConsentLog::create([
                    'contact_id' => $contact->id,
                    'email' => $email,
                    'action' => 'granted',
                    'source' => $request->input('consent_source'),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'notes' => 'Imported via CSV with confirmed opt-in.',
                    'logged_at' => now(),
                ]);

                $imported++;
            }

            DB::commit();
            fclose($handle);

            return redirect()->route('admin.marketing.contacts.index')
                ->with('success', "Import complete! Successfully imported/updated {$imported} contacts. Skipped {$skipped} unsubscribed contacts. Blocked {$suppressed} suppressed addresses.");
        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);
            return back()->with('error', 'Error processing CSV: ' . $e->getMessage());
        }
    }
}
