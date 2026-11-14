@extends('layouts.marketing')

@section('title', 'Amazon SES Settings')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1"><i class="fa-brands fa-aws text-warning me-2"></i> Amazon SES Settings</h3>
                <p class="text-muted small mb-0">Configure your AWS SES credentials, verified sending identity and rate limits.</p>
            </div>
            <span class="badge {{ $sesStats['connected'] ? 'bg-success' : 'bg-warning text-dark' }} p-2">
                {{ $sesStats['connected'] ? 'Connected' : 'Credentials Needed' }}
            </span>
        </div>

        <div class="card p-4">
            <form method="POST" action="{{ route('admin.marketing.settings.ses.update') }}">
                @csrf

                <h5 class="fw-bold border-bottom pb-2 mb-3 text-primary"><i class="fa-solid fa-key me-2"></i> AWS Authentication</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">AWS Access Key ID</label>
                        <input type="text" name="aws_access_key_id" class="form-control" value="{{ $settings['aws_access_key_id'] }}" placeholder="AKIAIOSFODNN7EXAMPLE">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">AWS Secret Access Key</label>
                        <input type="password" name="aws_secret_access_key" class="form-control" value="{{ $settings['aws_secret_access_key'] ? '••••••••••••••••' : '' }}" placeholder="Enter secret key">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">AWS Region</label>
                        <input type="text" name="aws_region" class="form-control" value="{{ $settings['aws_region'] }}" required>
                        <div class="form-text">e.g. <code>eu-north-1</code> (Stockholm), <code>ap-south-1</code> (Mumbai)</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">SES Configuration Set (Optional)</label>
                        <input type="text" name="aws_ses_configuration_set" class="form-control" value="{{ $settings['aws_ses_configuration_set'] }}" placeholder="Leave blank if not created yet">
                        <div class="form-text">e.g. <code>marketing-tracking</code> (optional in testing)</div>
                    </div>
                </div>

                <h5 class="fw-bold border-bottom pb-2 mb-3 text-primary"><i class="fa-solid fa-envelope-circle-check me-2"></i> Sender Identity & Domain</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Sender Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="aws_ses_from_address" class="form-control" value="{{ $settings['aws_ses_from_address'] }}" required>
                        <div class="form-text">e.g. <code>noreply@marketing.thenexteck.com</code></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Sender Display Name <span class="text-danger">*</span></label>
                        <input type="text" name="aws_ses_from_name" class="form-control" value="{{ $settings['aws_ses_from_name'] }}" required>
                        <div class="form-text">e.g. <code>TheNexteck Marketing</code></div>
                    </div>
                </div>

                <h5 class="fw-bold border-bottom pb-2 mb-3 text-primary"><i class="fa-solid fa-gauge-high me-2"></i> Queue Rate Throttling</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Sending Rate (emails / minute)</label>
                        <input type="number" step="1" min="1" max="5000" name="aws_ses_sending_rate" class="form-control" value="{{ $settings['aws_ses_sending_rate'] }}" required>
                        <div class="form-text">Configure maximum emails sent per minute (e.g. <code>60</code> = 1/sec, <code>30</code> = 1 every 2s, <code>10</code> = 1 every 6s).</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Daily Sending Quota</label>
                        <input type="number" name="aws_ses_daily_quota" class="form-control" value="{{ $settings['aws_ses_daily_quota'] }}" required>
                        <div class="form-text">e.g. 50,000 emails per 24 hours</div>
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-brand">Save AWS SES Settings</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
