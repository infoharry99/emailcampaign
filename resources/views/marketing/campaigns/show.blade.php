@extends('layouts.marketing')

@section('title', $campaign->name . ' - Reports & Performance')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h3 class="fw-bold mb-0">{{ $campaign->name }}</h3>
            @php
                $badge = match($campaign->status) {
                    'running' => 'bg-success',
                    'completed' => 'bg-primary',
                    'paused' => 'bg-warning text-dark',
                    'scheduled' => 'bg-info text-dark',
                    default => 'bg-light text-dark border'
                };
            @endphp
            <span class="badge {{ $badge }}">{{ ucfirst($campaign->status) }}</span>
            <span class="badge bg-secondary">{{ strtoupper($campaign->sending_mode) }} Mode</span>
        </div>
        <p class="text-muted small mb-0 mt-1">{{ $campaign->purpose }}</p>
    </div>

    <!-- Campaign Action Buttons -->
    <div class="d-flex gap-2">
        @if(in_array($campaign->status, ['draft', 'scheduled', 'paused', 'completed']))
        <form method="POST" action="{{ route('admin.marketing.campaigns.start', $campaign) }}" onsubmit="const btn = this.querySelector('button[type=submit]'); if(btn) { btn.disabled = true; btn.innerHTML = '<i class=\'fa-solid fa-spinner fa-spin me-1\'></i> Dispatching...'; }">
            @csrf
            <input type="hidden" name="mode" value="instant">
            <button type="submit" class="btn btn-success fw-bold">
                <i class="fa-solid fa-bolt me-1"></i> Send Instantly (Live SES)
            </button>
        </form>
        @if($campaign->status !== 'completed')
        <form method="POST" action="{{ route('admin.marketing.campaigns.start', $campaign) }}">
            @csrf
            <button type="submit" class="btn btn-outline-success">
                <i class="fa-solid fa-clock me-1"></i> Queue Background
            </button>
        </form>
        @endif
        @endif

        @if($campaign->status === 'running')
        <form method="POST" action="{{ route('admin.marketing.campaigns.start', $campaign) }}" onsubmit="const btn = this.querySelector('button[type=submit]'); if(btn) { btn.disabled = true; btn.innerHTML = '<i class=\'fa-solid fa-spinner fa-spin me-1\'></i> Dispatching...'; }">
            @csrf
            <input type="hidden" name="mode" value="instant">
            <button type="submit" class="btn btn-success fw-bold" title="Force immediate dispatch without waiting for queue worker">
                <i class="fa-solid fa-bolt me-1"></i> Dispatch Instantly Now
            </button>
        </form>
        <form method="POST" action="{{ route('admin.marketing.campaigns.pause', $campaign) }}">
            @csrf
            <button type="submit" class="btn btn-warning">
                <i class="fa-solid fa-pause me-1"></i> Pause Campaign
            </button>
        </form>
        @endif

        <!-- Edit Campaign Button -->
        <a href="{{ route('admin.marketing.campaigns.edit', $campaign) }}" class="btn btn-outline-secondary">
            <i class="fa-solid fa-pen-to-square me-1"></i> Edit
        </a>

        <!-- Test Send Modal Trigger -->
        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#testSendModal">
            <i class="fa-solid fa-paper-plane me-1"></i> Send Test Email
        </button>

        <form method="POST" action="{{ route('admin.marketing.campaigns.destroy', $campaign) }}" onsubmit="return confirm('Are you sure you want to delete this campaign?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger">
                <i class="fa-solid fa-trash me-1"></i> Delete
            </button>
        </form>
    </div>
</div>

