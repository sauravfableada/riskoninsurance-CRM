@extends('layouts.app')

@section('page_title', 'Email Marketing Templates')

@section('page_actions')
    <a href="{{ route('marketing.email_marketing_templates.create') }}" class="btn btn-dark-blue btn-sm rounded-pill px-3">
        <i class="bi bi-plus-lg me-1"></i> New Marketing Template
    </a>
    <a href="" class="btn btn-dark-blue btn-sm rounded-pill px-3">
        <i class="bi bi-plus-lg me-1"></i> Send Emails
    </a>
@endsection

@section('content')
<div class="card border-0 shadow-sm mb-4 overflow-hidden">
    <div class="card-header bg-white border-bottom-0 py-3 px-4">
        <h5 class="fw-bold mb-0">Email Marketing Templates</h5>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Name</th>
                        <th>Base Template</th>
                        <th>Status</th>
                        <th>Created By</th>
                        <th>Created At</th>
                        <th class="pe-4 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $record)
                        <tr>
                            <td class="ps-4 fw-semibold">{{ $record->name }}</td>
                            <td>{{ $record->defaultTemplate->name ?? '--' }}</td>
                            <td><span class="badge crm-status-pill rounded-pill bg-secondary text-uppercase">{{ $record->status }}</span></td>
                            <td>{{ $record->creator->name ?? '--' }}</td>
                            <td>{{ $record->created_at?->format('d M Y H:i') }}</td>
                            <td class="pe-4 text-end">
                                <a href="{{ route('marketing.email_marketing_templates.show', $record) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('marketing.email_marketing_templates.edit', $record) }}" class="btn btn-sm crm-action-btn">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('marketing.email_marketing_templates.destroy', $record) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm crm-action-btn" data-confirm-message="Delete this template?">
                                        <i class="bi bi-trash text-danger"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No email marketing templates found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3">
            {{ $records->links() }}
        </div>
    </div>
</div>
@endsection
