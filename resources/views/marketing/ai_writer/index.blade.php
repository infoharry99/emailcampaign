@extends('layouts.marketing')

@section('title', 'AI Marketing Email Writer')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1"><i class="fa-solid fa-wand-magic-sparkles text-warning me-2"></i> AI Marketing Email Writer</h3>
        <p class="text-muted small mb-0">Interactive OpenAI sandbox to generate, test and refine 100-word personalized cold emails.</p>
    </div>
</div>

<div class="row g-4">
    <!-- Prompt Input Form -->
    <div class="col-md-6">
        <div class="card p-4 h-100">
            <h5 class="fw-bold border-bottom pb-2 mb-3 text-primary"><i class="fa-solid fa-sliders me-2"></i> Target Prospect & Campaign Context</h5>

            <form id="aiWriterForm">
                @csrf
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Prospect Full Name</label>
                        <input type="text" id="prospectName" class="form-control" value="Sarah Jenkins" placeholder="e.g. John Doe">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Company Name</label>
                        <input type="text" id="prospectCompany" class="form-control" value="Vertex Cloud Solutions" placeholder="e.g. Acme Corp">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Website</label>
                        <input type="text" id="prospectWebsite" class="form-control" value="https://vertexcloud.io" placeholder="https://company.com">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Industry / Business Type</label>
                        <input type="text" id="prospectType" class="form-control" value="Fintech & SaaS" placeholder="e.g. SaaS, E-commerce">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Campaign Objective / Value Proposition <span class="text-danger">*</span></label>
                    <textarea id="campaignPurpose" class="form-control" rows="3" required placeholder="What are you offering? E.g. Introducing our zero-latency API platform that cuts SES email infrastructure costs by 40% with automated reputation monitoring.">Introduce our modern marketing automation platform built on Laravel + Amazon SES, capable of handling 100,000+ monthly dispatches with guaranteed inbox placement.</textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Call to Action (CTA) details</label>
                    <input type="text" id="productDetails" class="form-control" value="Free 15-minute deliverability audit call or reply with 'AUDIT' to receive a report." placeholder="e.g. Schedule a 10 min demo call">
                </div>

                <button type="submit" id="generateBtn" class="btn btn-brand w-100 py-2">
                    <i class="fa-solid fa-wand-magic-sparkles me-2"></i> Generate AI Personalized Copy
                </button>
            </form>
        </div>
    </div>

    <!-- AI Output & Preview Panel -->
    <div class="col-md-6">
        <div class="card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                <h5 class="fw-bold mb-0 text-success"><i class="fa-solid fa-envelope-circle-check me-2"></i> Generated Output</h5>
                <span id="tokenBadge" class="badge bg-light text-dark border">Waiting for input</span>
            </div>

            <div id="loadingState" class="text-center py-5 d-none">
                <div class="spinner-border text-primary" role="status"></div>
                <div class="text-muted small mt-2">OpenAI is crafting personalized copy (~100 words)...</div>
            </div>

            <div id="outputContent">
                <div class="mb-3">
                    <label class="form-label fw-bold text-muted small">SUBJECT LINE</label>
                    <input type="text" id="resSubject" class="form-control fw-bold" readonly placeholder="Generated subject will appear here">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold text-muted small">EMAIL BODY (HTML / PREVIEW)</label>
                    <div id="resBodyPreview" class="p-3 border rounded-3 bg-light" style="min-height: 220px; font-size: 14px;">
                        <span class="text-muted">Fill out the prompt details on the left and click "Generate AI Copy" to produce a compliant, high-converting marketing email.</span>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <span id="wordCountBadge" class="small text-muted">Words: 0</span>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="copyContent()">
                        <i class="fa-regular fa-copy me-1"></i> Copy HTML
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('aiWriterForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const generateBtn = document.getElementById('generateBtn');
    const loadingState = document.getElementById('loadingState');
    const outputContent = document.getElementById('outputContent');
    const tokenBadge = document.getElementById('tokenBadge');

    generateBtn.disabled = true;
    loadingState.classList.remove('d-none');
    outputContent.classList.add('opacity-50');

    try {
        const response = await fetch("{{ route('admin.marketing.ai-writer.generate') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                prospect_name: document.getElementById('prospectName').value,
                prospect_company: document.getElementById('prospectCompany').value,
                prospect_website: document.getElementById('prospectWebsite').value,
                prospect_business_type: document.getElementById('prospectType').value,
                campaign_purpose: document.getElementById('campaignPurpose').value,
                product_details: document.getElementById('productDetails').value,
            })
        });

        const res = await response.json();

        if (res.status === 'success') {
            document.getElementById('resSubject').value = res.data.subject;
            document.getElementById('resBodyPreview').innerHTML = res.data.body_html;
            
            // Calculate word count
            const text = res.data.body_text || document.getElementById('resBodyPreview').innerText;
            const words = text.trim().split(/\s+/).filter(Boolean).length;
            document.getElementById('wordCountBadge').innerText = 'Words: ~' + words + ' (Optimized for Cold Reach)';

            const tokens = res.data.metadata.total_tokens || 0;
            const model = res.data.metadata.model || 'Local';
            tokenBadge.innerText = `${model} | ${tokens} Tokens`;
        }
    } catch (err) {
        alert('Error communicating with AI writer endpoint: ' + err.message);
    } finally {
        generateBtn.disabled = false;
        loadingState.classList.add('d-none');
        outputContent.classList.remove('opacity-50');
    }
});

function copyContent() {
    const preview = document.getElementById('resBodyPreview').innerHTML;
    navigator.clipboard.writeText(preview);
    alert('HTML content copied to clipboard.');
}
</script>
@endpush
