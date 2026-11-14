@extends('layouts.marketing')

@section('title', 'Create Template')

@push('scripts')
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
                <h3 class="fw-bold mb-1">Create Email Template</h3>
                <p class="text-muted small mb-0">Design rich reusable templates with dynamic recipient tags.</p>
            </div>
            <a href="{{ route('admin.marketing.templates.index') }}" class="btn btn-outline-secondary">
                <i class="fa-solid fa-arrow-left me-1"></i> Back
            </a>
        </div>

        <div class="card p-4">
            <form method="POST" action="{{ route('admin.marketing.templates.store') }}" onsubmit="syncEditorContent();">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-bold">Template Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. Standard Follow-up Intro" value="{{ old('name') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Subject Line <span class="text-danger">*</span></label>
                    <input type="text" name="subject" class="form-control" required placeholder="Quick question regarding @{{company}}" value="{{ old('subject') }}">
                </div>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label fw-bold mb-0">Template Content (Rich Text Editor)</label>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" id="toggleSourceBtn" style="font-size: 0.76rem; border-radius: 6px;">
                            <i class="fa-solid fa-code me-1"></i> HTML Source
                        </button>
                    </div>

                    <!-- Variable chips -->
                    <div class="d-flex flex-wrap align-items-center gap-1 mb-2 p-2 rounded border" style="background-color: #f8fafc;">
                        <span class="small fw-bold text-muted me-2" style="font-size: 0.75rem;">
                            <i class="fa-solid fa-bolt text-warning me-1"></i>Insert:
                        </span>
                        <button type="button" class="var-badge-btn" data-tag="@{{name}}">+ Name</button>
                        <button type="button" class="var-badge-btn" data-tag="@{{company}}">+ Company</button>
                        <button type="button" class="var-badge-btn" data-tag="@{{email}}">+ Email</button>
                        <button type="button" class="var-badge-btn" data-tag="@{{website}}">+ Website</button>
                        <button type="button" class="var-badge-btn" data-tag="@{{unsubscribe_url}}">+ Unsubscribe Link</button>
                    </div>

                    <!-- Quill Visual Editor -->
                    <div id="quillEditorWrapper">
                        <div id="quillEditor" style="min-height: 240px; background-color: #ffffff;">{!! old('content', '<p>Hi {{name}},</p><p>We would love to connect with {{company}} regarding our latest solutions.</p>') !!}</div>
                    </div>

                    <!-- Raw HTML Textarea -->
                    <textarea name="content" id="templateContent" class="form-control font-monospace d-none" rows="10" required>{{ old('content', '<p>Hi {{name}},</p><p>We would love to connect with {{company}} regarding our latest solutions.</p>') }}</textarea>
                </div>

                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="activeCheck" checked>
                    <label class="form-check-label" for="activeCheck">Active (Available for campaigns)</label>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.marketing.templates.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-brand">Save Template</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const quill = new Quill('#quillEditor', {
        theme: 'snow',
        placeholder: 'Design your email template here...',
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
});
</script>
@endpush
@endsection
