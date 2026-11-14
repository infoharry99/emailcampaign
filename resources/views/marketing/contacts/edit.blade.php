@extends('layouts.marketing')

@section('title', 'Edit Contact - ' . ($contact->name ?: $contact->email))
@section('breadcrumb', 'Audience / Edit')
@section('page_title', 'Edit Marketing Contact')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1" style="color: #0f172a;">Update Contact Profile</h4>
                <p class="text-muted small mb-0">Modify contact metadata, consent history, or subscription status.</p>
            </div>
            <a href="{{ route('admin.marketing.contacts.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius: 8px;">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to Directory
            </a>
        </div>

        <div class="flat-card p-4">
            <form method="POST" action="{{ route('admin.marketing.contacts.update', $contact) }}">
                @csrf
                @method('PUT')

                <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary">
                    <i class="fa-solid fa-user me-2"></i> Contact & Company Details
                </h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size: 0.8rem;">Full Name</label>
                        <input type="text" name="name" class="form-control form-control-sm" value="{{ old('name', $contact->name) }}" style="border-radius: 8px; border-color: #e2e8f0;">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size: 0.8rem;">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control form-control-sm @error('email') is-invalid @enderror" value="{{ old('email', $contact->email) }}" required style="border-radius: 8px; border-color: #e2e8f0;">
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size: 0.8rem;">Company Name</label>
                        <input type="text" name="company" class="form-control form-control-sm" value="{{ old('company', $contact->company) }}" style="border-radius: 8px; border-color: #e2e8f0;">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size: 0.8rem;">Website URL</label>
                        <input type="url" name="website" class="form-control form-control-sm" value="{{ old('website', $contact->website) }}" style="border-radius: 8px; border-color: #e2e8f0;">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size: 0.8rem;">Phone Number</label>
                        <input type="text" name="phone" class="form-control form-control-sm" value="{{ old('phone', $contact->phone) }}" style="border-radius: 8px; border-color: #e2e8f0;">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size: 0.8rem;">Industry / Business Type</label>
                        <input type="text" name="business_type" class="form-control form-control-sm" value="{{ old('business_type', $contact->business_type) }}" style="border-radius: 8px; border-color: #e2e8f0;">
                    </div>
                </div>

                <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary">
                    <i class="fa-solid fa-shield-halved me-2"></i> Permissions & Marketing Consent
                </h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size: 0.8rem;">Consent Status <span class="text-danger">*</span></label>
                        <select name="consent_status" class="form-select form-select-sm" required style="border-radius: 8px; border-color: #e2e8f0;">
                            <option value="granted" {{ old('consent_status', $contact->consent_status) === 'granted' ? 'selected' : '' }}>Granted (Permission Documented)</option>
                            <option value="pending" {{ old('consent_status', $contact->consent_status) === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="revoked" {{ old('consent_status', $contact->consent_status) === 'revoked' ? 'selected' : '' }}>Revoked</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size: 0.8rem;">Consent Source</label>
                        <input type="text" name="consent_source" class="form-control form-control-sm" value="{{ old('consent_source', $contact->consent_source) }}" style="border-radius: 8px; border-color: #e2e8f0;">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size: 0.8rem;">Subscription Status <span class="text-danger">*</span></label>
                        <select name="subscription_status" class="form-select form-select-sm" required style="border-radius: 8px; border-color: #e2e8f0;">
                            <option value="subscribed" {{ old('subscription_status', $contact->subscription_status) === 'subscribed' ? 'selected' : '' }}>Subscribed</option>
                            <option value="unsubscribed" {{ old('subscription_status', $contact->subscription_status) === 'unsubscribed' ? 'selected' : '' }}>Unsubscribed</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size: 0.8rem;">Verification Status <span class="text-danger">*</span></label>
                        <select name="verification_status" class="form-select form-select-sm" required style="border-radius: 8px; border-color: #e2e8f0;">
                            <option value="valid" {{ old('verification_status', $contact->verification_status) === 'valid' ? 'selected' : '' }}>Valid</option>
                            <option value="unverified" {{ old('verification_status', $contact->verification_status) === 'unverified' ? 'selected' : '' }}>Unverified</option>
                            <option value="risky" {{ old('verification_status', $contact->verification_status) === 'risky' ? 'selected' : '' }}>Risky</option>
                            <option value="invalid" {{ old('verification_status', $contact->verification_status) === 'invalid' ? 'selected' : '' }}>Invalid</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.marketing.contacts.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius: 8px;">Cancel</a>
                    <button type="submit" class="btn btn-sm btn-dark" style="border-radius: 8px; padding: 6px 16px;">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Update Contact
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
