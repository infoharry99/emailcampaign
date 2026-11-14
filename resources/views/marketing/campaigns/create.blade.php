@extends('layouts.marketing')

@section('title', 'Create Marketing Campaign')

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
                <h3 class="fw-bold mb-1">Create Campaign</h3>
                <p class="text-muted small mb-0">Configure your campaign objectives, choose AI or Template mode, and schedule dispatches.</p>
            </div>
            <a href="{{ route('admin.marketing.campaigns.index') }}" class="btn btn-outline-secondary">
                <i class="fa-solid fa-arrow-left me-1"></i> Back
            </a>
        </div>

        <div class="card p-4">
            <form method="POST" action="{{ route('admin.marketing.campaigns.store') }}" enctype="multipart/form-data" id="campaignForm" onsubmit="syncEditorContent(); const btn = this.querySelector('button[type=submit]'); if(btn) { btn.disabled = true; btn.innerHTML = '<i class=\'fa-solid fa-spinner fa-spin me-1\'></i> Saving...'; }">
                @csrf

                <!-- Basic Information -->
                <h5 class="fw-bold border-bottom pb-2 mb-3 text-primary"><i class="fa-solid fa-circle-info me-2"></i> Campaign Identity</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Campaign Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Q4 Cloud Infrastructure Consultation" value="{{ old('name') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Sending Mode <span class="text-danger">*</span></label>
                        <select name="sending_mode" id="sendingModeSelect" class="form-select" required>
                            <option value="ai" selected>AI Generated (OpenAI Personalized per contact)</option>
                            <option value="template">Template (Static HTML with Dynamic Tags)</option>
                            <option value="both">Both (AI Initial Draft + Manual Editing)</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Campaign Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Internal campaign notes and target demographic info...">{{ old('description') }}</textarea>
                    </div>
                </div>

                <!-- AI Prompt Details -->
                <div id="aiPromptSection">
                    <h5 class="fw-bold border-bottom pb-2 mb-3 text-primary"><i class="fa-solid fa-wand-magic-sparkles me-2 text-warning"></i> AI Writer Objective & Details</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Campaign Purpose / Objective <span class="text-danger">*</span></label>
                            <textarea name="purpose" class="form-control" rows="3" required placeholder="Describe the goal of this email. Example: Introduce our managed cloud services, highlight cost savings on AWS/SES, and invite recipient for a 15-min discovery call.">{{ old('purpose') }}</textarea>
                            <div class="form-text">OpenAI will use this goal to generate a ~100-word concise, high-converting email for each prospect.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Product or Service Details</label>
                            <textarea name="product_service_details" class="form-control" rows="2" placeholder="Features, special advantages, or consultation offer details...">{{ old('product_service_details') }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Template & Content -->
                <h5 class="fw-bold border-bottom pb-2 mb-3 text-primary"><i class="fa-solid fa-envelope me-2"></i> Email Subject & Content</h5>
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Default Subject Line</label>
                        <input type="text" name="subject" class="form-control" placeholder="e.g. Accelerating Tech Growth for @{{company}}" value="{{ old('subject') }}">
                        <div class="form-text">For AI mode, OpenAI will generate customized subjects if left empty or use this as guidance.</div>
                    </div>
                    <div class="col-12" id="templateContentGroup">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-semibold mb-0">
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
                        @php
                            $initialContent = old('content', '<p>Hi {{name}},</p><p>We help businesses like {{company}} scale with high deliverability.</p>');
                        @endphp
                        <div id="quillEditorWrapper">
                            <div id="quillEditor" style="min-height: 220px; background-color: #ffffff;">{!! $initialContent !!}</div>
                        </div>

                        <!-- Raw HTML Textarea (Toggled via HTML Source button) -->
                        <textarea name="content" id="templateContent" class="form-control font-monospace d-none" rows="8" placeholder="<p>Hi @{{name}},</p>">{{ $initialContent }}</textarea>
                    </div>

                    <!-- Email Attachments (Multiple) -->
                    <div class="col-12 mt-3">
                        <label class="form-label fw-semibold">
                            <i class="fa-solid fa-paperclip text-primary me-1"></i> Email Attachments (Optional, Multiple Files Allowed)
                        </label>
                        <input type="file" name="attachments[]" id="campaignAttachments" class="form-control" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.png,.jpg,.jpeg,.gif,.zip,.csv">
                        <div class="form-text">Attach proposals, catalogues, presentations, or documents. Hold <kbd>Ctrl</kbd> or <kbd>Shift</kbd> to select multiple files at once. (Max 20MB per file).</div>
                        <div id="filePreviewList" class="d-flex flex-wrap gap-2 mt-2"></div>
                    </div>
                </div>

                <!-- Audience Targeting & Scheduling -->
                <h5 class="fw-bold border-bottom pb-2 mb-3 text-primary"><i class="fa-solid fa-sliders me-2"></i> Target Audience & Schedule</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Target Contacts <span class="text-danger">*</span></label>
                        <select name="target_criteria" class="form-select" required>
                            <option value="all" {{ (old('target_criteria', $defaultTarget ?? '') === 'all') ? 'selected' : '' }}>All Verified & Subscribed Contacts ({{ number_format($totalEligibleContacts) }} leads)</option>
                            @if(isset($lists) && $lists->count() > 0)
                                <optgroup label="📁 Custom Contact Lists">
                                    @foreach($lists as $list)
                                        <option value="list:{{ $list->id }}" {{ (old('target_criteria', $defaultTarget ?? '') === 'list:' . $list->id) ? 'selected' : '' }}>
                                            {{ $list->name }} ({{ number_format($list->contacts_count) }} contacts)
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endif
                            <optgroup label="🏢 Filter by Industry">
                                <option value="business_type:Technology" {{ (old('target_criteria', $defaultTarget ?? '') === 'business_type:Technology') ? 'selected' : '' }}>Industry: Technology</option>
                                <option value="business_type:Enterprise" {{ (old('target_criteria', $defaultTarget ?? '') === 'business_type:Enterprise') ? 'selected' : '' }}>Industry: Enterprise</option>
                                <option value="business_type:Retail" {{ (old('target_criteria', $defaultTarget ?? '') === 'business_type:Retail') ? 'selected' : '' }}>Industry: Retail</option>
                            </optgroup>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Sending Date (Optional)</label>
                        <input type="date" name="sending_date" class="form-control" value="{{ old('sending_date') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Sending Time (Optional)</label>
                        <input type="time" name="sending_time" class="form-control" value="{{ old('sending_time') }}">
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.marketing.campaigns.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-brand">Save Campaign</button>
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

    // 5. Toggle Template vs AI Section based on Sending Mode
    const sendingModeSelect = document.getElementById('sendingModeSelect');
    const templateContentGroup = document.getElementById('templateContentGroup');
    const aiPromptSection = document.getElementById('aiPromptSection');

    function updateModeVisibility() {
        const mode = sendingModeSelect.value;
        if (mode === 'template') {
            aiPromptSection.classList.add('d-none');
            const purposeField = document.querySelector('textarea[name=purpose]');
            if (purposeField) purposeField.removeAttribute('required');
        } else {
            aiPromptSection.classList.remove('d-none');
            const purposeField = document.querySelector('textarea[name=purpose]');
            if (purposeField) purposeField.setAttribute('required', 'required');
        }
    }

    sendingModeSelect.addEventListener('change', updateModeVisibility);
    updateModeVisibility();
});
</script>
@endpush
@endsection
