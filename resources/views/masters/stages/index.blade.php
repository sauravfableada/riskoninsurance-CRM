@extends('layouts.masters')

@section('page_title', 'Masters - Stages')

@push('styles')
<link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/main.css' : '/css/main.css') }}?v={{ filemtime(public_path('css/main.css')) }}">
<link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/stages.css' : '/css/stages.css') }}">
@endpush

@section('masters_content')
<div class="container-fluid p-0">
    <div class="card border-0 shadow-sm overflow-hidden stages-card">
        <div class="card-header border-bottom-0 py-3 px-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div>
                    <h4 class="fw-bold mb-0">Manage Stages</h4>
                    <p class="text-muted small mb-0">Organize your CRM workflow stages and statuses.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @can('stages.view')
                    <button type="button" class="btn btn-outline-dark-blue">
                        <i class="fa-solid fa-download me-1"></i>Export
                    </button>
                    @endcan
                    @can('stages.create')
                    <button class="btn btn-dark-blue addStageBtn">
                        <i class="bi bi-plus-lg me-1"></i>Add Stage
                    </button>
                    @endcan
                </div>
            </div>
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center stages-toolbar gap-3">
                <h6 class="fw-bold mb-0">Active Stages</h6>
                <div class="input-group input-group-sm" style="max-width: 300px; width: 100%;">
                    <span class="input-group-text crm-search-icon border-0"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control crm-search-input border-0" placeholder="Search stages..." id="stageSearch">
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 responsive-table" id="stagesTable">
                    <thead>
                        <tr>
                            <th class="ps-4" style="width: 80px;">Sr.No</th>
                            <th class="ps-4">Stage Name</th>
                            <th class="text-center d-none d-md-table-cell" style="width: 180px;">Status</th>
                            <th class="d-none d-md-table-cell" style="width: 180px;">Created At</th>
                            <th class="text-end pe-4 d-none d-md-table-cell" style="width: 140px;">Actions</th>
                            <th class="text-center d-md-none" style="width: 80px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="spinner-border text-primary"></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div id="stagesPaginationContainer" class="px-4 pb-3 pt-0"></div>
        </div>
    </div>

    <div class="modal fade" id="stageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0">
                <div class="modal-header">
                    <h5 class="modal-title" id="stageModalTitle">Add Stage</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="/api/masters/stages" id="stageForm" novalidate>
                        @csrf
                        <input type="hidden" name="_method" id="stageFormMethod">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" id="stageName" class="form-control" required>
                            <div class="invalid-feedback" id="name-error"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="stageStatus" class="form-select" required>
                                <option value="in_progress">In Progress</option>
                                <option value="paused">Paused</option>
                                <option value="completed">Completed</option>
                            </select>
                            <div class="invalid-feedback" id="status-error"></div>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-dark-blue" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-dark-blue" id="stageSubmitBtn">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
window.crmUserPermissions = {
    ...(window.crmUserPermissions || {}),
    stages: {
        view: @json(auth()->user()?->hasMatrixPermission('view_stages')),
        create: @json(auth()->user()?->hasMatrixPermission('create_stages')),
        edit: @json(auth()->user()?->hasMatrixPermission('edit_stages')),
        delete: @json(auth()->user()?->hasMatrixPermission('delete_stages')),
    }
};
</script>
<script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/js/stages.js' : '/js/stages.js') }}"></script>
@endpush