<!-- Campaign KPI Summary -->
<div class="row g-3 mb-4">
    <div class="col-md-2">
        <div class="card p-3 text-center">
            <span class="text-muted small">Total Audience</span>
            <h4 class="fw-bold my-1">{{ number_format($campaign->total_recipients) }}</h4>
            <span class="badge bg-light text-dark border">Contacts</span>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card p-3 text-center">
            <span class="text-muted small">Generated</span>
            <h4 class="fw-bold my-1 text-info">{{ number_format($campaign->generated_count) }}</h4>
            <span class="small text-muted">AI / Template</span>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card p-3 text-center">
            <span class="text-muted small">Sent via SES</span>
            <h4 class="fw-bold my-1 text-primary">{{ number_format($campaign->sent_count) }}</h4>
            <span class="small text-muted">Dispatched</span>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card p-3 text-center">
            <span class="text-muted small">Delivered</span>
            <h4 class="fw-bold my-1 text-success">{{ number_format($campaign->delivered_count) }}</h4>
            <span class="small text-success">Confirmed</span>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card p-3 text-center">
            <span class="text-muted small">Unique Opens</span>
            <h4 class="fw-bold my-1 text-warning">{{ number_format($campaign->opened_count) }}</h4>
            <span class="small text-muted">{{ $campaign->delivered_count > 0 ? round(($campaign->opened_count / $campaign->delivered_count) * 100, 1) : 0 }}% Open Rate</span>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card p-3 text-center">
            <span class="text-muted small">Bounces / Failed</span>
            <h4 class="fw-bold my-1 text-danger">{{ number_format($campaign->bounced_count + $campaign->failed_count) }}</h4>
            <span class="small text-danger">{{ $campaign->complained_count }} Complaints</span>
        </div>
    </div>
</div>

@if(!empty($campaign->attachments))
<div class="card p-3 mb-4 shadow-sm" style="border-left: 4px solid #4f46e5;">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h6 class="fw-bold mb-1 text-dark">
                <i class="fa-solid fa-paperclip text-primary me-2"></i> Attached Campaign Files ({{ count($campaign->attachments) }})
            </h6>
            <p class="text-muted small mb-0">These files are attached to dispatches sent to every recipient via AWS SES.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @foreach($campaign->attachments as $att)
                <a href="{{ asset('storage/' . ($att['path'] ?? '')) }}" target="_blank" class="badge bg-white text-dark border p-2 text-decoration-none d-inline-flex align-items-center gap-2 shadow-sm" style="border-radius: 6px;">
                    <i class="fa-solid fa-file text-primary"></i>
                    <span class="fw-semibold">{{ $att['name'] ?? 'File' }}</span>
                    <span class="text-muted">({{ number_format(($att['size'] ?? 0) / 1024, 1) }} KB)</span>
                    <i class="fa-solid fa-download text-muted ms-1"></i>
                </a>
            @endforeach
        </div>
    </div>
</div>
@endif

<!-- Recipients & Status Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fa-solid fa-users me-2"></i> Targeted Recipients & Individual Status</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Recipient</th>
                    <th>Subject Line</th>
                    <th>Status</th>
                    <th>Mode</th>
                    <th>SES Message ID</th>
                    <th>Last Updated</th>
                </tr>
            </thead>
            <tbody>
                @forelse($emails as $email)
                <tr>
                    <td>
                        <div class="fw-bold">{{ $email->recipient_email }}</div>
                        <small class="text-muted">{{ $email->recipient_name }} @if($email->contact?->company) ({{ $email->contact->company }}) @endif</small>
                    </td>
                    <td>{{ Str::limit($email->subject, 38) }}</td>
                    <td>
                        @php
                            $badge = match($email->status) {
                                'delivered' => 'bg-success',
                                'opened' => 'bg-info',
                                'clicked' => 'bg-primary',
                                'sent' => 'bg-secondary',
                                'queued' => 'bg-warning text-dark',
                                'bounced', 'failed' => 'bg-danger',
                                'suppressed' => 'bg-dark',
                                default => 'bg-light text-dark border'
                            };
                        @endphp
                        <span class="badge {{ $badge }}">{{ ucfirst($email->status) }}</span>
                    </td>
                    <td>
                        <span class="badge bg-light text-dark border">{{ $email->is_ai_generated ? 'AI' : 'Template' }}</span>
                    </td>
                    <td><code class="small text-muted">{{ Str::limit($email->ses_message_id, 20) ?: '—' }}</code></td>
                    <td class="text-muted small">{{ $email->updated_at->diffForHumans() }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">
                        No individual dispatches generated yet. Click <strong>Start Sending</strong> to dispatch emails.
                    </td>
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

<!-- Test Send Modal -->
<div class="modal fade" id="testSendModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.marketing.campaigns.test-send', $campaign) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Send Test Email</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Dispatch a live test email rendered with this campaign's settings and AI prompt to your own address.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Destination Email</label>
                        <input type="email" name="test_email" class="form-control" value="{{ auth()->user()->email ?? 'admin@thenexteck.com' }}" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane me-1"></i> Send Test Now</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
