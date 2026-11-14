@extends('layouts.marketing')

@section('title', 'OpenAI API Settings')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1"><i class="fa-solid fa-brain text-success me-2"></i> OpenAI Configuration</h3>
                <p class="text-muted small mb-0">Manage OpenAI API keys, model selections, temperature and token consumption limits.</p>
            </div>
            <span class="badge bg-light text-dark border p-2">
                Total Used: ${{ number_format($settings['openai_total_cost_usd'], 4) }} USD
            </span>
        </div>

        <div class="card p-4">
            <form method="POST" action="{{ route('admin.marketing.settings.openai.update') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-bold">OpenAI Secret API Key</label>
                    <input type="password" name="openai_api_key" class="form-control" value="{{ $settings['openai_api_key'] ? '••••••••••••••••' : '' }}" placeholder="sk-proj-...">
                    <div class="form-text">Stored securely in your database/env settings and never exposed to the frontend.</div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Model</label>
                        <select name="openai_model" class="form-select" required>
                            <option value="gpt-4o-mini" {{ $settings['openai_model'] === 'gpt-4o-mini' ? 'selected' : '' }}>GPT-4o Mini (Fast, Cost-Effective & Recommended)</option>
                            <option value="gpt-4o" {{ $settings['openai_model'] === 'gpt-4o' ? 'selected' : '' }}>GPT-4o (Maximum Nuance & Sophistication)</option>
                            <option value="gpt-3.5-turbo" {{ $settings['openai_model'] === 'gpt-3.5-turbo' ? 'selected' : '' }}>GPT-3.5 Turbo</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Max Tokens</label>
                        <input type="number" name="openai_max_tokens" class="form-control" value="{{ $settings['openai_max_tokens'] }}" required>
                        <div class="form-text">~450 tokens is ideal for 100-word emails.</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Temperature</label>
                        <input type="number" step="0.1" min="0" max="1" name="openai_temperature" class="form-control" value="{{ $settings['openai_temperature'] }}" required>
                        <div class="form-text">0.7 yields optimal creativity.</div>
                    </div>
                </div>

                <div class="p-3 bg-light rounded-3 border mb-4">
                    <h6 class="fw-bold mb-2">Usage Statistics</h6>
                    <div class="row text-center">
                        <div class="col-6 border-end">
                            <span class="text-muted small">Total Tokens Processed</span>
                            <h4 class="fw-bold my-1 text-primary">{{ number_format($settings['openai_total_tokens_used']) }}</h4>
                        </div>
                        <div class="col-6">
                            <span class="text-muted small">Estimated Expenditure</span>
                            <h4 class="fw-bold my-1 text-success">${{ number_format($settings['openai_total_cost_usd'], 4) }}</h4>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-brand">Save OpenAI Settings</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
