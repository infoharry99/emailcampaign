@extends('layouts.marketing')

@section('title', 'Create Contact List')
@section('breadcrumb', 'Audience / Lists')
@section('page_title', 'Create Contact List')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <a href="{{ route('admin.marketing.lists.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to Lists
            </a>
        </div>

        <div class="card p-4">
            <h5 class="fw-bold border-bottom pb-2 mb-3 text-primary">
                <i class="fa-solid fa-folder-plus me-2"></i> List Information
            </h5>

            <form method="POST" action="{{ route('admin.marketing.lists.store') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-semibold">List Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. New List, Follow-up List, Tech CEOs, Q4 Prospects" value="{{ old('name') }}">
                    <div class="form-text">Choose a descriptive name to easily select this target in campaigns.</div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Description (Optional)</label>
                    <textarea name="description" rows="3" class="form-control" placeholder="Target audience notes, criteria, or purpose...">{{ old('description') }}</textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.marketing.lists.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-brand">Create List</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
