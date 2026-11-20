@extends('layouts.marketing')

@section('title', 'Contacts Directory')
@section('breadcrumb', 'Audience')
@section('page_title', 'Contacts Directory & Consent Master')

@section('content')

<!-- ── Top Banner Summary ── -->
<div class="flat-card mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <div class="text-uppercase fw-bold text-muted" style="font-size: 0.72rem; letter-spacing: 0.08em;">
                TOTAL VERIFIED AUDIENCE
            </div>
            <div class="d-flex align-items-baseline gap-2 mt-1">
                <span class="fw-bold" style="font-size: 2.1rem; color: #0f172a; letter-spacing: -0.5px;">
                    {{ number_format($contacts->total()) }}
                </span>
                <span class="text-muted" style="font-size: 0.9rem; font-weight: 500;">Permitted Opt-in Contacts</span>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.marketing.contacts.import') }}" class="btn-header-purple" style="padding: 9px 20px;">
                <i class="fa-solid fa-file-import"></i> Bulk CSV Import
            </a>

            <a href="{{ route('admin.marketing.contacts.create') }}" class="btn-header-dark" style="padding: 9px 20px;">
                <i class="fa-solid fa-plus"></i> New Contact
            </a>
        </div>
    </div>
</div>

<!-- ── Clean Filters Bar ── -->
<div class="flat-card p-3 mb-4">
    <form method="GET" action="{{ route('admin.marketing.contacts.index') }}" class="row g-2 align-items-center">
        <div class="col-md-3">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white border-end-0 text-muted" style="border-radius: 8px 0 0 8px; border-color: #e2e8f0;"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="text" name="search" value="{{ $search }}" class="form-control border-start-0" style="border-radius: 0 8px 8px 0; border-color: #e2e8f0; font-size: 0.82rem;" placeholder="Search by name, email, or company...">
            </div>
        </div>
        <div class="col-md-2">
            <select name="list_id" class="form-select form-select-sm" onchange="this.form.submit()" style="border-radius: 8px; border-color: #e2e8f0; font-size: 0.82rem;">
                <option value="">All Contact Lists</option>
                @foreach($lists as $lst)
                    <option value="{{ $lst->id }}" {{ (string)$listId === (string)$lst->id ? 'selected' : '' }}>📁 {{ $lst->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="consent_status" class="form-select form-select-sm" onchange="this.form.submit()" style="border-radius: 8px; border-color: #e2e8f0; font-size: 0.82rem;">
                <option value="">All Consent States</option>
                <option value="granted" {{ $consent === 'granted' ? 'selected' : '' }}>Consent: Granted</option>
                <option value="pending" {{ $consent === 'pending' ? 'selected' : '' }}>Consent: Pending</option>
                <option value="revoked" {{ $consent === 'revoked' ? 'selected' : '' }}>Consent: Revoked</option>
            </select>
        </div>
        <div class="col-md-2">
            <select name="subscription_status" class="form-select form-select-sm" onchange="this.form.submit()" style="border-radius: 8px; border-color: #e2e8f0; font-size: 0.82rem;">
                <option value="">All Subscriptions</option>
                <option value="subscribed" {{ $subscription === 'subscribed' ? 'selected' : '' }}>Subscribed</option>
                <option value="unsubscribed" {{ $subscription === 'unsubscribed' ? 'selected' : '' }}>Unsubscribed</option>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-dark w-100" style="border-radius: 8px;">Filter</button>
            <a href="{{ route('admin.marketing.contacts.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius: 8px;">Reset</a>
        </div>
    </form>
</div>

<!-- ── Clean Contacts Table ── -->
<div class="clean-table-card">
    <div class="table-responsive">
        <table class="clean-table align-middle">
            <thead>
                <tr>
                    <th>CONTACT #</th>
                    <th>PROSPECT NAME</th>
                    <th>COMPANY & WEBSITE</th>
                    <th>CONSENT SOURCE</th>
                    <th>SUBSCRIPTION</th>
                    <th>ENGAGEMENT</th>
                    <th class="text-end">ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                @forelse($contacts as $contact)
                <tr>
                    <td class="fw-bold" style="color: #0f172a;">
                        CNT-{{ str_pad($contact->id, 4, '0', STR_PAD_LEFT) }}
                    </td>
                    <td>
                        <div class="fw-bold" style="color: #0f172a;">{{ $contact->name ?: 'N/A' }}</div>
                        <div class="text-primary small">{{ $contact->email }}</div>
                        @if($contact->lists->isNotEmpty())
                            <div class="mt-1 d-flex flex-wrap gap-1">
                                @foreach($contact->lists as $cl)
                                    <span class="badge bg-light text-secondary border" style="font-size: 0.68rem; font-weight: 500;">
                                        <i class="fa-solid fa-folder me-1 text-primary"></i>{{ $cl->name }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </td>
                    <td>
                        <div class="fw-semibold" style="color: #0f172a;">{{ $contact->company ?: '—' }}</div>
                        @if($contact->website)
                            <a href="{{ $contact->website }}" target="_blank" class="small text-muted text-decoration-none">
                                <i class="fa-solid fa-arrow-up-right-from-square me-1"></i>{{ Str::limit($contact->website, 22) }}
                            </a>
                        @endif
                        @if($contact->business_description)
                            <div class="small text-muted mt-1" style="font-size: 0.72rem;" title="{{ $contact->business_description }}">
                                <i class="fa-solid fa-circle-info text-info me-1"></i>{{ Str::limit($contact->business_description, 28) }}
                            </div>
                        @endif
                    </td>
                    <td>
                        <span class="pill-badge" style="background-color: #ecfdf5; color: #059669; border: 1px solid #a7f3d0;">
                            {{ ucfirst($contact->consent_status) }}
                        </span>
                        <div class="small text-muted" style="font-size: 0.72rem; margin-top: 2px;">{{ $contact->consent_source ?: 'Direct' }}</div>
                    </td>
                    <td>
                        <span class="pill-badge" style="{{ $contact->subscription_status === 'subscribed' ? 'background-color: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe;' : 'background-color: #fef2f2; color: #dc2626; border: 1px solid #fecaca;' }}">
                            {{ ucfirst($contact->subscription_status) }}
                        </span>
                    </td>
                    <td class="small text-muted" style="font-size: 0.78rem;">
                        <div>Sent: <strong>{{ $contact->emails_sent_count }}</strong></div>
                        <small>Opened: {{ $contact->emails_opened_count }}</small>
                    </td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('admin.marketing.contacts.edit', $contact) }}" class="voucher-pill">
                            Edit
                        </a>
                        <form method="POST" action="{{ route('admin.marketing.contacts.destroy', $contact) }}" class="d-inline" onsubmit="return confirm('Remove contact?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm" style="background-color: #fef2f2; color: #dc2626; border: 1px solid #fecaca; border-radius: 6px; padding: 3px 8px;">
                                <i class="fa-solid fa-trash" style="font-size: 0.7rem;"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-5 text-muted">
                        No contacts found.
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

@endsection
