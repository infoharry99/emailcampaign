@extends('layouts.marketing')

@section('title', 'Edit Contact List')
@section('breadcrumb', 'Audience / Lists')
@section('page_title', 'Edit List: ' . $list->name)

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
                <i class="fa-solid fa-pen-to-square me-2"></i> Edit List Details
            </h5>

            <form method="POST" action="{{ route('admin.marketing.lists.update', $list) }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label fw-semibold">List Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required value="{{ old('name', $list->name) }}">
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Description (Optional)</label>
                    <textarea name="description" rows="3" class="form-control">{{ old('description', $list->description) }}</textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.marketing.lists.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-brand">Update List</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
