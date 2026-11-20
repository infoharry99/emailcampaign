@extends('layouts.marketing')

@section('title', 'Edit AI Campaign - ' . $campaign->name)
@section('breadcrumb', 'Campaigns / AI Personalized / Edit')
@section('page_title', 'Edit AI Campaign Parameters')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1" style="color: #0f172a;">Edit AI Campaign Settings</h4>
                <p class="text-muted small mb-0">Modify campaign name, target contact lists, topic, or description.</p>
            </div>
            <a href="{{ route('admin.marketing.ai-campaigns.show', $campaign) }}" class="btn btn-sm btn-outline-secondary" style="border-radius: 8px;">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to Review
            </a>
        </div>

        <div class="flat-card p-4">
            <form method="POST" action="{{ route('admin.marketing.ai-campaigns.update', $campaign) }}">
                @csrf
                @method('PUT')

                <!-- 1. Campaign Name -->
                <div class="mb-4">
                    <label class="form-label fw-bold text-dark">
                        1. Campaign Name <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $campaign->name) }}" required style="border-radius: 8px;">
                </div>

                <!-- 2. Select Contact List(s) -->
                <div class="mb-4">
                    <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center">
                        <span>2. Select Target Contact List(s) <span class="text-danger">*</span></span>
                    </label>

                    @php
                        $selectedLists = old('contact_list_ids', $campaign->contact_list_ids ?? []);
                    @endphp

                    <div class="p-3 bg-light rounded-3 border">
                        <div class="row g-2">
                            @foreach($lists as $list)
                                <div class="col-md-6">
                                    <div class="form-check p-2 bg-white rounded border d-flex align-items-center justify-content-between gap-2 shadow-sm">
                                        <div class="d-flex align-items-center gap-2">
                                            <input class="form-check-input ms-1" type="checkbox" name="contact_list_ids[]" value="{{ $list->id }}" id="list_{{ $list->id }}" {{ in_array($list->id, $selectedLists) ? 'checked' : '' }}>
                                            <label class="form-check-label fw-semibold text-dark small" for="list_{{ $list->id }}">
                                                📁 {{ $list->name }}
                                            </label>
                                        </div>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1" style="font-size: 0.72rem;">
                                            {{ number_format($list->contacts_count) }} leads
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- 3. Campaign Subject / Topic -->
                <div class="mb-4">
                    <label class="form-label fw-bold text-dark">
                        3. Campaign Subject / Main Topic <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="subject" class="form-control" value="{{ old('subject', $campaign->subject) }}" required style="border-radius: 8px;">
                </div>

                <!-- 4. Short Description -->
                <div class="mb-4">
                    <label class="form-label fw-bold text-dark">
                        4. Short Description & Offer Details <span class="text-danger">*</span>
                    </label>
                    <textarea name="description" class="form-control" rows="4" required style="border-radius: 8px;">{{ old('description', $campaign->description) }}</textarea>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                    <a href="{{ route('admin.marketing.ai-campaigns.show', $campaign) }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-brand">Update Campaign</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
