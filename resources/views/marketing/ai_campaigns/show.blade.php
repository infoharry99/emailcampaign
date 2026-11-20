@extends('layouts.marketing')

@section('title', $campaign->name . ' - AI Personalized Review & Dispatch Hub')
@section('breadcrumb', 'Campaigns / AI Personalized / ' . $campaign->name)
@section('page_title', $campaign->name)

@section('content')
<!-- Header & Main Actions -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <h4 class="fw-bold mb-0 text-dark">{{ $campaign->name }}</h4>
            @php
                $statusBadge = match($campaign->status) {
                    'running' => 'bg-success',
                    'completed' => 'bg-primary',
                    'paused' => 'bg-warning text-dark',
                    'scheduled' => 'bg-info text-dark',
                    default => 'bg-secondary'
                };
            @endphp
            <span class="badge {{ $statusBadge }} px-2 py-1">{{ ucfirst($campaign->status) }}</span>
            <span class="badge bg-purple text-white px-2 py-1" style="background-color: #7c3aed;">
                <i class="fa-solid fa-wand-magic-sparkles me-1"></i> AI Personalized
            </span>
        </div>
        <p class="text-muted small mb-0 mt-1">
            <strong>Topic:</strong> {{ $campaign->subject }} 
            <span class="mx-2">•</span> 
            <strong>Target Lists:</strong>
            @forelse($lists as $list)
                <span class="badge bg-light text-dark border me-1">📁 {{ $list->name }} ({{ $list->contacts()->count() }})</span>
            @empty
                <span class="text-muted fst-italic">No lists assigned</span>
            @endforelse
        </p>
    </div>

    <!-- Actions Toolbar -->
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <!-- 1. Generate / Regenerate AI Emails -->
        <form method="POST" action="{{ route('admin.marketing.ai-campaigns.generate', $campaign) }}" onsubmit="const btn = this.querySelector('button[type=submit]'); if(btn) { btn.disabled = true; btn.innerHTML = '<i class=\'fa-solid fa-spinner fa-spin me-1\'></i> Generating AI Emails...'; }">
            @csrf
            <button type="submit" class="btn btn-outline-primary fw-semibold" style="border-radius: 8px;" title="Research each lead and generate tailored emails">
                <i class="fa-solid fa-wand-magic-sparkles me-1"></i> 
                {{ $generatedCount > 0 ? 'Generate Remaining' : 'Generate AI Emails' }}
            </button>
        </form>

        @if($generatedCount > 0)
        <!-- Force Regenerate All -->
        <form method="POST" action="{{ route('admin.marketing.ai-campaigns.generate', $campaign) }}" onsubmit="return confirm('Regenerate AI emails for ALL un-sent leads? Existing un-sent drafts will be re-written.');">
            @csrf
            <input type="hidden" name="force" value="1">
            <button type="submit" class="btn btn-outline-secondary btn-sm" style="border-radius: 8px;" title="Overwrite all drafts with new AI generations">
                <i class="fa-solid fa-arrows-rotate me-1"></i> Force Regenerate All
            </button>
        </form>

        <!-- 2. Approve All Drafts -->
        @if($approvedCount < $generatedCount)
        <form method="POST" action="{{ route('admin.marketing.ai-campaigns.approve', $campaign) }}">
            @csrf
            <input type="hidden" name="approve_all" value="1">
            <button type="submit" class="btn btn-outline-success fw-semibold" style="border-radius: 8px;">
                <i class="fa-solid fa-check-double me-1"></i> Approve All ({{ $generatedCount - $approvedCount }})
            </button>
        </form>
        @endif

        <!-- 3. Send Instantly (Live SES) -->
        <form method="POST" action="{{ route('admin.marketing.ai-campaigns.start', $campaign) }}" onsubmit="const btn = this.querySelector('button[type=submit]'); if(btn) { btn.disabled = true; btn.innerHTML = '<i class=\'fa-solid fa-spinner fa-spin me-1\'></i> Dispatching...'; }">
            @csrf
            <input type="hidden" name="mode" value="instant">
            <button type="submit" class="btn btn-success fw-bold shadow-sm" style="border-radius: 8px;" {{ $approvedCount === 0 && $generatedCount === 0 ? 'disabled' : '' }}>
                <i class="fa-solid fa-bolt me-1"></i> Send Instantly (Live SES)
            </button>
        </form>
        @endif

        <!-- Send Test Email -->
        <button type="button" class="btn btn-outline-info" style="border-radius: 8px;" data-bs-toggle="modal" data-bs-target="#testSendModal">
            <i class="fa-solid fa-paper-plane me-1"></i> Send Test
        </button>

        <!-- Edit Campaign Details -->
        <a href="{{ route('admin.marketing.ai-campaigns.edit', $campaign) }}" class="btn btn-outline-secondary" style="border-radius: 8px;">
            <i class="fa-solid fa-pen-to-square me-1"></i> Edit
        </a>

        <!-- Delete Campaign -->
        <form method="POST" action="{{ route('admin.marketing.ai-campaigns.destroy', $campaign) }}" onsubmit="return confirm('Are you sure you want to delete this AI campaign and all generated emails?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger" style="border-radius: 8px;">
                <i class="fa-solid fa-trash me-1"></i>
            </button>
        </form>
    </div>
