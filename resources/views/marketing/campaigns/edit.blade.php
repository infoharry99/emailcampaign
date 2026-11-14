@extends('layouts.marketing')

@section('title', 'Edit Campaign - ' . $campaign->name)
@section('breadcrumb', 'Campaigns / Edit')
@section('page_title', 'Edit Campaign')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-9">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1" style="color: #0f172a;">Edit Campaign Details</h4>
                <p class="text-muted small mb-0">Update campaign settings, objectives, and sending criteria.</p>
            </div>
            <a href="{{ route('admin.marketing.campaigns.show', $campaign) }}" class="btn btn-sm btn-outline-secondary" style="border-radius: 8px;">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to Report
            </a>
        </div>

        <div class="flat-card p-4">
            <form method="POST" action="{{ route('admin.marketing.campaigns.update', $campaign) }}">
                @csrf
                @method('PUT')

                <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary"><i class="fa-solid fa-circle-info me-2"></i> Campaign Identity</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold" style="font-size: 0.8rem;">Campaign Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $campaign->name) }}" required style="border-radius: 8px; border-color: #e2e8f0;">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size: 0.8rem;">Sending Mode <span class="text-danger">*</span></label>
                        <select name="sending_mode" class="form-select" required style="border-radius: 8px; border-color: #e2e8f0;">
                            <option value="ai" {{ old('sending_mode', $campaign->sending_mode) === 'ai' ? 'selected' : '' }}>AI Generated</option>
                            <option value="template" {{ old('sending_mode', $campaign->sending_mode) === 'template' ? 'selected' : '' }}>Template</option>
                            <option value="both" {{ old('sending_mode', $campaign->sending_mode) === 'both' ? 'selected' : '' }}>Both (AI + Manual Edit)</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold" style="font-size: 0.8rem;">Campaign Description</label>
                        <textarea name="description" class="form-control" rows="2" style="border-radius: 8px; border-color: #e2e8f0;">{{ old('description', $campaign->description) }}</textarea>
                    </div>
                </div>

                <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary"><i class="fa-solid fa-wand-magic-sparkles me-2 text-warning"></i> AI Writer Objective</h6>
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <label class="form-label fw-semibold" style="font-size: 0.8rem;">Campaign Purpose / Objective <span class="text-danger">*</span></label>
                        <textarea name="purpose" class="form-control" rows="3" required style="border-radius: 8px; border-color: #e2e8f0;">{{ old('purpose', $campaign->purpose) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold" style="font-size: 0.8rem;">Product / Service Details</label>
                        <textarea name="product_service_details" class="form-control" rows="2" style="border-radius: 8px; border-color: #e2e8f0;">{{ old('product_service_details', $campaign->product_service_details) }}</textarea>
                    </div>
                </div>

                <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary"><i class="fa-solid fa-envelope me-2"></i> Content & Targeting</h6>
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <label class="form-label fw-semibold" style="font-size: 0.8rem;">Subject Line</label>
                        <input type="text" name="subject" class="form-control" value="{{ old('subject', $campaign->subject) }}" style="border-radius: 8px; border-color: #e2e8f0;">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold" style="font-size: 0.8rem;">Template Content</label>
                        <textarea name="content" class="form-control font-monospace" rows="6" style="border-radius: 8px; border-color: #e2e8f0;">{{ old('content', $campaign->content) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size: 0.8rem;">Target Contacts</label>
                        <select name="target_criteria" class="form-select" style="border-radius: 8px; border-color: #e2e8f0;">
                            <option value="all" {{ old('target_criteria', $campaign->target_criteria) === 'all' ? 'selected' : '' }}>All Verified & Subscribed Contacts</option>
                            <option value="business_type:Technology" {{ old('target_criteria', $campaign->target_criteria) === 'business_type:Technology' ? 'selected' : '' }}>Industry: Technology</option>
                            <option value="business_type:Enterprise" {{ old('target_criteria', $campaign->target_criteria) === 'business_type:Enterprise' ? 'selected' : '' }}>Industry: Enterprise</option>
                            <option value="business_type:Logistics" {{ old('target_criteria', $campaign->target_criteria) === 'business_type:Logistics' ? 'selected' : '' }}>Industry: Logistics</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.marketing.campaigns.show', $campaign) }}" class="btn btn-sm btn-outline-secondary" style="border-radius: 8px;">Cancel</a>
                    <button type="submit" class="btn btn-sm btn-dark" style="border-radius: 8px; padding: 6px 18px;">Update Campaign</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
