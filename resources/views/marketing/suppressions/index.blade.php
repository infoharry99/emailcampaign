@extends('layouts.marketing')

@section('title', 'Global Suppression List')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1"><i class="fa-solid fa-ban text-danger me-2"></i> Suppression Management</h3>
        <p class="text-muted small mb-0">Emails blocked from all future marketing sends due to hard bounces, spam complaints, or unsubscribes.</p>
    </div>

    <!-- Manual Suppression Modal Button -->
    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#addSuppressionModal">
        <i class="fa-solid fa-plus-circle me-1"></i> Add Email to Suppression
    </button>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Suppressed Email</th>
                    <th>Reason</th>
                    <th>Details / Subtype</th>
                    <th>Source</th>
                    <th>Suppressed Since</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($suppressions as $supp)
                <tr>
                    <td class="fw-bold text-danger">{{ $supp->email }}</td>
                    <td>
                        @php
                            $badge = match($supp->reason) {
                                'hard_bounce' => 'bg-danger',
                                'complaint' => 'bg-warning text-dark',
                                'unsubscribe' => 'bg-secondary',
                                default => 'bg-dark'
                            };
                        @endphp
                        <span class="badge {{ $badge }}">{{ strtoupper($supp->reason) }}</span>
                    </td>
                    <td class="small text-muted">{{ $supp->details ?: 'Automatic rule enforcement' }}</td>
                    <td><span class="badge bg-light text-dark border">{{ $supp->source }}</span></td>
                    <td class="text-muted small">{{ $supp->created_at->format('M d, Y H:i') }}</td>
                    <td class="text-end">
                        <form method="POST" action="{{ route('admin.marketing.suppressions.destroy', $supp) }}" onsubmit="return confirm('Remove suppression? Note: Only do this if the contact has re-confirmed written consent!')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="fa-solid fa-trash me-1"></i> Remove
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">
                        <i class="fa-solid fa-shield-heart fs-3 text-success mb-2 d-block"></i> No emails currently suppressed. Your sender reputation is clean.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($suppressions->hasPages())
        <div class="card-footer bg-white">
            {{ $suppressions->links() }}
        </div>
    @endif
</div>

<!-- Modal -->
<div class="modal fade" id="addSuppressionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.marketing.suppressions.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-danger"><i class="fa-solid fa-ban me-2"></i> Add Email to Suppression</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email Address</label>
                        <input type="email" name="email" class="form-control" required placeholder="contact@domain.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Reason</label>
                        <select name="reason" class="form-select" required>
                            <option value="hard_bounce">Hard Bounce (Undeliverable mailbox)</option>
                            <option value="complaint">Spam Complaint</option>
                            <option value="unsubscribe">User Opt-out / Unsubscribe</option>
                            <option value="manual">Manual Admin Blacklist</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Notes / Reason Details</label>
                        <input type="text" name="details" class="form-control" placeholder="Optional explanation...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Suppress Address</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
