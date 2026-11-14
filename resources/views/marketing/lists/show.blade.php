@extends('layouts.marketing')

@section('title', 'List: ' . $list->name)
@section('breadcrumb', 'Audience / Lists')
@section('page_title', $list->name)

@section('content')
<!-- ── Top List Banner ── -->
<div class="flat-card mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('admin.marketing.lists.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <h4 class="fw-bold mb-0 text-dark">{{ $list->name }}</h4>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2">
                    {{ number_format($contacts->total()) }} Contacts
                </span>
            </div>
            <p class="text-muted small mt-2 mb-0">
                {{ $list->description ?: 'Custom target audience list for dedicated campaign dispatches.' }}
            </p>
        </div>

        <div class="d-flex gap-2">
            <!-- Launch Campaign With This List -->
            <a href="{{ route('admin.marketing.campaigns.create') }}?target_criteria=list:{{ $list->id }}" class="btn btn-brand">
                <i class="fa-solid fa-bullhorn me-1"></i> Send Campaign to this List
            </a>

            <!-- Add Contact Modal Trigger -->
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addContactModal">
                <i class="fa-solid fa-user-plus me-1"></i> Add Contact
            </button>

            <!-- Edit List -->
            <a href="{{ route('admin.marketing.lists.edit', $list) }}" class="btn btn-outline-secondary">
                <i class="fa-solid fa-pen-to-square"></i>
            </a>
        </div>
    </div>
</div>

<!-- ── Contacts Inside List Table ── -->
<div class="clean-table-card">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center" style="background-color: #ffffff;">
        <span class="fw-bold" style="color: #0f172a; font-size: 0.9rem;">
            <i class="fa-solid fa-users me-2 text-primary"></i> Contacts in {{ $list->name }} ({{ number_format($contacts->total()) }})
        </span>
    </div>

    <div class="table-responsive">
        <table class="clean-table align-middle">
            <thead>
                <tr>
                    <th>NAME & EMAIL</th>
                    <th>COMPANY</th>
                    <th>BUSINESS / INDUSTRY</th>
                    <th>SUBSCRIPTION</th>
                    <th>CONSENT</th>
                    <th class="text-end">ACTION</th>
                </tr>
            </thead>
            <tbody>
                @forelse($contacts as $contact)
                <tr>
                    <td>
                        <div class="fw-bold" style="color: #0f172a;">{{ $contact->name ?: 'Unnamed Lead' }}</div>
                        <div class="text-muted small">{{ $contact->email }}</div>
                    </td>
                    <td>
                        <div class="fw-semibold">{{ $contact->company ?: '—' }}</div>
                        @if($contact->website)
                            <a href="{{ $contact->website }}" target="_blank" class="text-muted small text-decoration-none">
                                <i class="fa-solid fa-globe me-1"></i>{{ Str::limit($contact->website, 25) }}
                            </a>
                        @endif
                    </td>
                    <td class="small text-muted">
                        {{ $contact->business_type ?: 'General' }}
                    </td>
                    <td>
                        <span class="pill-badge" style="background-color: #ecfdf5; color: #059669; border: 1px solid #a7f3d0;">
                            {{ ucfirst($contact->subscription_status) }}
                        </span>
                    </td>
                    <td>
                        <span class="pill-badge" style="background-color: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe;">
                            {{ ucfirst($contact->consent_status) }}
                        </span>
                    </td>
                    <td class="text-end">
                        <form method="POST" action="{{ route('admin.marketing.lists.remove-contact', [$list, $contact]) }}" class="d-inline" onsubmit="return confirm('Remove contact from this list? (Will still remain in master directory)');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm" style="background-color: #fef2f2; color: #ef4444; border: 1px solid #fecaca; border-radius: 6px; padding: 2px 8px;" title="Remove from list">
                                <i class="fa-solid fa-user-minus"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        <i class="fa-solid fa-user-slash fs-2 mb-2 d-block"></i>
                        No contacts in this list yet. Click <strong>Add Contact</strong> above or import a CSV to populate this list.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($contacts->hasPages())
        <div class="p-3 border-top" style="background-color: #ffffff;">
            {{ $contacts->links() }}
        </div>
    @endif
</div>

<!-- ── Add Contact Modal ── -->
<div class="modal fade" id="addContactModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.marketing.lists.add-contact', $list) }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-user-plus text-primary me-2"></i> Add Contact to {{ $list->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Existing Contact</label>
                        <select name="contact_id" class="form-select" required>
                            <option value="">-- Choose a contact --</option>
                            @foreach($availableContacts as $c)
                                <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->email }}) - {{ $c->company }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Showing up to 100 available subscribed contacts not currently in this list.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-brand">Add to List</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
