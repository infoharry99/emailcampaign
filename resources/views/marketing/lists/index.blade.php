@extends('layouts.marketing')

@section('title', 'Contact Lists & Segments')
@section('breadcrumb', 'Audience')
@section('page_title', 'Contact Lists & Segments')

@section('content')
<!-- ── Top Action & Summary ── -->
<div class="flat-card mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <div class="text-uppercase fw-bold text-muted" style="font-size: 0.72rem; letter-spacing: 0.08em;">
                MANAGED AUDIENCE LISTS
            </div>
            <div class="d-flex align-items-baseline gap-2 mt-1">
                <span class="fw-bold" style="font-size: 2.1rem; color: #0f172a; letter-spacing: -0.5px;">
                    {{ number_format($lists->total()) }}
                </span>
                <span class="text-muted" style="font-size: 0.9rem; font-weight: 500;">Custom Audience Lists ({{ number_format($totalContacts) }} Total Leads)</span>
            </div>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('admin.marketing.lists.create') }}" class="btn btn-brand">
                <i class="fa-solid fa-plus me-1"></i> New List
            </a>
            <a href="{{ route('admin.marketing.contacts.import') }}" class="btn btn-outline-primary">
                <i class="fa-solid fa-file-import me-1"></i> Import CSV to List
            </a>
        </div>
    </div>
</div>

<!-- ── Lists Table Card ── -->
<div class="clean-table-card">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center" style="background-color: #ffffff;">
        <span class="fw-bold" style="color: #0f172a; font-size: 0.9rem;">
            <i class="fa-solid fa-list-check me-2 text-primary"></i> All Contact Lists
        </span>
    </div>

    <div class="table-responsive">
        <table class="clean-table align-middle">
            <thead>
                <tr>
                    <th>LIST NAME</th>
                    <th>DESCRIPTION</th>
                    <th>TOTAL CONTACTS</th>
                    <th>ACTIVE / SUBSCRIBED</th>
                    <th>CREATED AT</th>
                    <th class="text-end">ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                @forelse($lists as $list)
                <tr>
                    <td>
                        <a href="{{ route('admin.marketing.lists.show', $list) }}" class="fw-bold text-decoration-none" style="color: #0f172a; font-size: 0.95rem;">
                            <i class="fa-solid fa-folder-closed me-2 text-primary"></i>{{ $list->name }}
                        </a>
                    </td>
                    <td class="text-muted small">
                        {{ $list->description ?: 'No description provided.' }}
                    </td>
                    <td>
                        <span class="badge bg-light text-dark border px-2 py-1">
                            <i class="fa-solid fa-users me-1 text-muted"></i>{{ number_format($list->contacts_count) }} Leads
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                            <i class="fa-solid fa-check me-1"></i>{{ number_format($list->activeContactsCount()) }} Eligible
                        </span>
                    </td>
                    <td class="text-muted small">
                        {{ $list->created_at->format('d M Y, H:i') }}
                    </td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('admin.marketing.lists.show', $list) }}" class="voucher-pill me-1" title="View Members">
                            <i class="fa-solid fa-eye me-1"></i> View
                        </a>
                        <a href="{{ route('admin.marketing.lists.edit', $list) }}" class="btn btn-sm" style="background-color: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; border-radius: 6px; padding: 3px 8px;" title="Edit List">
                            <i class="fa-solid fa-pen-to-square" style="font-size: 0.75rem;"></i>
                        </a>
                        <form method="POST" action="{{ route('admin.marketing.lists.destroy', $list) }}" class="d-inline" onsubmit="return confirm('Delete this list? Contacts will remain safe in master list.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm" style="background-color: #fef2f2; color: #ef4444; border: 1px solid #fecaca; border-radius: 6px; padding: 3px 8px;" title="Delete List">
                                <i class="fa-solid fa-trash" style="font-size: 0.75rem;"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        <i class="fa-solid fa-folder-open fs-2 mb-2 d-block"></i>
                        No contact lists created yet. Click <strong>New List</strong> to segment your audience (e.g. "New Leads", "Follow-up List", "CEOs").
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($lists->hasPages())
        <div class="p-3 border-top" style="background-color: #ffffff;">
            {{ $lists->links() }}
        </div>
    @endif
</div>
@endsection
