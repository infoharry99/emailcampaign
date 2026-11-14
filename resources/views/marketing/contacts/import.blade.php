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
                        Supported columns: <code>name</code>, <code>email</code> (required), <code>company</code>, <code>website</code>, <code>phone</code>, <code>business_type</code>.
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
