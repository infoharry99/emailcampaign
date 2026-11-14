@extends('layouts.marketing')

@section('title', 'Import Contacts CSV')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1">Bulk Contact CSV Import</h3>
                <p class="text-muted small mb-0">Upload permitted opt-in audience lists with automatic deduplication & suppression enforcement.</p>
            </div>
            <a href="{{ route('admin.marketing.contacts.index') }}" class="btn btn-outline-secondary">
                <i class="fa-solid fa-arrow-left me-1"></i> Directory
            </a>
        </div>

        <div class="card p-4">
            <!-- Strict Compliance Notice -->
            <div class="alert alert-warning d-flex align-items-start mb-4">
                <i class="fa-solid fa-triangle-exclamation fs-4 me-3 text-warning"></i>
                <div class="small">
                    <strong>Mandatory Compliance Warning:</strong>
                    Do NOT upload purchased, scraped, unverified, or third-party mailing lists. Only contacts with affirmative, documented marketing permission are allowed. Suppressed and previously unsubscribed addresses are automatically blocked.
                </div>
            </div>

            <form method="POST" action="{{ route('admin.marketing.contacts.import.process') }}" enctype="multipart/form-data">
                @csrf

                <div class="mb-4">
                    <label class="form-label fw-bold">Select CSV File <span class="text-danger">*</span></label>
                    <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                    <div class="form-text mt-2">
                        Supported columns: <code>name</code> (or <code>first name</code> + <code>last name</code>), <code>email</code> (or <code>work email</code>), <code>company</code>, <code>website</code>, <code>phone</code>, <code>industries</code> / <code>business_type</code>.
                    </div>
                </div>

                <!-- Contact List Assignment -->
                <div class="mb-4 p-3 rounded-3" style="background-color: #f8fafc; border: 1px solid #e2e8f0;">
                    <label class="form-label fw-bold text-dark mb-1">
                        <i class="fa-solid fa-folder-plus text-primary me-1"></i> Assign to Contact List (Recommended)
                    </label>
                    <p class="text-muted small mb-3">Group these imported contacts into a specific list (e.g. "New List", "Mining Follow-up", etc.) so you can target only this list when creating campaigns.</p>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Choose Existing List</label>
                            <select name="contact_list_id" class="form-select">
                                <option value="">-- No specific list (Master directory only) --</option>
                                @foreach($lists as $list)
                                    <option value="{{ $list->id }}">{{ $list->name }} ({{ number_format($list->contacts_count) }} contacts)</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">OR Create & Assign to a New List</label>
                            <input type="text" name="new_list_name" class="form-control" placeholder="e.g. New List, Follow Up Leads, Mining Transport">
                            <div class="form-text" style="font-size: 0.72rem;">If entered, a new list will be created and all contacts in this CSV will be added to it.</div>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Consent Source Description <span class="text-danger">*</span></label>
                    <input type="text" name="consent_source" class="form-control" required value="Website Registration & Checkout Opt-in" placeholder="e.g. Inbound Demo Requests, Customer Portal">
                </div>

                <div class="mb-4 p-3 bg-light rounded-3 border">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="consent_confirmation" id="consentCheck" required>
                        <label class="form-check-label fw-semibold text-dark small" for="consentCheck">
                            I verify and affirm that all contacts contained in this file have provided verifiable opt-in marketing permission to receive communications from our domain, conforming to RFC 8058, CAN-SPAM, GDPR, and AWS SES Acceptable Use Policies.
                        </label>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.marketing.contacts.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-brand">
                        <i class="fa-solid fa-upload me-1"></i> Start Import Process
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
