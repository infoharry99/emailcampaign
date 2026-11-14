@extends('layouts.marketing')

@section('title', 'Edit Template - ' . $template->name)
@section('breadcrumb', 'Templates / Edit')
@section('page_title', 'Edit Email Template')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0" style="color: #0f172a;">Edit Template</h4>
            <a href="{{ route('admin.marketing.templates.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius: 8px;">Back</a>
        </div>

        <div class="flat-card p-4">
            <form method="POST" action="{{ route('admin.marketing.templates.update', $template) }}">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size: 0.82rem;">Template Name</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $template->name) }}" required style="border-radius: 8px; border-color: #e2e8f0;">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size: 0.82rem;">Subject Line</label>
                    <input type="text" name="subject" class="form-control" value="{{ old('subject', $template->subject) }}" required style="border-radius: 8px; border-color: #e2e8f0;">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size: 0.82rem;">HTML Content</label>
                    <textarea name="content" class="form-control font-monospace" rows="10" required style="border-radius: 8px; border-color: #e2e8f0;">{{ old('content', $template->content) }}</textarea>
                    <div class="form-text mt-1">Available Tags: <code>@{{name}}</code>, <code>@{{company}}</code>, <code>@{{email}}</code>, <code>@{{website}}</code>, <code>@{{unsubscribe_url}}</code></div>
                </div>
                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="activeCheck" {{ $template->is_active ? 'checked' : '' }}>
                    <label class="form-check-label" for="activeCheck">Active</label>
                </div>
                <button type="submit" class="btn btn-sm btn-dark" style="border-radius: 8px; padding: 6px 18px;">Update Template</button>
            </form>
        </div>
    </div>
</div>
@endsection
