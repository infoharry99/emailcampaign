<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketingContact;
use App\Models\MarketingConsentLog;
use App\Models\MarketingContactList;
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
        $listId = $request->get('list_id');

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

        if ($listId) {
            $query->whereHas('lists', function ($q) use ($listId) {
                $q->where('marketing_contact_lists.id', $listId);
            });
        }

        $contacts = $query->with('lists')->latest()->paginate(25)->withQueryString();
        $lists = MarketingContactList::orderBy('name')->get();

        return view('marketing.contacts.index', compact('contacts', 'search', 'consent', 'subscription', 'verification', 'listId', 'lists'));
    }

    public function create()
    {
        $lists = MarketingContactList::orderBy('name')->get();
        return view('marketing.contacts.create', compact('lists'));
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
            'business_description' => 'nullable|string',
            'consent_status' => 'required|in:granted,revoked,pending',
            'consent_source' => 'required|string|max:255',
            'consent_proof' => 'nullable|string',
            'subscription_status' => 'required|in:subscribed,unsubscribed',
            'verification_status' => 'required|in:unverified,valid,invalid,risky',
            'list_ids' => 'nullable|array',
            'list_ids.*' => 'exists:marketing_contact_lists,id',
        ]);

        if ($validated['consent_status'] === 'granted') {
            $validated['consent_date'] = now();
        }

        $contact = MarketingContact::create($validated);

        if ($request->has('list_ids')) {
            $contact->lists()->sync($request->input('list_ids', []));
        }

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
        $lists = MarketingContactList::orderBy('name')->get();
        $contact->load('lists');
        return view('marketing.contacts.edit', compact('contact', 'lists'));
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
            'business_description' => 'nullable|string',
            'consent_status' => 'required|in:granted,revoked,pending',
            'consent_source' => 'nullable|string|max:255',
            'subscription_status' => 'required|in:subscribed,unsubscribed',
            'verification_status' => 'required|in:unverified,valid,invalid,risky',
            'list_ids' => 'nullable|array',
            'list_ids.*' => 'exists:marketing_contact_lists,id',
        ]);

        $contact->update($validated);

        $contact->lists()->sync($request->input('list_ids', []));

        return redirect()->route('admin.marketing.contacts.index')->with('success', 'Contact updated successfully.');
    }

    public function destroy(MarketingContact $contact)
    {
        $contact->delete();
        return redirect()->route('admin.marketing.contacts.index')->with('success', 'Contact removed successfully.');
    }

    public function showImport()
    {
        $lists = MarketingContactList::withCount('contacts')->orderBy('name')->get();
        return view('marketing.contacts.import', compact('lists'));
    }

    public function processImport(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
            'consent_confirmation' => 'required|accepted',
            'consent_source' => 'required|string|max:255',
            'contact_list_id' => 'nullable|exists:marketing_contact_lists,id',
            'new_list_name' => 'nullable|string|max:255',
        ]);

        // Resolve or create target contact list
        $targetList = null;
        if ($request->filled('new_list_name')) {
            $targetList = MarketingContactList::firstOrCreate([
                'name' => trim($request->input('new_list_name')),
            ], [
                'description' => 'Imported via CSV on ' . now()->format('d M Y, h:i A'),
            ]);
        } elseif ($request->filled('contact_list_id')) {
            $targetList = MarketingContactList::find($request->input('contact_list_id'));
        }

        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');

        $header = fgetcsv($handle);
        if (!$header) {
            return back()->with('error', 'CSV file is empty.');
        }

        // Clean UTF-8 BOM if present
        if (isset($header[0])) {
            $header[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $header[0]);
        }
        $header = array_map(fn($col) => strtolower(trim($col)), $header);

        // Helper to find column index matching candidate aliases
        $findCol = function(array $candidates) use ($header) {
            foreach ($candidates as $cand) {
                $idx = array_search(strtolower($cand), $header);
                if ($idx !== false) return $idx;
            }
            return false;
        };

        $emailIdx = $findCol(['email', 'work email', 'work_email', 'email address', 'e-mail']);
        if ($emailIdx === false) {
            return back()->with('error', 'CSV must have an "email" or "work email" column.');
        }

        $nameIdx = $findCol(['name', 'full name', 'full_name', 'contact name']);
        $firstNameIdx = $findCol(['first name', 'first_name', 'fname']);
        $lastNameIdx = $findCol(['last name', 'last_name', 'lname']);
        $companyIdx = $findCol(['company', 'company name', 'organization', 'account name']);
        $websiteIdx = $findCol(['website', 'web', 'url', 'company website']);
        $phoneIdx = $findCol(['phone', 'phone number', 'mobile', 'telephone']);
        $typeIdx = $findCol(['business_type', 'industry', 'industries', 'sector']);
        $descIdx = $findCol(['business_description', 'description', 'business description', 'about', 'overview', 'summary', 'notes']);

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
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }

                // Check suppression
                if (MarketingSuppression::isSuppressed($email)) {
                    $suppressed++;
                    continue;
                }

                // Resolve contact name from single name or first+last name columns
                $contactName = null;
                if ($nameIdx !== false && !empty($row[$nameIdx])) {
                    $contactName = trim($row[$nameIdx]);
                } elseif ($firstNameIdx !== false || $lastNameIdx !== false) {
                    $first = $firstNameIdx !== false ? trim($row[$firstNameIdx] ?? '') : '';
                    $last = $lastNameIdx !== false ? trim($row[$lastNameIdx] ?? '') : '';
                    $contactName = trim("{$first} {$last}") ?: null;
                }

                $company = $companyIdx !== false ? (trim($row[$companyIdx] ?? '') ?: null) : null;
                $website = $websiteIdx !== false ? (trim($row[$websiteIdx] ?? '') ?: null) : null;
                $phone = $phoneIdx !== false ? (trim($row[$phoneIdx] ?? '') ?: null) : null;
                $businessType = ($typeIdx !== false && !empty($row[$typeIdx])) ? trim($row[$typeIdx]) : 'Business';
                $businessDescription = ($descIdx !== false && !empty($row[$descIdx])) ? trim($row[$descIdx]) : null;

                // Check existing
                $existing = MarketingContact::where('email', $email)->first();
                if ($existing) {
                    if ($existing->subscription_status === 'unsubscribed') {
                        $skipped++;
                        continue;
                    }

                    $updateData = [];
                    if ($contactName && empty($existing->name)) $updateData['name'] = $contactName;
                    if ($company && empty($existing->company)) $updateData['company'] = $company;
                    if ($website && empty($existing->website)) $updateData['website'] = $website;
                    if ($phone && empty($existing->phone)) $updateData['phone'] = $phone;
                    if ($businessType !== 'Business' && $existing->business_type === 'Business') $updateData['business_type'] = $businessType;
                    if ($businessDescription && empty($existing->business_description)) $updateData['business_description'] = $businessDescription;

                    if (!empty($updateData)) {
                        $existing->update($updateData);
                    }

                    if ($targetList) {
                        $existing->lists()->syncWithoutDetaching([$targetList->id]);
                    }

                    $imported++;
                    continue;
                }

                $contact = MarketingContact::create([
                    'email' => $email,
                    'name' => $contactName,
                    'company' => $company,
                    'website' => $website,
                    'phone' => $phone,
                    'business_type' => $businessType,
                    'business_description' => $businessDescription,
                    'consent_status' => 'granted',
                    'consent_date' => now(),
                    'consent_source' => $request->input('consent_source'),
                    'consent_proof' => 'Bulk CSV import with documented opt-in assertion.',
                    'subscription_status' => 'subscribed',
                    'verification_status' => 'valid',
                ]);

                if ($targetList) {
                    $contact->lists()->syncWithoutDetaching([$targetList->id]);
                }

                MarketingConsentLog::create([
                    'contact_id' => $contact->id,
                    'email' => $email,
                    'action' => 'granted',
                    'source' => $request->input('consent_source'),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'notes' => 'Imported via CSV with confirmed opt-in.' . ($targetList ? " Assigned to list: {$targetList->name}" : ''),
                    'logged_at' => now(),
                ]);

                $imported++;
            }

            DB::commit();
            fclose($handle);

            $msg = "Import complete! Successfully imported/updated {$imported} contacts. Skipped {$skipped} unsubscribed contacts. Blocked {$suppressed} suppressed addresses.";
            if ($targetList) {
                $msg .= " All contacts were assigned to List '{$targetList->name}'.";
            }

            return redirect()->route('admin.marketing.contacts.index')->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);
            return back()->with('error', 'Error processing CSV: ' . $e->getMessage());
        }
    }
}
