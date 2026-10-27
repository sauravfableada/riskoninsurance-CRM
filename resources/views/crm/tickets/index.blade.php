@extends('layouts.app')

@section('page_title', 'Support Tickets')

@push('styles')
<link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/main.css' : '/css/main.css') }}?v={{ filemtime(public_path('css/main.css')) }}">
@endpush

@section('content')
<div class="container-fluid p-0">
    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="card-header border-bottom-0 py-3 px-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div>
                    <h4 class="fw-bold mb-0">Manage Tickets</h4>
                    <p class="text-muted small mb-0">Track open support requests and customer issues.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @can('tickets.view')
                    <a href="{{ route('tickets.export') }}" class="btn btn-outline-dark-blue">
                        <i class="fa-solid fa-download me-1"></i>Export
                    </a>
                    @endcan
                    @can('tickets.create')
                    <a href="{{ route('tickets.create') }}" class="btn btn-dark-blue">
                        <i class="bi bi-plus-lg me-1"></i>Add Ticket
                    </a>
                    @endcan
                </div>
            </div>
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <h6 class="fw-bold mb-0">Active Tickets</h6>
                <div class="input-group input-group-sm" style="max-width: 300px; width: 100%;">
                    <span class="input-group-text crm-search-icon border-0"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control crm-search-input border-0" placeholder="Search tickets..." id="ticketsSearch" value="{{ request('search') }}">
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 responsive-table" id="ticketsTable">
                    <thead>
                        <tr>
                            <th class="ps-4 text-nowrap" style="min-width: 90px;">Sr.No</th>
                            <th class="d-none d-md-table-cell">Customer Name</th>
                            <th>Ticket Name</th>
                            <th class="d-none d-md-table-cell">Priority</th>
                            <th class="d-none d-md-table-cell">Status</th>
                            <th class="d-none d-md-table-cell">Created At</th>
                            <th class="text-end pe-4 d-none d-md-table-cell" style="width: 120px;">Actions</th>
                            <th class="text-center d-md-none" style="width: 80px;">Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <div class="card-footer border-top-0 py-4 px-4">
                <div id="paginationContainer"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
window.crmUserPermissions = {
    ...(window.crmUserPermissions || {}),
    tickets: {
        view: @json(auth()->user()?->hasMatrixPermission('view_tickets')),
        create: @json(auth()->user()?->hasMatrixPermission('create_tickets')),
        edit: @json(auth()->user()?->hasMatrixPermission('edit_tickets')),
        delete: @json(auth()->user()?->hasMatrixPermission('delete_tickets')),
    }
};
</script>
<script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/js/tickets-api.js' : '/js/tickets-api.js') }}"></script>
@endpush
