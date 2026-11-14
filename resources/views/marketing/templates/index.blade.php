@extends('layouts.marketing')

@section('title', 'Email Templates')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1">Email Templates</h3>
        <p class="text-muted small mb-0">Create and manage reusable HTML marketing templates with merge tags.</p>
    </div>
    <a href="{{ route('admin.marketing.templates.create') }}" class="btn btn-brand">
        <i class="fa-solid fa-plus-circle me-1"></i> New Template
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Template Name</th>
                    <th>Default Subject</th>
                    <th>Status</th>
                    <th>Last Updated</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($templates as $tmpl)
                <tr>
                    <td class="fw-bold">{{ $tmpl->name }}</td>
                    <td>{{ $tmpl->subject }}</td>
                    <td>
                        <span class="badge {{ $tmpl->is_active ? 'bg-success' : 'bg-secondary' }}">
                            {{ $tmpl->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="text-muted small">{{ $tmpl->updated_at->format('M d, Y') }}</td>
                    <td class="text-end">
                        <a href="{{ route('admin.marketing.templates.edit', $tmpl) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fa-solid fa-pen-to-square"></i> Edit
                        </a>
                        <form method="POST" action="{{ route('admin.marketing.templates.destroy', $tmpl) }}" class="d-inline" onsubmit="return confirm('Delete template?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-4 text-muted">No templates found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
