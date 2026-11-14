@extends('layouts.marketing')

@section('title', 'Campaigns & Settlements')
@section('breadcrumb', 'Campaigns')
@section('page_title', 'Email Campaigns & Broadcasts')

@section('content')

<!-- ── Top Big Banner Card (Matching image 'TOTAL SETTLEMENTS PAID' style) ── -->
<div class="flat-card mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <div class="text-uppercase fw-bold text-muted" style="font-size: 0.72rem; letter-spacing: 0.08em;">
                TOTAL EMAILS DISPATCHED (AWS SES)
            </div>
            <div class="d-flex align-items-baseline gap-2 mt-1">
                <span class="fw-bold" style="font-size: 2.1rem; color: #0f172a; letter-spacing: -0.5px;">
                    {{ number_format($campaigns->sum('sent_count')) }}
                </span>
                <span class="text-muted" style="font-size: 0.9rem; font-weight: 500;">Delivered</span>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <!-- Purple button -->
            <a href="{{ route('admin.marketing.campaigns.create') }}" class="btn-header-purple" style="padding: 9px 20px;">
                <i class="fa-solid fa-layer-group"></i> Run Bulk Campaign
            </a>

            <!-- Dark Navy button -->
            <a href="{{ route('admin.marketing.campaigns.create') }}" class="btn-header-dark" style="padding: 9px 20px;">
                <i class="fa-solid fa-plus"></i> New Campaign
            </a>
        </div>
    </div>
</div>

<!-- ── Clean White Data Table Card ── -->
<div class="clean-table-card">
    <div class="table-responsive">
        <table class="clean-table align-middle">
            <thead>
                <tr>
                    <th>CAMPAIGN # <i class="fa-solid fa-sort ms-1 text-muted" style="font-size: 0.65rem;"></i></th>
                    <th>CAMPAIGN NAME <i class="fa-solid fa-sort ms-1 text-muted" style="font-size: 0.65rem;"></i></th>
                    <th>SENDING PERIOD <i class="fa-solid fa-sort ms-1 text-muted" style="font-size: 0.65rem;"></i></th>
                    <th>TOTAL RECIPIENTS <i class="fa-solid fa-sort ms-1 text-muted" style="font-size: 0.65rem;"></i></th>
                    <th>QUEUED / MODE <i class="fa-solid fa-sort ms-1 text-muted" style="font-size: 0.65rem;"></i></th>
                    <th>STATUS <i class="fa-solid fa-sort ms-1 text-muted" style="font-size: 0.65rem;"></i></th>
                    <th>DELIVERED <i class="fa-solid fa-sort ms-1 text-muted" style="font-size: 0.65rem;"></i></th>
                    <th>ENGAGEMENT <i class="fa-solid fa-sort ms-1 text-muted" style="font-size: 0.65rem;"></i></th>
                    <th class="text-end">ACTIONS <i class="fa-solid fa-sort ms-1 text-muted" style="font-size: 0.65rem;"></i></th>
                </tr>
            </thead>
            <tbody>
                @forelse($campaigns as $camp)
                <tr>
                    <!-- Campaign ID -->
                    <td class="fw-bold" style="color: #0f172a; font-size: 0.85rem;">
                        CAMP-{{ str_pad($camp->id, 3, '0', STR_PAD_LEFT) }}
                    </td>

                    <!-- Campaign Name & Objective -->
                    <td>
                        <div class="fw-bold" style="color: #0f172a;">{{ $camp->name }}</div>
                        <small class="text-muted" style="font-size: 0.73rem;">{{ Str::limit($camp->purpose ?: $camp->description, 35) }}</small>
                    </td>

                    <!-- Billing/Sending Period -->
                    <td style="font-size: 0.82rem; color: #475569;">
                        <div>{{ $camp->created_at->format('d M') }} to {{ $camp->scheduled_at ? $camp->scheduled_at->format('d M Y') : now()->format('d M Y') }}</div>
                        <small class="text-muted" style="font-size: 0.72rem;">{{ $camp->created_at->format('Y') }}</small>
                    </td>

                    <!-- Total Volume -->
                    <td class="fw-semibold" style="color: #0f172a;">
                        {{ number_format($camp->total_recipients) }} Leads
                    </td>

                    <!-- Queued / Mode -->
                    <td>
                        <span class="pill-badge" style="background-color: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;">
                            {{ strtoupper($camp->sending_mode) }}
                        </span>
                        <div class="text-muted" style="font-size: 0.72rem; margin-top: 2px;">{{ number_format($camp->queued_count) }} in queue</div>
                    </td>

                    <!-- Status -->
                    <td>
                        @php
                            $statusStyle = match($camp->status) {
                                'running' => 'background-color: #ecfdf5; color: #059669; border: 1px solid #a7f3d0;',
                                'completed' => 'background-color: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe;',
                                'paused' => 'background-color: #fffbeb; color: #d97706; border: 1px solid #fde68a;',
                                default => 'background-color: #f8fafc; color: #64748b; border: 1px solid #e2e8f0;'
                            };
                        @endphp
                        <span class="pill-badge" style="{{ $statusStyle }}">
                            {{ ucfirst($camp->status) }}
                        </span>
                    </td>

                    <!-- Net Delivered Count -->
                    <td class="fw-bold" style="color: #0f172a; font-size: 0.92rem;">
                        {{ number_format($camp->delivered_count) }}
                    </td>

                    <!-- Engagement (Opens / Clicks) -->
                    <td style="font-size: 0.8rem;">
                        <div>Opens: <span class="fw-bold text-primary">{{ $camp->opened_count }}</span></div>
                        <small class="text-muted">Clicks: {{ $camp->clicked_count }}</small>
                    </td>

                    <!-- Action Voucher Button Style -->
                    <td class="text-end text-nowrap">
                        <a href="{{ route('admin.marketing.campaigns.show', $camp) }}" class="voucher-pill">
                            Report
                        </a>

                        @if($camp->status === 'draft' || $camp->status === 'paused')
                        <form method="POST" action="{{ route('admin.marketing.campaigns.start', $camp) }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm" style="background-color: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; border-radius: 6px; padding: 3px 8px;" title="Start Dispatch">
                                <i class="fa-solid fa-play" style="font-size: 0.7rem;"></i>
                            </button>
                        </form>
                        @endif

                        @if($camp->status === 'running')
                        <form method="POST" action="{{ route('admin.marketing.campaigns.pause', $camp) }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm" style="background-color: #fffbeb; color: #d97706; border: 1px solid #fde68a; border-radius: 6px; padding: 3px 8px;" title="Pause Dispatch">
                                <i class="fa-solid fa-pause" style="font-size: 0.7rem;"></i>
                            </button>
                        </form>
                        @endif

                        <form method="POST" action="{{ route('admin.marketing.campaigns.duplicate', $camp) }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm" style="background-color: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; border-radius: 6px; padding: 3px 8px;" title="Duplicate">
                                <i class="fa-solid fa-copy" style="font-size: 0.7rem;"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center py-5 text-muted">
                        <i class="fa-solid fa-bullhorn fs-2 mb-2 d-block"></i> No campaigns found. Click <strong>New Campaign</strong> to launch your first dispatch.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($campaigns->hasPages())
        <div class="p-3 border-top" style="background-color: #ffffff;">
            {{ $campaigns->links() }}
        </div>
    @endif
</div>
@endsection
