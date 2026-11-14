@extends('layouts.marketing')

@section('title', 'Email Dispatch Details #' . $email->id)
@section('breadcrumb', 'Dispatch Logs')
@section('page_title', 'Dispatch Log Voucher #' . $email->id)

@section('content')
<div class="mb-3">
    <a href="{{ route('admin.marketing.logs.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Logs
    </a>
</div>

<div class="row g-4">
    <div class="col-md-8">
        <div class="card p-4 mb-4">
            <h5 class="fw-bold border-bottom pb-2 mb-3 text-primary">
                <i class="fa-solid fa-envelope me-2"></i> Message Details
            </h5>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <span class="text-muted small">Recipient</span>
                    <div class="fw-bold">{{ $email->recipient_name }} &lt;{{ $email->recipient_email }}&gt;</div>
                </div>
                <div class="col-md-6">
                    <span class="text-muted small">Campaign</span>
                    <div>{{ $email->campaign?->name ?: 'Direct/Test' }}</div>
                </div>
                <div class="col-md-12">
                    <span class="text-muted small">Subject</span>
                    <div class="fw-semibold">{{ $email->subject }}</div>
                </div>
            </div>

            @if($email->error_message)
            <div class="alert alert-danger mb-3">
                <h6 class="fw-bold mb-1"><i class="fa-solid fa-circle-exclamation me-1"></i> Dispatch Failure Error:</h6>
                <div class="small font-monospace">{{ $email->error_message }}</div>
            </div>
            @endif

            <h6 class="fw-bold border-bottom pb-2 mt-3 mb-2 text-muted small text-uppercase">Email Body Preview</h6>
            <div class="border rounded p-3 bg-light" style="max-height: 400px; overflow-y: auto;">
                {!! $email->body_html !!}
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card p-4">
            <h5 class="fw-bold border-bottom pb-2 mb-3 text-primary">
                <i class="fa-solid fa-info-circle me-2"></i> Delivery Status
            </h5>
            <div class="mb-3">
                <span class="text-muted small d-block">Status</span>
                @php
                    $badge = match($email->status) {
                        'delivered', 'opened', 'clicked' => 'bg-success',
                        'sent' => 'bg-info',
                        'bounced', 'failed' => 'bg-danger',
                        default => 'bg-warning text-dark'
                    };
                @endphp
                <span class="badge {{ $badge }} fs-6">{{ ucfirst($email->status) }}</span>
            </div>

            <div class="mb-3">
                <span class="text-muted small d-block">SES Message ID</span>
                <code class="small text-break">{{ $email->ses_message_id ?: 'Not assigned' }}</code>
            </div>

            <div class="mb-3">
                <span class="text-muted small d-block">Retry Count</span>
                <span class="badge bg-light text-dark border">{{ $email->retry_count }} retries</span>
            </div>

            <div class="mb-3">
                <span class="text-muted small d-block">Dispatched At</span>
                <span class="small text-muted">{{ $email->created_at->format('M d, Y H:i:s') }}</span>
            </div>
        </div>
    </div>
</div>
@endsection
