@extends('layouts.app')

@section('page_title', 'Pipeline')

@push('styles')
<link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/main.css' : '/css/main.css') }}?v={{ filemtime(public_path('css/main.css')) }}">
<link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/pipeline.css' : '/css/pipeline.css') }}">
@endpush

@section('content')
<div class="container-fluid p-0">
    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="card-header border-bottom-0 py-3 px-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div>
                    <h4 class="fw-bold mb-0">Manage Pipeline</h4>
                    <p class="text-muted small mb-0">Track active pipelines and their current status.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @can('pipeline.view')
                    <a href="{{ route('pipeline.export') }}" class="btn btn-outline-dark-blue">
                        <i class="fa-solid fa-download me-1"></i>Export
                    </a>
                    @endcan
                    @can('pipeline.create')
                    <a href="{{ route('pipeline.create') }}" class="btn btn-dark-blue">
                        <i class="bi bi-plus-lg me-1"></i>Add Pipeline
                    </a>
                    @endcan
                </div>
            </div>
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <h6 class="fw-bold mb-0">Active Pipelines</h6>
                <div class="input-group input-group-sm" style="max-width: 300px; width: 100%;">
                    <span class="input-group-text crm-search-icon border-0"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control crm-search-input border-0" placeholder="Search pipeline..." id="pipelineSearch">
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 responsive-table" id="pipelineTable">
                    <thead>
                        <tr>
                            <th class="ps-4" style="width: 80px;">Sr.No</th>
                            <th class="d-none d-md-table-cell">Customer Name</th>
                            <th>Pipeline Name</th>
                            <th class="d-none d-md-table-cell">Stage Name</th>
                            <th class="d-none d-md-table-cell">Status</th>
                            <th class="d-none d-md-table-cell">Created At</th>
                            <th class="text-end pe-4 d-none d-md-table-cell" style="width: 120px;">Actions</th>
                            <th class="text-center d-md-none" style="width: 80px;">Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <div class="card-footer border-top-0 py-4 px-4" id="paginationContainer"></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
window.crmUserPermissions = {
    pipeline: {
        view: @json(auth()->user()?->hasMatrixPermission('view_pipeline')),
        create: @json(auth()->user()?->hasMatrixPermission('create_pipeline')),
        edit: @json(auth()->user()?->hasMatrixPermission('edit_pipeline')),
        delete: @json(auth()->user()?->hasMatrixPermission('delete_pipeline')),
    }
};
</script>
<script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/js/pipeline.js' : '/js/pipeline.js') }}"></script>
@endpush
