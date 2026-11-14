@extends('layouts.marketing')

@section('title', 'Marketing Dashboard')
@section('breadcrumb', 'Overview')
@section('page_title', 'Marketing Intelligence & Settlements')

@section('content')

<!-- ── Top Banner Summary (Matching image style) ── -->
<div class="flat-card mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <div class="text-uppercase fw-bold text-muted" style="font-size: 0.72rem; letter-spacing: 0.08em;">
                TOTAL AUDIENCE REACH & DISPATCHED
            </div>
            <div class="d-flex align-items-baseline gap-2 mt-1">
                <span class="fw-bold" style="font-size: 2.1rem; color: #0f172a; letter-spacing: -0.5px;">
                    {{ number_format($emailsSent) }}
                </span>
                <span class="text-muted" style="font-size: 0.9rem; font-weight: 500;">Emails Delivered via AWS SES</span>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <!-- Filter Dropdown -->
            <form method="GET" action="{{ route('admin.marketing.dashboard') }}" class="d-flex gap-2">
                <select name="campaign_id" class="form-select form-select-sm" onchange="this.form.submit()" style="border-radius: 8px; border-color: #e2e8f0; font-size: 0.8rem;">
                    <option value="">All Campaigns</option>
                    @foreach($campaigns as $camp)
                        <option value="{{ $camp->id }}" {{ $campaignId == $camp->id ? 'selected' : '' }}>{{ $camp->name }}</option>
                    @endforeach
                </select>
            </form>

            <a href="{{ route('admin.marketing.campaigns.create') }}" class="btn-header-purple" style="padding: 9px 20px;">
                <i class="fa-solid fa-layer-group"></i> Run Bulk Send
            </a>

            <a href="{{ route('admin.marketing.campaigns.create') }}" class="btn-header-dark" style="padding: 9px 20px;">
                <i class="fa-solid fa-plus"></i> New Campaign
            </a>
        </div>
    </div>
</div>

<!-- ── Metrics Stat Row (Crisp White Bordered Cards) ── -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="flat-card h-100 p-3">
            <div class="text-uppercase fw-bold text-muted" style="font-size: 0.7rem;">Active Subscribed Contacts</div>
            <div class="d-flex justify-content-between align-items-end mt-2">
                <div class="fw-bold" style="font-size: 1.6rem; color: #0f172a;">{{ number_format($totalSubscribed) }}</div>
                <div class="pill-badge" style="background-color: #ecfdf5; color: #059669; border: 1px solid #a7f3d0;">
                    <i class="fa-solid fa-shield-check me-1"></i> Permitted
                </div>
            </div>
            <div class="text-muted" style="font-size: 0.75rem; margin-top: 4px;">Out of {{ number_format($totalContacts) }} total leads</div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="flat-card h-100 p-3">
            <div class="text-uppercase fw-bold text-muted" style="font-size: 0.7rem;">Delivered Rate (SES)</div>
            <div class="d-flex justify-content-between align-items-end mt-2">
                <div class="fw-bold" style="font-size: 1.6rem; color: #0f172a;">{{ $openRate }}%</div>
                <div class="pill-badge" style="background-color: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe;">
                    Open Rate
                </div>
            </div>
            <div class="text-muted" style="font-size: 0.75rem; margin-top: 4px;">{{ $clickRate }}% Click Rate</div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="flat-card h-100 p-3">
            <div class="text-uppercase fw-bold text-muted" style="font-size: 0.7rem;">Bounce & Spam Protection</div>
            <div class="d-flex justify-content-between align-items-end mt-2">
                <div class="fw-bold" style="font-size: 1.6rem; color: {{ $bounceRate > 5 ? '#dc2626' : '#059669' }};">{{ $bounceRate }}%</div>
                <div class="pill-badge" style="background-color: #f8fafc; color: #64748b; border: 1px solid #e2e8f0;">
                    SES Health
                </div>
            </div>
            <div class="text-muted" style="font-size: 0.75rem; margin-top: 4px;">{{ $complaintRate }}% complaints (Safe)</div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="flat-card h-100 p-3">
            <div class="text-uppercase fw-bold text-muted" style="font-size: 0.7rem;">OpenAI API Cost</div>
            <div class="d-flex justify-content-between align-items-end mt-2">
                <div class="fw-bold" style="font-size: 1.6rem; color: #0f172a;">${{ number_format($openaiCost, 4) }}</div>
                <div class="pill-badge" style="background-color: #eef2ff; color: #4f46e5; border: 1px solid #c7d2fe;">
                    GPT-4o Mini
                </div>
            </div>
            <div class="text-muted" style="font-size: 0.75rem; margin-top: 4px;">{{ number_format($openaiTokens) }} tokens consumed</div>
        </div>
    </div>
</div>

<!-- ── Recent Dispatches Clean Table ── -->
<div class="clean-table-card">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center" style="background-color: #ffffff;">
        <span class="fw-bold" style="color: #0f172a; font-size: 0.9rem;">
            <i class="fa-solid fa-list-check me-2 text-primary"></i> Recent Dispatches & Event Activity
        </span>
        <a href="{{ route('admin.marketing.logs.index') }}" class="voucher-pill">
            View All Logs
        </a>
    </div>

    <div class="table-responsive">
        <table class="clean-table align-middle">
            <thead>
                <tr>
                    <th>EMAIL #</th>
                    <th>RECIPIENT & COMPANY</th>
                    <th>CAMPAIGN</th>
                    <th>SUBJECT LINE</th>
                    <th>STATUS</th>
                    <th>SES MESSAGE ID</th>
                    <th class="text-end">RECEIPT / VOUCHER</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentEmails as $email)
                <tr>
                    <td class="fw-bold" style="color: #0f172a;">
                        EML-{{ str_pad($email->id, 4, '0', STR_PAD_LEFT) }}
                    </td>
                    <td>
                        <div class="fw-bold" style="color: #0f172a;">{{ $email->recipient_email }}</div>
                        <small class="text-muted" style="font-size: 0.73rem;">{{ $email->recipient_name }} @if($email->contact?->company) ({{ $email->contact->company }}) @endif</small>
                    </td>
                    <td>
                        <span class="pill-badge" style="background-color: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;">
                            {{ $email->campaign->name ?? 'General' }}
                        </span>
                    </td>
                    <td>{{ Str::limit($email->subject, 32) }}</td>
                    <td>
                        @php
                            $statusStyle = match($email->status) {
                                'delivered' => 'background-color: #ecfdf5; color: #059669; border: 1px solid #a7f3d0;',
                                'opened' => 'background-color: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe;',
                                'clicked' => 'background-color: #eef2ff; color: #4f46e5; border: 1px solid #c7d2fe;',
                                'sent' => 'background-color: #f8fafc; color: #475569; border: 1px solid #e2e8f0;',
                                'bounced', 'failed' => 'background-color: #fef2f2; color: #dc2626; border: 1px solid #fecaca;',
                                default => 'background-color: #fffbeb; color: #d97706; border: 1px solid #fde68a;'
                            };
                        @endphp
                        <span class="pill-badge" style="{{ $statusStyle }}">
                            {{ ucfirst($email->status) }}
                        </span>
                    </td>
                    <td>
                        <code style="font-size: 0.75rem; color: #64748b;">{{ Str::limit($email->ses_message_id, 20) ?: 'Pending' }}</code>
                    </td>
                    <td class="text-end">
                        <a href="{{ route('admin.marketing.logs.show', $email) }}" class="voucher-pill">
                            Voucher
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-5 text-muted">
                        No dispatches recorded yet.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
