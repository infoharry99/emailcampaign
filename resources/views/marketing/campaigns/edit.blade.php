@extends('layouts.marketing')

@section('title', 'Edit Campaign - ' . $campaign->name)
@section('breadcrumb', 'Campaigns / Edit')
@section('page_title', 'Edit Campaign')

@push('scripts')
<!-- Quill Rich Text Editor CDN -->
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<style>
    .ql-toolbar.ql-snow {
        border-top-left-radius: 8px;
        border-top-right-radius: 8px;
        background-color: #f8fafc;
        border-color: #cbd5e1;
    }
    .ql-container.ql-snow {
        border-bottom-left-radius: 8px;
        border-bottom-right-radius: 8px;
        border-color: #cbd5e1;
        font-family: inherit;
        font-size: 0.92rem;
    }
    .var-badge-btn {
        background-color: #ffffff;
        border: 1px solid #cbd5e1;
        color: #334155;
        font-size: 0.76rem;
        font-weight: 600;
        border-radius: 6px;
        padding: 3px 8px;
        cursor: pointer;
        transition: all 0.15s ease-in-out;
    }
    .var-badge-btn:hover {
        background-color: #eff6ff;
        color: #2563eb;
        border-color: #93c5fd;
    }
</style>
@endpush

@section('content')
<div class="row justify-content-center">
    <div class="col-md-9">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1" style="color: #0f172a;">Edit Campaign Details</h4>
                <p class="text-muted small mb-0">Update campaign settings, objectives, and sending criteria.</p>
            </div>
            <a href="{{ route('admin.marketing.campaigns.show', $campaign) }}" class="btn btn-sm btn-outline-secondary" style="border-radius: 8px;">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to Report
            </a>
        </div>

        <div class="flat-card p-4">
            <form method="POST" action="{{ route('admin.marketing.campaigns.update', $campaign) }}" enctype="multipart/form-data" id="editCampaignForm" onsubmit="syncEditorContent(); const btn = this.querySelector('button[type=submit]'); if(btn) { btn.disabled = true; btn.innerHTML = '<i class=\'fa-solid fa-spinner fa-spin me-1\'></i> Saving...'; }">
                @csrf
                @method('PUT')

                <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary"><i class="fa-solid fa-circle-info me-2"></i> Campaign Identity</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold" style="font-size: 0.8rem;">Campaign Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $campaign->name) }}" required style="border-radius: 8px; border-color: #e2e8f0;">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size: 0.8rem;">Sending Mode <span class="text-danger">*</span></label>
                        <select name="sending_mode" class="form-select" required style="border-radius: 8px; border-color: #e2e8f0;">
                            <option value="ai" {{ old('sending_mode', $campaign->sending_mode) === 'ai' ? 'selected' : '' }}>AI Generated</option>
                            <option value="template" {{ old('sending_mode', $campaign->sending_mode) === 'template' ? 'selected' : '' }}>Template</option>
                            <option value="both" {{ old('sending_mode', $campaign->sending_mode) === 'both' ? 'selected' : '' }}>Both (AI + Manual Edit)</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold" style="font-size: 0.8rem;">Campaign Description</label>
                        <textarea name="description" class="form-control" rows="2" style="border-radius: 8px; border-color: #e2e8f0;">{{ old('description', $campaign->description) }}</textarea>
                    </div>
                </div>

                <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary"><i class="fa-solid fa-wand-magic-sparkles me-2 text-warning"></i> AI Writer Objective</h6>
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <label class="form-label fw-semibold" style="font-size: 0.8rem;">Campaign Purpose / Objective <span class="text-danger">*</span></label>
                        <textarea name="purpose" class="form-control" rows="3" required style="border-radius: 8px; border-color: #e2e8f0;">{{ old('purpose', $campaign->purpose) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold" style="font-size: 0.8rem;">Product / Service Details</label>
                        <textarea name="product_service_details" class="form-control" rows="2" style="border-radius: 8px; border-color: #e2e8f0;">{{ old('product_service_details', $campaign->product_service_details) }}</textarea>
                    </div>
                </div>

                <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary"><i class="fa-solid fa-envelope me-2"></i> Content & Targeting</h6>
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <label class="form-label fw-semibold" style="font-size: 0.8rem;">Subject Line</label>
                        <input type="text" name="subject" class="form-control" value="{{ old('subject', $campaign->subject) }}" style="border-radius: 8px; border-color: #e2e8f0;">
                    </div>
                    <div class="col-12" id="templateContentGroup">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-semibold mb-0" style="font-size: 0.8rem;">
                                <i class="fa-solid fa-pen-nib text-primary me-1"></i> Template Content (Rich Text Editor)
                            </label>
                            <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" id="toggleSourceBtn" style="font-size: 0.76rem; border-radius: 6px;">
                                <i class="fa-solid fa-code me-1"></i> HTML Source
                            </button>
                        </div>

                        <!-- Quick Insert Variable Chips -->
                        <div class="d-flex flex-wrap align-items-center gap-1 mb-2 p-2 rounded border" style="background-color: #f8fafc;">
                            <span class="small fw-bold text-muted me-2" style="font-size: 0.75rem;">
                                <i class="fa-solid fa-bolt text-warning me-1"></i>Insert Placeholder:
                            </span>
                            <button type="button" class="var-badge-btn" data-tag="@{{name}}">+ Name</button>
                            <button type="button" class="var-badge-btn" data-tag="@{{company}}">+ Company</button>
                            <button type="button" class="var-badge-btn" data-tag="@{{email}}">+ Email</button>
                            <button type="button" class="var-badge-btn" data-tag="@{{website}}">+ Website</button>
                            <button type="button" class="var-badge-btn" data-tag="@{{unsubscribe_url}}">+ Unsubscribe Link</button>
                        </div>

                        <!-- Quill Visual Editor -->
                        <div id="quillEditorWrapper">
                            <div id="quillEditor" style="min-height: 220px; background-color: #ffffff;">{!! old('content', $campaign->content) !!}</div>
                        </div>

                        <!-- Raw HTML Textarea (Toggled via HTML Source button) -->
                        <textarea name="content" id="templateContent" class="form-control font-monospace d-none" rows="8" placeholder="<p>Hi @{{name}},</p>">{{ old('content', $campaign->content) }}</textarea>
                    </div>

                    <!-- Email Attachments (Existing & New) -->
                    <div class="col-12 mt-3">
                        <label class="form-label fw-semibold" style="font-size: 0.8rem;">
                            <i class="fa-solid fa-paperclip text-primary me-1"></i> Email Attachments
                        </label>

                        @if(!empty($campaign->attachments))
                            <div class="p-3 mb-2 rounded border bg-light">
                                <span class="small fw-bold text-dark d-block mb-2">Current Attached Files:</span>
                                <div class="d-flex flex-column gap-2">
                                    @foreach($campaign->attachments as $idx => $att)
                                        <div class="d-flex align-items-center justify-content-between p-2 bg-white rounded border">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="fa-solid fa-file text-primary"></i>
                                                <span class="fw-semibold small">{{ $att['name'] ?? 'Attachment' }}</span>
                                                <small class="text-muted">({{ number_format(($att['size'] ?? 0) / 1024, 1) }} KB)</small>
                                            </div>
                                            <div class="form-check mb-0">
                                                <input class="form-check-input" type="checkbox" name="remove_attachments[]" value="{{ $idx }}" id="remove_att_{{ $idx }}">
                                                <label class="form-check-label text-danger small" for="remove_att_{{ $idx }}">
                                                    <i class="fa-solid fa-trash me-1"></i> Remove
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <label class="form-label text-muted small mb-1">Add More Files (Multiple allowed):</label>
                        <input type="file" name="attachments[]" id="campaignAttachments" class="form-control form-control-sm" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.png,.jpg,.jpeg,.gif,.zip,.csv" style="border-radius: 8px;">
                        <div class="form-text" style="font-size: 0.72rem;">Attach PDFs, proposals, images, etc. Hold <kbd>Ctrl</kbd> to select multiple files. (Max 20MB per file).</div>
                        <div id="filePreviewList" class="d-flex flex-wrap gap-2 mt-2"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size: 0.8rem;">Target Contacts</label>
                        <select name="target_criteria" class="form-select" style="border-radius: 8px; border-color: #e2e8f0;">
                            <option value="all" {{ old('target_criteria', $campaign->target_criteria) === 'all' ? 'selected' : '' }}>All Verified & Subscribed Contacts</option>
                            @if(isset($lists) && $lists->count() > 0)
                                <optgroup label="📁 Custom Contact Lists">
                                    @foreach($lists as $list)
                                        <option value="list:{{ $list->id }}" {{ old('target_criteria', $campaign->target_criteria) === 'list:' . $list->id ? 'selected' : '' }}>
                                            {{ $list->name }} ({{ number_format($list->contacts_count) }} contacts)
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endif
                            <optgroup label="🏢 Filter by Industry">
                                <option value="business_type:Technology" {{ old('target_criteria', $campaign->target_criteria) === 'business_type:Technology' ? 'selected' : '' }}>Industry: Technology</option>
                                <option value="business_type:Enterprise" {{ old('target_criteria', $campaign->target_criteria) === 'business_type:Enterprise' ? 'selected' : '' }}>Industry: Enterprise</option>
                                <option value="business_type:Logistics" {{ old('target_criteria', $campaign->target_criteria) === 'business_type:Logistics' ? 'selected' : '' }}>Industry: Logistics</option>
                            </optgroup>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.marketing.campaigns.show', $campaign) }}" class="btn btn-sm btn-outline-secondary" style="border-radius: 8px;">Cancel</a>
                    <button type="submit" class="btn btn-sm btn-dark" style="border-radius: 8px; padding: 6px 18px;">Update Campaign</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Initialize Quill Editor
    const quill = new Quill('#quillEditor', {
        theme: 'snow',
        placeholder: 'Write your email template content here...',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'color': [] }, { 'background': [] }],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'align': [] }],
                ['link', 'blockquote', 'code-block'],
                ['clean']
            ]
        }
    });

    const contentTextarea = document.getElementById('templateContent');
    const editorWrapper = document.getElementById('quillEditorWrapper');
    const toggleSourceBtn = document.getElementById('toggleSourceBtn');
    let isSourceMode = false;

    // Synchronize Quill content to textarea
    window.syncEditorContent = function () {
        if (!isSourceMode) {
            contentTextarea.value = quill.root.innerHTML;
        }
    };

    quill.on('text-change', function () {
        if (!isSourceMode) {
            contentTextarea.value = quill.root.innerHTML;
        }
    });

    // 2. Toggle Visual / Source mode
    toggleSourceBtn.addEventListener('click', function () {
        isSourceMode = !isSourceMode;
        if (isSourceMode) {
            contentTextarea.value = quill.root.innerHTML;
            editorWrapper.classList.add('d-none');
            contentTextarea.classList.remove('d-none');
            toggleSourceBtn.innerHTML = '<i class="fa-solid fa-pen-to-square me-1"></i> Visual Editor';
            toggleSourceBtn.classList.replace('btn-outline-secondary', 'btn-primary');
        } else {
            quill.root.innerHTML = contentTextarea.value;
            contentTextarea.classList.add('d-none');
            editorWrapper.classList.remove('d-none');
            toggleSourceBtn.innerHTML = '<i class="fa-solid fa-code me-1"></i> HTML Source';
            toggleSourceBtn.classList.replace('btn-primary', 'btn-outline-secondary');
        }
    });

    // 3. Quick Insert Variable Buttons
    document.querySelectorAll('.var-badge-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const tag = this.getAttribute('data-tag');
            if (isSourceMode) {
                const start = contentTextarea.selectionStart;
                const end = contentTextarea.selectionEnd;
                const val = contentTextarea.value;
                contentTextarea.value = val.substring(0, start) + tag + val.substring(end);
                contentTextarea.focus();
            } else {
                const range = quill.getSelection(true);
                quill.insertText(range ? range.index : 0, tag);
                if (range) {
                    quill.setSelection(range.index + tag.length);
                }
            }
        });
    });

    // 4. Attachments Live Preview
    const attachmentsInput = document.getElementById('campaignAttachments');
    const filePreviewList = document.getElementById('filePreviewList');
    if (attachmentsInput && filePreviewList) {
        attachmentsInput.addEventListener('change', function () {
            filePreviewList.innerHTML = '';
            if (this.files.length > 0) {
                Array.from(this.files).forEach((file, idx) => {
                    const sizeKB = (file.size / 1024).toFixed(1);
                    const pill = document.createElement('div');
                    pill.className = 'badge bg-white text-dark border p-2 d-inline-flex align-items-center gap-2 shadow-sm';
                    pill.style.borderRadius = '6px';
                    pill.innerHTML = `
                        <i class="fa-solid fa-paperclip text-primary"></i>
                        <span><strong>${file.name}</strong> <small class="text-muted">(${sizeKB} KB)</small></span>
                    `;
                    filePreviewList.appendChild(pill);
                });
            }
        });
    }
});
</script>
@endpush
@endsection
