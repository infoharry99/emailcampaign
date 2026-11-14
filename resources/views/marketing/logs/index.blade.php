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
                </tr>
            </thead>
            <tbody>
                @forelse($emails as $email)
                <tr>
                    <td>
                        <div class="fw-bold">{{ $email->recipient_email }}</div>
                        <small class="text-muted">{{ $email->recipient_name }}</small>
                    </td>
                    <td>
                        <span class="badge bg-light text-dark border">{{ $email->campaign->name ?? 'Direct' }}</span>
                    </td>
                    <td>{{ Str::limit($email->subject, 35) }}</td>
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
                    </td>
                    <td><code class="small text-muted">{{ Str::limit($email->ses_message_id, 24) ?: '—' }}</code></td>
                    <td><span class="badge bg-light text-dark border">{{ $email->retry_count }}</span></td>
                    <td class="text-muted small">{{ $email->created_at->format('M d, H:i') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">No email logs matching criteria.</td>
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
