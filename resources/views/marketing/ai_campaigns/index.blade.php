@extends('layouts.marketing')

@section('title', 'AI Personalized Email Campaigns')
@section('breadcrumb', 'Campaigns / AI Personalized')
@section('page_title', 'AI Personalized Email Campaigns')

@section('content')
<!-- ── Top Overview Banner ── -->
<div class="flat-card mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <div class="d-flex align-items-center gap-2">
                <div class="badge bg-primary text-white p-2 rounded-circle" style="width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;">
                    <i class="fa-solid fa-robot fs-5"></i>
                </div>
                <div>
                    <h4 class="fw-bold mb-0 text-dark">AI-Powered 1-on-1 Personalized Campaigns</h4>
                    <p class="text-muted small mb-0">Every contact receives a unique, customized B2B email tailored to their specific company and industry niche.</p>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('admin.marketing.ai-campaigns.create') }}" class="btn btn-brand">
                <i class="fa-solid fa-wand-magic-sparkles me-1"></i> New AI Personalized Campaign
            </a>
            <a href="{{ route('admin.marketing.campaigns.create') }}" class="btn btn-outline-secondary">
                <i class="fa-solid fa-bullhorn me-1"></i> Standard Campaign
            </a>
        </div>
    </div>
</div>

<!-- ── Campaigns List Table ── -->
<div class="clean-table-card">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center" style="background-color: #ffffff;">
        <span class="fw-bold" style="color: #0f172a; font-size: 0.92rem;">
            <i class="fa-solid fa-robot text-primary me-2"></i> All AI Personalized Campaigns ({{ $campaigns->total() }})
        </span>
        <span class="text-muted small">Unique AI subjects & bodies generated per prospect</span>
    </div>

    <div class="table-responsive">
        <table class="clean-table align-middle">
            <thead>
                <tr>
                    <th>CAMPAIGN #</th>
                    <th>CAMPAIGN NAME & TOPIC</th>
                    <th>TARGET AUDIENCE</th>
                    <th>AI GENERATED</th>
                    <th>STATUS</th>
                    <th>DISPATCHED</th>
                    <th>OPENS</th>
                    <th class="text-end">ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                @forelse($campaigns as $camp)
                <tr>
                    <td class="fw-bold" style="color: #0f172a;">
                        AI-{{ str_pad($camp->id, 3, '0', STR_PAD_LEFT) }}
                    </td>
                    <td>
                        <a href="{{ route('admin.marketing.ai-campaigns.show', $camp) }}" class="fw-bold text-dark text-decoration-none d-block">
                            {{ $camp->name }}
                        </a>
                        <small class="text-muted" style="font-size: 0.78rem;">
                            <i class="fa-solid fa-heading text-secondary me-1"></i>{{ Str::limit($camp->subject, 45) }}
                        </small>
                    </td>
                    <td>
                        <div class="fw-semibold">{{ number_format($camp->total_recipients) }} Leads</div>
                        <small class="text-muted" style="font-size: 0.72rem;">
                            {{ $camp->contact_list_ids ? count($camp->contact_list_ids) . ' lists selected' : 'All contacts' }}
                        </small>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="fw-bold text-primary">{{ number_format($camp->generated_count) }}</span>
                            <span class="text-muted small">/ {{ number_format($camp->total_recipients) }}</span>
                        </div>
                        @php
                            $pct = $camp->total_recipients > 0 ? min(100, round(($camp->generated_count / $camp->total_recipients) * 100)) : 0;
                        @endphp
                        <div class="progress mt-1" style="height: 4px; width: 85px;">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $pct }}%"></div>
                        </div>
                    </td>
                    <td>
                        @php
                            $statusBadge = match($camp->status) {
                                'running' => 'background-color: #ecfdf5; color: #059669; border: 1px solid #a7f3d0;',
                                'completed' => 'background-color: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe;',
                                'paused' => 'background-color: #fffbeb; color: #d97706; border: 1px solid #fde68a;',
                                default => 'background-color: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;'
                            };
                        @endphp
                        <span class="pill-badge" style="{{ $statusBadge }}">
                            {{ ucfirst($camp->status) }}
                        </span>
                    </td>
                    <td class="fw-bold" style="color: #0f172a;">
                        {{ number_format($camp->delivered_count) }}
                    </td>
                    <td>
                        <div>Opens: <span class="fw-bold text-primary">{{ $camp->opened_count }}</span></div>
                        <small class="text-muted">{{ $camp->delivered_count > 0 ? round(($camp->opened_count / $camp->delivered_count) * 100, 1) : 0 }}% Rate</small>
                    </td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('admin.marketing.ai-campaigns.show', $camp) }}" class="btn btn-sm btn-dark" style="border-radius: 6px; padding: 3px 10px; font-size: 0.78rem;">
                            <i class="fa-solid fa-eye me-1"></i> Review & Send
                        </a>

                        <form method="POST" action="{{ route('admin.marketing.ai-campaigns.destroy', $camp) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this AI campaign?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm" style="background-color: #fef2f2; color: #ef4444; border: 1px solid #fecaca; border-radius: 6px; padding: 3px 8px;" title="Delete">
                                <i class="fa-solid fa-trash" style="font-size: 0.7rem;"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                        <i class="fa-solid fa-robot fs-1 text-secondary mb-3 d-block"></i>
                        <h5>No AI Personalized Campaigns yet</h5>
                        <p class="small text-muted mb-3">Create a campaign where AI analyzes each company individually and generates bespoke outreach emails.</p>
                        <a href="{{ route('admin.marketing.ai-campaigns.create') }}" class="btn btn-brand">
                            <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Create First AI Personalized Campaign
                        </a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($campaigns->hasPages())
    <div class="p-3 border-top">
        {{ $campaigns->links() }}
    </div>
    @endif
</div>
@endsection
