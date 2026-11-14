@extends('layouts.marketing')

@section('title', 'Create Marketing Campaign')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-9">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1">Create Campaign</h3>
                <p class="text-muted small mb-0">Configure your campaign objectives, choose AI or Template mode, and schedule dispatches.</p>
            </div>
            <a href="{{ route('admin.marketing.campaigns.index') }}" class="btn btn-outline-secondary">
                <i class="fa-solid fa-arrow-left me-1"></i> Back
            </a>
        </div>

        <div class="card p-4">
            <form method="POST" action="{{ route('admin.marketing.campaigns.store') }}" onsubmit="const btn = this.querySelector('button[type=submit]'); if(btn) { btn.disabled = true; btn.innerHTML = '<i class=\'fa-solid fa-spinner fa-spin me-1\'></i> Saving...'; }">
                @csrf

                <!-- Basic Information -->
                <h5 class="fw-bold border-bottom pb-2 mb-3 text-primary"><i class="fa-solid fa-circle-info me-2"></i> Campaign Identity</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Campaign Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Q4 Cloud Infrastructure Consultation" value="{{ old('name') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Sending Mode <span class="text-danger">*</span></label>
                        <select name="sending_mode" id="sendingModeSelect" class="form-select" required>
                            <option value="ai" selected>AI Generated (OpenAI Personalized per contact)</option>
                            <option value="template">Template (Static HTML with Dynamic Tags)</option>
                            <option value="both">Both (AI Initial Draft + Manual Editing)</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Campaign Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Internal campaign notes and target demographic info...">{{ old('description') }}</textarea>
                    </div>
                </div>

                <!-- AI Prompt Details -->
                <div id="aiPromptSection">
                    <h5 class="fw-bold border-bottom pb-2 mb-3 text-primary"><i class="fa-solid fa-wand-magic-sparkles me-2 text-warning"></i> AI Writer Objective & Details</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Campaign Purpose / Objective <span class="text-danger">*</span></label>
                            <textarea name="purpose" class="form-control" rows="3" required placeholder="Describe the goal of this email. Example: Introduce our managed cloud services, highlight cost savings on AWS/SES, and invite recipient for a 15-min discovery call.">{{ old('purpose') }}</textarea>
                            <div class="form-text">OpenAI will use this goal to generate a ~100-word concise, high-converting email for each prospect.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Product or Service Details</label>
                            <textarea name="product_service_details" class="form-control" rows="2" placeholder="Features, special advantages, or consultation offer details...">{{ old('product_service_details') }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Template & Content -->
                <h5 class="fw-bold border-bottom pb-2 mb-3 text-primary"><i class="fa-solid fa-envelope me-2"></i> Email Subject & Content</h5>
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Default Subject Line</label>
                        <input type="text" name="subject" class="form-control" placeholder="e.g. Accelerating Tech Growth for @{{company}}" value="{{ old('subject') }}">
                        <div class="form-text">For AI mode, OpenAI will generate customized subjects if left empty or use this as guidance.</div>
                    </div>
                    <div class="col-12" id="templateContentGroup">
                        <label class="form-label fw-semibold">Template Content (HTML supported)</label>
                        <textarea name="content" class="form-control font-monospace" rows="6" placeholder="<p>Hi @{{name}},</p><p>We help businesses like @{{company}} scale with high deliverability.</p>">{{ old('content') }}</textarea>
                        <div class="form-text">Variables: <code>@{{name}}</code>, <code>@{{company}}</code>, <code>@{{email}}</code>, <code>@{{website}}</code>, <code>@{{unsubscribe_url}}</code></div>
                    </div>
                </div>

                <!-- Audience Targeting & Scheduling -->
                <h5 class="fw-bold border-bottom pb-2 mb-3 text-primary"><i class="fa-solid fa-sliders me-2"></i> Target Audience & Schedule</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Target Contacts <span class="text-danger">*</span></label>
                        <select name="target_criteria" class="form-select" required>
                            <option value="all">All Verified & Subscribed Contacts ({{ number_format($totalEligibleContacts) }} leads)</option>
                            <option value="business_type:Technology">Industry: Technology</option>
                            <option value="business_type:Enterprise">Industry: Enterprise</option>
                            <option value="business_type:Retail">Industry: Retail</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Sending Date (Optional)</label>
                        <input type="date" name="sending_date" class="form-control" value="{{ old('sending_date') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Sending Time (Optional)</label>
                        <input type="time" name="sending_time" class="form-control" value="{{ old('sending_time') }}">
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.marketing.campaigns.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-brand">Save Campaign</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
