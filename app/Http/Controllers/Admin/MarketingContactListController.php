<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketingContact;
use App\Models\MarketingContactList;
use Illuminate\Http\Request;

class MarketingContactListController extends Controller
{
    public function index()
    {
        $lists = MarketingContactList::withCount('contacts')->latest()->paginate(15);
        $totalContacts = MarketingContact::count();

        return view('marketing.lists.index', compact('lists', 'totalContacts'));
    }

    public function create()
    {
        return view('marketing.lists.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $list = MarketingContactList::create($validated);

        return redirect()->route('admin.marketing.lists.show', $list)
            ->with('success', "List '{$list->name}' created successfully.");
    }

    public function show(MarketingContactList $list)
    {
        $contacts = $list->contacts()->latest()->paginate(25);
        $availableContacts = MarketingContact::whereDoesntHave('lists', function ($q) use ($list) {
            $q->where('marketing_contact_lists.id', $list->id);
        })->where('subscription_status', 'subscribed')->limit(100)->get();

        return view('marketing.lists.show', compact('list', 'contacts', 'availableContacts'));
    }

    public function edit(MarketingContactList $list)
    {
        return view('marketing.lists.edit', compact('list'));
    }

    public function update(Request $request, MarketingContactList $list)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $list->update($validated);

        return redirect()->route('admin.marketing.lists.index')
            ->with('success', "List '{$list->name}' updated successfully.");
    }

    public function destroy(MarketingContactList $list)
    {
        $name = $list->name;
        $list->contacts()->detach();
        $list->delete();

        return redirect()->route('admin.marketing.lists.index')
            ->with('success', "List '{$name}' deleted successfully (Contacts remain in database).");
    }

    public function addContact(Request $request, MarketingContactList $list)
    {
        $request->validate([
            'contact_id' => 'required|exists:marketing_contacts,id',
        ]);

        $list->contacts()->syncWithoutDetaching([$request->input('contact_id')]);

        return redirect()->route('admin.marketing.lists.show', $list)
            ->with('success', 'Contact added to list successfully.');
    }

    public function removeContact(MarketingContactList $list, MarketingContact $contact)
    {
        $list->contacts()->detach($contact->id);

        return redirect()->route('admin.marketing.lists.show', $list)
            ->with('success', 'Contact removed from list.');
    }
}
