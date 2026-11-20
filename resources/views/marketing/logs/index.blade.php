@extends('layouts.marketing')

@section('title', 'Email Dispatch Logs')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1">Email Dispatch Logs</h3>
        <p class="text-muted small mb-0">Audit history of every marketing email dispatched, queue retry states, and SES confirmations.</p>
    </div>
</div>

<!-- Filter Bar -->
<div class="card p-3 mb-4">
    <form method="GET" action="{{ route('admin.marketing.logs.index') }}" class="row g-2 align-items-center">
        <div class="col-md-5">
            <div class="input-group input-group-sm">
                <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Search by recipient email, subject, or SES ID...">
            </div>
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="sent" {{ $status === 'sent' ? 'selected' : '' }}>Sent</option>
                <option value="delivered" {{ $status === 'delivered' ? 'selected' : '' }}>Delivered</option>
                <option value="opened" {{ $status === 'opened' ? 'selected' : '' }}>Opened</option>
                <option value="clicked" {{ $status === 'clicked' ? 'selected' : '' }}>Clicked</option>
                <option value="bounced" {{ $status === 'bounced' ? 'selected' : '' }}>Bounced</option>
                <option value="failed" {{ $status === 'failed' ? 'selected' : '' }}>Failed</option>
                <option value="suppressed" {{ $status === 'suppressed' ? 'selected' : '' }}>Suppressed</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
        </div>
        <div class="col-md-2">
            <a href="{{ route('admin.marketing.logs.index') }}" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
        </div>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Recipient</th>
                    <th>Campaign</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>SES Message ID</th>
                    <th>Retries</th>
                    <th>Timestamp</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($emails as $email)
                <tr>
                    <td>
                        <a href="{{ route('admin.marketing.logs.show', $email) }}" class="fw-bold text-decoration-none text-dark">
                            {{ $email->recipient_email }}
                        </a>
                        <div class="text-muted small">
                            {{ $email->recipient_name }}
                            @if($email->contact?->company)
                                <span class="badge bg-light text-muted border ms-1">{{ $email->contact->company }}</span>
                            @endif
                        </div>
                    </td>
                    <td>
                        <span class="badge bg-light text-dark border">{{ $email->campaign->name ?? 'Direct' }}</span>
                        @if($email->is_ai_generated)
                            <div><span class="badge bg-purple-subtle text-purple border" style="background-color: #f3e8ff; color: #6b21a8; font-size: 0.7rem;"><i class="fa-solid fa-wand-magic-sparkles me-1"></i> AI</span></div>
                        @endif
                    </td>
                    <td>
                        <div class="fw-semibold text-dark" title="{{ $email->subject }}">{{ Str::limit($email->subject, 40) }}</div>
                        @if($email->ai_rationale)
                            <div class="text-muted small fst-italic" style="font-size: 0.72rem; max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $email->ai_rationale }}">
                                {{ $email->ai_rationale }}
                            </div>
                        @endif
                    </td>
                    <td>
                        @php
                            $badge = match($email->status) {
                                'delivered' => 'bg-success',
                                'opened' => 'bg-info',
                                'clicked' => 'bg-primary',
                                'sent' => 'bg-secondary',
                                'bounced', 'failed' => 'bg-danger',
                                'suppressed' => 'bg-dark',
                                default => 'bg-warning text-dark'
                            };
                        @endphp
                        <span class="badge {{ $badge }}">{{ ucfirst($email->status) }}</span>
                        @if($email->status === 'failed' || $email->status === 'bounced')
                            <div class="text-danger small mt-1" style="font-size: 0.72rem; max-width: 260px;" title="{{ $email->error_message }}">
                                <i class="fa-solid fa-triangle-exclamation me-1"></i> {{ Str::limit($email->error_message ?: 'SES Error', 50) }}
                            </div>
                        @endif
                    </td>
                    <td><code class="small text-muted">{{ Str::limit($email->ses_message_id, 24) ?: '—' }}</code></td>
                    <td><span class="badge bg-light text-dark border">{{ $email->retry_count }}</span></td>
                    <td class="text-muted small">{{ $email->created_at->format('M d, H:i') }}</td>
                    <td class="text-end">
                        <a href="{{ route('admin.marketing.logs.show', $email) }}" class="btn btn-xs btn-outline-primary py-1 px-2" style="font-size: 0.78rem;" title="View exact email sent to this contact">
                            <i class="fa-regular fa-eye me-1"></i> View Email
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">No email logs matching criteria.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($emails->hasPages())
        <div class="card-footer bg-white">
            {{ $emails->links() }}
        </div>
    @endif
</div>
@endsection
