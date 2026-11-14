@extends('layouts.marketing')

@section('title', 'Add New Contact')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1">Create Marketing Contact</h3>
                <p class="text-muted small mb-0">Record a new verified prospect with audit-ready marketing consent.</p>
            </div>
            <a href="{{ route('admin.marketing.contacts.index') }}" class="btn btn-outline-secondary">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to Directory
            </a>
        </div>

        <div class="card p-4">
            <form method="POST" action="{{ route('admin.marketing.contacts.store') }}">
                @csrf

                <h5 class="fw-bold border-bottom pb-2 mb-3 text-primary"><i class="fa-solid fa-user me-2"></i> Contact & Company Details</h5>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Full Name</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="e.g. John Doe">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required placeholder="john@company.com">
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Company Name</label>
                        <input type="text" name="company" class="form-control" value="{{ old('company') }}" placeholder="e.g. Acme Innovations">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Website URL</label>
                        <input type="url" name="website" class="form-control" value="{{ old('website') }}" placeholder="https://company.com">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Phone Number</label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" placeholder="+91 9876543210">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Business Industry / Niche</label>
                        <input type="text" name="business_type" class="form-control" value="{{ old('business_type', 'Technology') }}" placeholder="e.g. SaaS, Logistics, Retail">
                    </div>
                </div>

                <h5 class="fw-bold border-bottom pb-2 mb-3 mt-4 text-primary"><i class="fa-solid fa-shield-halved me-2"></i> Permission & Marketing Consent</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Consent Status <span class="text-danger">*</span></label>
                        <select name="consent_status" class="form-select" required>
                            <option value="granted" selected>Granted (Permission Explicitly Documented)</option>
                            <option value="pending">Pending</option>
                            <option value="revoked">Revoked</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Consent Source <span class="text-danger">*</span></label>
                        <input type="text" name="consent_source" class="form-control" required value="{{ old('consent_source', 'Direct Inbound Contact / Opt-in') }}" placeholder="e.g. Contact Form, Webinar, Contract Agreement">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Subscription Status <span class="text-danger">*</span></label>
                        <select name="subscription_status" class="form-select" required>
                            <option value="subscribed" selected>Subscribed</option>
                            <option value="unsubscribed">Unsubscribed</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Email Verification Status <span class="text-danger">*</span></label>
                        <select name="verification_status" class="form-select" required>
                            <option value="valid" selected>Valid</option>
                            <option value="unverified">Unverified</option>
                            <option value="risky">Risky</option>
                            <option value="invalid">Invalid</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Consent Proof / Notes</label>
                        <textarea name="consent_proof" class="form-control" rows="2" placeholder="Audit notes regarding where and how the recipient opted in to receive marketing communications..."></textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.marketing.contacts.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-brand">Save Contact</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
