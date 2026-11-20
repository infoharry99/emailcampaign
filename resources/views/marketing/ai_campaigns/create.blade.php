@extends('layouts.marketing')

@section('title', 'New AI Personalized Campaign')
@section('breadcrumb', 'Campaigns / AI Personalized / Create')
@section('page_title', 'Create AI Personalized Email Campaign')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1" style="color: #0f172a;">New AI Personalized Campaign</h4>
                <p class="text-muted small mb-0">Configure your campaign parameters. AI will research every prospect's business and craft individual emails.</p>
            </div>
            <a href="{{ route('admin.marketing.ai-campaigns.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius: 8px;">
                <i class="fa-solid fa-arrow-left me-1"></i> Back
            </a>
        </div>

        <div class="flat-card p-4">
            <!-- How AI Works Banner -->
            <div class="p-3 mb-4 rounded-3 d-flex align-items-start gap-3" style="background-color: #f0fdf4; border: 1px solid #bbf7d0;">
                <i class="fa-solid fa-wand-magic-sparkles text-success fs-4 mt-1"></i>
                <div class="small text-dark">
                    <strong>100% Unique Email per Prospect:</strong>
                    AI will inspect each prospect's <strong>Company Name, Website, Industry, and Business Description</strong> from Contacts Master. Each customer will receive a bespoke, consultative email addressing their exact market domain—not a generic template.
                </div>
            </div>

            <form method="POST" action="{{ route('admin.marketing.ai-campaigns.store') }}" onsubmit="const btn = this.querySelector('button[type=submit]'); if(btn) { btn.disabled = true; btn.innerHTML = '<i class=\'fa-solid fa-spinner fa-spin me-1\'></i> Creating Campaign...'; }">
                @csrf

                <!-- 1. Campaign Name -->
                <div class="mb-4">
                    <label class="form-label fw-bold text-dark">
                        1. Campaign Name <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required placeholder="e.g. Q4 Maritime & Supply Chain Growth Campaign" style="border-radius: 8px;">
                    <div class="form-text">Internal identifier for this marketing campaign.</div>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <!-- 2. Select Contact List(s) -->
                <div class="mb-4">
                    <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center">
                        <span>2. Select Target Contact List(s) <span class="text-danger">*</span></span>
                        <span class="text-muted small fw-normal">Select one or multiple lists</span>
                    </label>

                    @if(isset($lists) && $lists->isNotEmpty())
                        <div class="p-3 bg-light rounded-3 border">
                            <div class="row g-2">
                                @foreach($lists as $list)
                                    <div class="col-md-6">
                                        <div class="form-check p-2 bg-white rounded border d-flex align-items-center justify-content-between gap-2 shadow-sm">
                                            <div class="d-flex align-items-center gap-2">
                                                <input class="form-check-input ms-1" type="checkbox" name="contact_list_ids[]" value="{{ $list->id }}" id="list_{{ $list->id }}" {{ in_array($list->id, old('contact_list_ids', [])) ? 'checked' : '' }}>
                                                <label class="form-check-label fw-semibold text-dark small" for="list_{{ $list->id }}">
                                                    📁 {{ $list->name }}
                                                </label>
                                            </div>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1" style="font-size: 0.72rem;">
                                                {{ number_format($list->contacts_count) }} leads
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="alert alert-warning mb-0 small">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i> No contact lists found.
                            <a href="{{ route('admin.marketing.lists.create') }}" class="fw-bold text-dark">Create a Contact List first</a>.
                        </div>
                    @endif
                    @error('contact_list_ids') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <!-- 3. Campaign Subject / Topic -->
                <div class="mb-4">
                    <label class="form-label fw-bold text-dark">
                        3. Campaign Subject / Main Topic <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="subject" class="form-control @error('subject') is-invalid @enderror" value="{{ old('subject') }}" required placeholder="e.g. Introduce our high-deliverability cloud infrastructure & consulting services" style="border-radius: 8px;">
                    <div class="form-text">The core subject or message intent. AI will craft an engaging, personalized email subject for each individual prospect based on this.</div>
                    @error('subject') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <!-- 4. Short Description -->
                <div class="mb-4">
                    <label class="form-label fw-bold text-dark">
                        4. Short Description & Offer Details <span class="text-danger">*</span>
                    </label>
                    <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="4" required placeholder="Explain what type of email or offer we want to communicate. Example: Introduce our managed cloud and high-volume email deliverability services. Explain how we help companies reduce infrastructure costs and optimize operations, and invite them for a 15-minute introductory call." style="border-radius: 8px;">{{ old('description') }}</textarea>
                    <div class="form-text">AI will read this description and adapt the value proposition specifically to the prospect's industry.</div>
                    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                    <a href="{{ route('admin.marketing.ai-campaigns.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-brand">
                        <i class="fa-solid fa-arrow-right me-1"></i> Create Campaign & Proceed to AI Generation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