</div>

<!-- Campaign Description Box -->
<div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(to right, #faf5ff, #f8fafc); border-left: 4px solid #8b5cf6 !important;">
    <div class="card-body py-3">
        <div class="d-flex align-items-start gap-3">
            <div class="p-2 rounded-circle bg-white text-purple shadow-sm mt-1" style="color: #7c3aed;">
                <i class="fa-solid fa-brain fs-5"></i>
            </div>
            <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-dark small text-uppercase tracking-wider">AI Strategy & Brief</span>
                    <span class="badge bg-light text-muted border">Campaign ID: CAMP-{{ str_pad($campaign->id, 3, '0', STR_PAD_LEFT) }}</span>
                </div>
                <p class="text-dark mb-0 mt-1" style="font-size: 0.92rem; line-height: 1.5;">{{ $campaign->description }}</p>
            </div>
        </div>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm p-3 text-center h-100">
            <span class="text-muted small">Target Leads</span>
            <h4 class="fw-bold my-1 text-dark">{{ number_format($targetCount) }}</h4>
            <span class="small text-muted">In Contact Lists</span>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm p-3 text-center h-100">
            <span class="text-muted small">AI Generated</span>
            <h4 class="fw-bold my-1 text-primary">{{ number_format($generatedCount) }}</h4>
            <span class="small text-muted">{{ $targetCount > 0 ? round(($generatedCount / $targetCount) * 100) : 0 }}% of audience</span>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm p-3 text-center h-100">
            <span class="text-muted small">Approved</span>
            <h4 class="fw-bold my-1 text-success">{{ number_format($approvedCount) }}</h4>
            <span class="small text-muted">Ready to dispatch</span>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm p-3 text-center h-100">
            <span class="text-muted small">Dispatched</span>
            <h4 class="fw-bold my-1 text-info">{{ number_format($campaign->sent_count) }}</h4>
            <span class="small text-muted">Sent via AWS SES</span>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm p-3 text-center h-100">
            <span class="text-muted small">Delivered</span>
            <h4 class="fw-bold my-1 text-dark">{{ number_format($campaign->delivered_count) }}</h4>
            <span class="small text-muted">Confirmed</span>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm p-3 text-center h-100">
            <span class="text-muted small">Unique Opens</span>
            <h4 class="fw-bold my-1 text-purple" style="color: #7c3aed;">{{ number_format($campaign->opened_count) }}</h4>
            <span class="small text-muted">{{ $campaign->open_rate }}% open rate</span>
        </div>
    </div>
</div>

<!-- Filter & Search Bar -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('admin.marketing.ai-campaigns.show', $campaign) }}" class="row g-2 align-items-center">
            <!-- Filter Tabs -->
            <div class="col-md-7 d-flex gap-1 flex-wrap">
                <a href="{{ route('admin.marketing.ai-campaigns.show', [$campaign, 'search' => $search]) }}" class="btn btn-sm {{ empty($statusFilter) ? 'btn-primary' : 'btn-light border' }}">
                    All Leads ({{ $campaign->emails()->count() }})
                </a>
                <a href="{{ route('admin.marketing.ai-campaigns.show', [$campaign, 'status' => 'pending_approval', 'search' => $search]) }}" class="btn btn-sm {{ $statusFilter === 'pending_approval' ? 'btn-warning text-dark' : 'btn-light border' }}">
                    Pending Approval ({{ $campaign->emails()->where('is_approved', false)->where('status', 'generated')->count() }})
                </a>
                <a href="{{ route('admin.marketing.ai-campaigns.show', [$campaign, 'status' => 'approved', 'search' => $search]) }}" class="btn btn-sm {{ $statusFilter === 'approved' ? 'btn-success' : 'btn-light border' }}">
                    Approved ({{ $approvedCount }})
                </a>
                <a href="{{ route('admin.marketing.ai-campaigns.show', [$campaign, 'status' => 'sent', 'search' => $search]) }}" class="btn btn-sm {{ $statusFilter === 'sent' ? 'btn-info text-white' : 'btn-light border' }}">
                    Dispatched ({{ $campaign->sent_count }})
                </a>
                <a href="{{ route('admin.marketing.ai-campaigns.show', [$campaign, 'status' => 'failed', 'search' => $search]) }}" class="btn btn-sm {{ $statusFilter === 'failed' ? 'btn-danger' : 'btn-light border' }}">
                    Failed ({{ $campaign->failed_count + $campaign->bounced_count }})
                </a>
            </div>

            <!-- Search Field -->
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search by name, company, email..." value="{{ $search }}">
                    @if($statusFilter)
                        <input type="hidden" name="status" value="{{ $statusFilter }}">
                    @endif
                    <button class="btn btn-secondary" type="submit">Filter</button>
                    @if($search || $statusFilter)
                        <a href="{{ route('admin.marketing.ai-campaigns.show', $campaign) }}" class="btn btn-outline-secondary">Reset</a>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Table of Prospect Emails -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-dark">
            <i class="fa-solid fa-envelope-open-text me-2 text-primary"></i>
            Personalized Email Dispatches & Reviews ({{ $emails->total() }})
        </h6>
        <span class="small text-muted">Showing {{ $emails->firstItem() ?? 0 }}-{{ $emails->lastItem() ?? 0 }} of {{ $emails->total() }}</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small text-uppercase text-muted">
                <tr>
                    <th style="width: 25%;">Prospect & Company</th>
                    <th style="width: 40%;">AI Personalized Email</th>
                    <th style="width: 15%;">Status / Approval</th>
                    <th style="width: 20%;" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($emails as $email)
                    @php
                        $contact = $email->contact;
                    @endphp
                    <tr>
                        <!-- 1. Prospect Info -->
                        <td>
                            <div class="fw-bold text-dark">{{ $email->recipient_name ?: ($contact->name ?? 'Lead') }}</div>
                            <div class="small text-muted">{{ $email->recipient_email }}</div>
                            
                            @if($contact)
                                <div class="mt-1 d-flex flex-wrap align-items-center gap-1">
                                    <span class="badge bg-light text-dark border">
                                        <i class="fa-regular fa-building me-1"></i> {{ $contact->company ?: 'N/A' }}
                                    </span>
                                    @if($contact->business_type)
                                        <span class="badge bg-info-subtle text-info border border-info-subtle">
                                            {{ $contact->business_type }}
                                        </span>
                                    @endif
                                </div>
                                @if($contact->website)
                                    <div class="mt-1">
                                        <a href="{{ $contact->website }}" target="_blank" rel="noopener noreferrer" class="small text-primary text-decoration-none" style="font-size: 0.78rem;">
                                            <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> {{ parse_url($contact->website, PHP_URL_HOST) ?: $contact->website }}
                                        </a>
                                    </div>
                                @endif
                                @if($contact->business_description)
                                    <div class="small text-muted mt-1 fst-italic" style="font-size: 0.75rem; max-width: 280px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $contact->business_description }}">
                                        "{{ $contact->business_description }}"
                                    </div>
                                @endif
                            @endif
                        </td>

                        <!-- 2. AI Generated Email Subject & Content -->
                        <td>
                            <div class="fw-semibold text-dark mb-1">
                                <i class="fa-regular fa-envelope me-1 text-primary"></i>
                                {{ $email->subject }}
                            </div>

                            @if($email->ai_rationale)
                                <div class="mb-2">
                                    <span class="badge bg-purple-subtle text-purple border" style="background-color: #f3e8ff; color: #6b21a8; font-size: 0.74rem;">
                                        <i class="fa-solid fa-lightbulb me-1"></i> {{ $email->ai_rationale }}
                                    </span>
                                </div>
                            @endif

                            <div class="text-muted small bg-light p-2 rounded border" style="font-size: 0.8rem; max-height: 70px; overflow: hidden; text-overflow: ellipsis;">
                                {{ Str::limit(strip_tags($email->body_html ?: $email->body_text), 160) }}
                            </div>
                        </td>

                        <!-- 3. Status & Approval -->
                        <td>
                            @php
                                $statusStyle = match($email->status) {
                                    'generated' => 'bg-secondary',
                                    'queued' => 'bg-warning text-dark',
                                    'sent' => 'bg-primary',
                                    'delivered' => 'bg-info text-white',
                                    'opened' => 'bg-success',
                                    'clicked' => 'bg-success',
                                    'failed' => 'bg-danger',
                                    'bounced' => 'bg-danger',
                                    default => 'bg-light text-dark border'
                                };
                            @endphp

                            <span class="badge {{ $statusStyle }} px-2 py-1 mb-1">
                                {{ ucfirst($email->status) }}
                            </span>

                            <!-- Approval State -->
                            @if(in_array($email->status, ['generated', 'draft']))
                                <div class="mt-1">
                                    <form method="POST" action="{{ route('admin.marketing.ai-campaigns.approve', $campaign) }}">
                                        @csrf
                                        <input type="hidden" name="email_id" value="{{ $email->id }}">
                                        @if($email->is_approved)
                                            <button type="submit" class="btn btn-xs btn-outline-success border-success text-success bg-white py-0 px-2 fw-semibold" style="font-size: 0.75rem;" title="Click to revoke approval">
                                                <i class="fa-solid fa-circle-check me-1"></i> Approved
                                            </button>
                                        @else
                                            <button type="submit" class="btn btn-xs btn-outline-warning text-dark bg-white py-0 px-2 fw-semibold" style="font-size: 0.75rem;" title="Click to approve for sending">
                                                <i class="fa-regular fa-circle me-1"></i> Click to Approve
                                            </button>
                                        @endif
                                    </form>
                                </div>
                            @elseif(in_array($email->status, ['sent', 'delivered', 'opened', 'clicked']))
                                <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                                    Dispatched {{ $email->sent_at ? $email->sent_at->diffForHumans() : '' }}
                                </div>
                            @elseif(in_array($email->status, ['failed', 'bounced']))
                                <div class="small text-danger mt-1" style="font-size: 0.75rem;" title="{{ $email->error_message }}">
                                    {{ Str::limit($email->error_message, 40) }}
                                </div>
                            @endif
                        </td>

                        <!-- 4. Action Buttons -->
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <!-- Preview Modal Trigger -->
                                <button type="button" class="btn btn-light border" data-bs-toggle="modal" data-bs-target="#previewModal_{{ $email->id }}" title="Preview Full Email">
                                    <i class="fa-regular fa-eye"></i>
                                </button>

                                <!-- Edit Draft Modal Trigger -->
                                @if(in_array($email->status, ['generated', 'draft', 'failed']))
                                    <button type="button" class="btn btn-light border" data-bs-toggle="modal" data-bs-target="#editModal_{{ $email->id }}" title="Edit Subject & Body">
                                        <i class="fa-solid fa-pen-to-square text-primary"></i>
                                    </button>

                                    <!-- Regenerate AI Email Button -->
                                    @if($contact)
                                    <form method="POST" action="{{ route('admin.marketing.ai-campaigns.generate', $campaign) }}" class="d-inline" onsubmit="const btn = this.querySelector('button[type=submit]'); if(btn) { btn.disabled = true; }">
                                        @csrf
                                        <input type="hidden" name="contact_id" value="{{ $contact->id }}">
                                        <button type="submit" class="btn btn-light border" title="Regenerate with AI">
                                            <i class="fa-solid fa-arrows-rotate text-secondary"></i>
                                        </button>
                                    </form>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>

                    <!-- Modal: Preview Email -->
                    <div class="modal fade" id="previewModal_{{ $email->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header border-bottom">
                                    <div>
                                        <h5 class="modal-title fw-bold">
                                            <i class="fa-solid fa-wand-magic-sparkles text-purple me-2" style="color: #7c3aed;"></i>
                                            Personalized Preview: {{ $email->recipient_name }}
                                        </h5>
                                        <div class="small text-muted">
                                            Company: <strong>{{ $contact->company ?? 'N/A' }}</strong> ({{ $contact->business_type ?? 'General' }})
                                        </div>
                                    </div>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body p-4">
                                    @if($email->ai_rationale)
                                        <div class="p-3 mb-3 rounded-3" style="background-color: #faf5ff; border: 1px solid #e9d5ff;">
                                            <div class="fw-bold small text-purple" style="color: #6b21a8;">
                                                <i class="fa-solid fa-lightbulb me-1"></i> AI Personalization Logic:
                                            </div>
                                            <div class="small text-dark mt-1">{{ $email->ai_rationale }}</div>
                                        </div>
                                    @endif

                                    <div class="mb-3 p-3 bg-light rounded border">
                                        <div class="small text-muted"><strong>To:</strong> {{ $email->recipient_name }} &lt;{{ $email->recipient_email }}&gt;</div>
                                        <div class="small text-muted mt-1"><strong>Subject:</strong> <span class="fw-bold text-dark">{{ $email->subject }}</span></div>
                                    </div>

                                    <div class="card border p-4 bg-white" style="min-height: 250px;">
                                        {!! $email->body_html ?: nl2br(e($email->body_text)) !!}
                                    </div>
                                </div>
                                <div class="modal-footer bg-light">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    @if(!$email->is_approved && in_array($email->status, ['generated', 'draft']))
                                        <form method="POST" action="{{ route('admin.marketing.ai-campaigns.approve', $campaign) }}">
                                            @csrf
                                            <input type="hidden" name="email_id" value="{{ $email->id }}">
                                            <button type="submit" class="btn btn-success">
                                                <i class="fa-solid fa-check me-1"></i> Approve This Email
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal: Edit Draft -->
                    @if(in_array($email->status, ['generated', 'draft', 'failed']))
                    <div class="modal fade" id="editModal_{{ $email->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content">
                                <form method="POST" action="{{ route('admin.marketing.ai-campaigns.update-email', [$campaign, $email]) }}">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-header border-bottom">
                                        <h5 class="modal-title fw-bold">
                                            <i class="fa-solid fa-pen-to-square text-primary me-2"></i>
                                            Edit Draft for {{ $email->recipient_name }} ({{ $email->recipient_email }})
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body p-4">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold text-dark small">Email Subject Line <span class="text-danger">*</span></label>
                                            <input type="text" name="subject" class="form-control" value="{{ old('subject', $email->subject) }}" required>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-bold text-dark small">Email Body (HTML supported) <span class="text-danger">*</span></label>
                                            <textarea name="body_html" class="form-control font-monospace" rows="10" required>{{ old('body_html', $email->body_html ?: $email->body_text) }}</textarea>
                                            <div class="form-text">Saving changes will automatically mark this email as Approved.</div>
                                        </div>
                                    </div>
                                    <div class="modal-footer bg-light">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary fw-bold">
                                            <i class="fa-solid fa-save me-1"></i> Save & Approve
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endif

                @empty
                    <tr>
                        <td colspan="4" class="text-center py-5 text-muted">
                            <div class="mb-3">
                                <i class="fa-solid fa-wand-magic-sparkles fs-1 text-secondary opacity-50"></i>
                            </div>
                            <h6 class="fw-bold text-dark">No Personalized Emails Generated Yet</h6>
                            <p class="small text-muted mb-3">
                                Click the button below to generate completely unique, tailored emails for each prospect in the target lists.
                            </p>
                            <form method="POST" action="{{ route('admin.marketing.ai-campaigns.generate', $campaign) }}" onsubmit="const btn = this.querySelector('button[type=submit]'); if(btn) { btn.disabled = true; btn.innerHTML = '<i class=\'fa-solid fa-spinner fa-spin me-1\'></i> Generating AI Emails...'; }">
                                @csrf
                                <button type="submit" class="btn btn-primary fw-bold px-4" style="border-radius: 8px;">
                                    <i class="fa-solid fa-wand-magic-sparkles me-2"></i> Generate AI Emails for All Leads
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($emails->hasPages())
        <div class="card-footer bg-white border-top py-3">
            {{ $emails->links() }}
        </div>
    @endif
