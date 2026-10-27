@extends('layouts.app')

@section('page_title', 'Configuration - Custom Fields')

@section('content')
<div class="container-fluid">
    {{-- Consistent CRM Header Section --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-1">Custom Fields Form Module Builder</h1>
            <p class="text-muted small mb-0">Management of dynamic information fields across your CRM modules.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary btn-sm">Settings</a>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4">
            <div class="row align-items-end g-3">
                <div class="col-md-5">
                    <label class="form-label fw-bold small text-muted text-uppercase mb-2">
                        <i class="bi bi-filter-square me-1"></i>Select Module
                    </label>
                    <select class="form-select border-2" onchange="window.location.href='{{ route('settings.custom-fields.index') }}?module=' + this.value">
                        <option value="">-- All Modules --</option>
                        @foreach($modules as $m)
                            <option value="{{ $m }}" {{ $module == $m ? 'selected' : '' }}>{{ $m }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-auto">
                    <a href="{{ route('settings.custom-fields.create', ['module' => $module ?? 'Lead']) }}" class="btn btn-indigo btn-lg px-5 py-2 fw-bold text-white w-100">
                        <i class="bi bi-plus-lg me-2"></i>Add Fields
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="h6 mb-0 fw-bold text-dark">All Custom Forms List</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr class="small text-uppercase fw-bold text-muted border-bottom">
                            <th class="ps-4 py-3" style="width: 80px;">Sr.No</th>
                            <th class="py-3">Module Name</th>
                            <th class="py-3">Form Title</th>
                            <th class="py-3">Created Date</th>
                            <th class="text-end pe-4 py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($fields as $index => $field)
                            <tr class="border-bottom">
                                <td class="ps-4 text-muted small fw-medium">{{ $index + 1 }}</td>
                                <td>
                                    <span class="badge bg-light text-indigo border border-indigo-subtle px-3 py-2 rounded-pill small fw-bold">
                                        {{ $field->module }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $field->label }}</div>
                                    <div class="extra-small text-muted mt-1">ID: <code>{{ $field->name }}</code> | Type: {{ ucfirst($field->type) }}</div>
                                </td>
                                <td class="text-muted small">
                                    {{ $field->created_at ? $field->created_at->format('d M Y h:i A') : '--' }}
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="{{ route('settings.custom-fields.edit', $field) }}" class="btn btn-sm crm-action-btn border-0 rounded-circle" title="Edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <form action="{{ route('settings.custom-fields.destroy', $field) }}" method="POST" class="d-inline" data-confirm-message="Delete this custom field?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm crm-action-btn border-0 rounded-circle" title="Delete">
                                                <i class="bi bi-trash text-danger"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="py-5">
                                        <i class="bi bi-list-task text-light display-1 mb-4"></i>
                                        <h5 class="text-secondary fw-normal">No custom fields found.</h5>
                                        <p class="text-muted small">Choose a module or add a new field to get started.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($fields->count() > 0)
            <div class="card-footer bg-white border-0 py-3 px-4">
                <div class="d-flex justify-content-between align-items-center small text-muted">
                    <div>Showing 1 to {{ $fields->count() }} of {{ $fields->count() }} entries</div>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item disabled"><a class="page-link" href="#"><i class="bi bi-chevron-left"></i></a></li>
                            <li class="page-item active"><a class="page-link" href="#">1</a></li>
                            <li class="page-item disabled"><a class="page-link" href="#"><i class="bi bi-chevron-right"></i></a></li>
                        </ul>
                    </nav>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<style>
    .btn-indigo { background: #1a237e; border-color: #1a237e; }
    .btn-indigo:hover { background: #0d124a; border-color: #0d124a; }
    .text-indigo { color: #1a237e !important; }
    .border-indigo-subtle { border-color: #c5cae9 !important; }
    .bg-light { background: #f8fafc !important; }
    .extra-small { font-size: 0.7rem; }
    .table thead th { border-bottom: none !important; font-size: 0.75rem; color: #64748b; }
    .page-link { border: 1px solid #e2e8f0; color: #64748b; }
    .page-item.active .page-link { background: #1a237e; border-color: #1a237e; }
</style>
@endsection
