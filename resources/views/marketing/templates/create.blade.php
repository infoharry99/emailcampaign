@extends('layouts.marketing')

@section('title', 'Create Template')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold mb-0">Create Template</h3>
            <a href="{{ route('admin.marketing.templates.index') }}" class="btn btn-outline-secondary">Back</a>
        </div>

        <div class="card p-4">
            <form method="POST" action="{{ route('admin.marketing.templates.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-bold">Template Name</label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. Standard Follow-up Intro">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Subject Line</label>
                    <input type="text" name="subject" class="form-control" required placeholder="Quick question regarding @{{company}}">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">HTML Content</label>
                    <textarea name="content" class="form-control font-monospace" rows="10" required placeholder="<p>Hi @{{name}},</p><p>We help businesses like @{{company}}...</p>"></textarea>
                    <div class="form-text">Available Tags: <code>@{{name}}</code>, <code>@{{company}}</code>, <code>@{{email}}</code>, <code>@{{website}}</code>, <code>@{{unsubscribe_url}}</code></div>
                </div>
                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="activeCheck" checked>
                    <label class="form-check-label" for="activeCheck">Active</label>
                </div>
                <button type="submit" class="btn btn-brand">Save Template</button>
            </form>
        </div>
    </div>
</div>
@endsection