</div>

<!-- Modal: Send Test Email -->
<div class="modal fade" id="testSendModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.marketing.ai-campaigns.test-send', $campaign) }}" onsubmit="const btn = this.querySelector('button[type=submit]'); if(btn) { btn.disabled = true; btn.innerHTML = '<i class=\'fa-solid fa-spinner fa-spin me-1\'></i> Dispatching Test...'; }">
                @csrf
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold">
                        <i class="fa-solid fa-paper-plane text-info me-2"></i> Send Live AI Test Email
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark small">Destination Test Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="test_email" class="form-control" placeholder="e.g. your-email@company.com" required>
                        <div class="form-text">The email where the live SES test dispatch will be delivered.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark small">Simulate Prospect Persona</label>
                        <select name="contact_id" class="form-select">
                            <option value="">Default Persona (Smart Maritime Network - Maritime Logistics)</option>
                            @foreach($targetContacts->take(15) as $tc)
                                <option value="{{ $tc->id }}">{{ $tc->name }} ({{ $tc->company ?: 'General' }}) - {{ $tc->business_type ?: 'B2B' }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Choose which contact's company & industry AI should customize the test email for.</div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info text-white fw-bold">
                        <i class="fa-solid fa-paper-plane me-1"></i> Send Test Now
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
