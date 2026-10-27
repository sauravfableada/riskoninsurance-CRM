@extends('layouts.app')

@section('page_title', 'Services')

@push('styles')
<link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/main.css' : '/css/main.css') }}?v={{ filemtime(public_path('css/main.css')) }}">
@endpush

@section('content')
<div class="container-fluid p-0">
    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="card-header border-bottom-0 py-3 px-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div>
                    <h4 class="fw-bold mb-0">Manage Services</h4>
                    <p class="text-muted small mb-0">Track all services and their current availability.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @can('services.view')
                    <a href="{{ route('services.export') }}" class="btn btn-outline-dark-blue">
                        <i class="fa-solid fa-download me-1"></i>Export
                    </a>
                    @endcan
                    @can('services.create')
                    <a href="{{ route('services.create') }}" class="btn btn-dark-blue">
                        <i class="bi bi-plus-lg me-1"></i>Add Service
                    </a>
                    @endcan
                </div>
            </div>

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <h6 class="fw-bold mb-0">Active Services</h6>
                <div class="input-group input-group-sm" style="max-width: 300px; width: 100%;">
                    <span class="input-group-text crm-search-icon border-0">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text"
                        class="form-control crm-search-input border-0"
                        placeholder="Search services..."
                        id="serviceSearch">
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 responsive-table" id="servicesTable">
                    <thead>
                        <tr>
                            <th class="ps-4" style="width: 80px;">Sr.No</th>
                            <th>Service Name</th>
                            <th class="d-none d-md-table-cell">Product Name</th>
                            <th class="d-none d-md-table-cell">Service Price</th>
                            <th class="d-none d-md-table-cell">Service Status</th>
                            <th class="d-none d-md-table-cell">Created At</th>
                            <th class="text-end pe-4 d-none d-md-table-cell" style="width: 120px;">Actions</th>
                            <th class="text-center d-md-none" style="width: 80px;">Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <div class="card-footer border-top-0 py-4 px-4">
            <div id="paginationContainer"></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
window.crmUserPermissions = {
    ...(window.crmUserPermissions || {}),
    services: {
        view: @json(auth()->user()?->hasMatrixPermission('view_services')),
        create: @json(auth()->user()?->hasMatrixPermission('create_services')),
        edit: @json(auth()->user()?->hasMatrixPermission('edit_services')),
        delete: @json(auth()->user()?->hasMatrixPermission('delete_services')),
    }
};
</script>
<script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/js/service.js' : '/js/service.js') }}"></script>
@endpush
