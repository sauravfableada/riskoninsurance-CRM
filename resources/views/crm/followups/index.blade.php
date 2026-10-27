@extends('layouts.app')

@section('page_title', 'Follow Ups')

@section('content')
<div class="container-fluid p-0">

    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="card-header border-bottom-0 py-3 px-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div>
                    <h4 class="fw-bold mb-0">Manage Follow Ups</h4>
                    <p class="text-muted small mb-0">Track and manage your follow up tasks.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @can('followups.view')
                    <a href="{{ route('followups.export') }}" class="btn btn-outline-dark-blue">
                        <i class="fa-solid fa-download me-1"></i>Export
                    </a>
                    @endcan
                    @can('followups.create')
                    <a href="{{ route('followups.create') }}" class="btn btn-dark-blue">
                        <i class="bi bi-plus-lg me-1"></i>Add Follow Up
                    </a>
                    @endcan
                </div>
            </div>
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="input-group input-group-sm" style="width: 250px;">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" id="followUpSearch" class="form-control border-start-0" placeholder="Search follow ups...">
                    </div>
                    <button class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1 bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse" aria-expanded="true" aria-controls="filterCollapse">
                        <i class="fa fa-filter"></i> Filters
                    </button>
                </div>
                <div class="d-flex align-items-center gap-2 small">
                    <span class="text-muted">Show per page:</span>
                    <select id="perPageSelect" class="form-select form-select-sm" style="width: 70px;">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>

            <div class="collapse show mt-3" id="filterCollapse">
                <div class="card card-body bg-light border-0 rounded-3">
                    <div class="row g-3 align-items-end">

                        <div class="col-md-2">
                            <label class="form-label small text-muted mb-1">Created By</label>
                            <select id="filterCreatedBy" class="form-select form-select-sm">
                                <option value="">All Users</option>
                                @foreach($users ?? [] as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label small text-muted mb-1">Status</label>
                            <select id="filterStatus" class="form-select form-select-sm">
                                <option value="">All Statuses</option>
                                <option value="pending">Pending</option>
                                <option value="resheduled">Rescheduled</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-muted mb-1">Start Date</label>
                            <input type="date" id="filterFromDate" class="form-control form-control-sm" name="from_date">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-muted mb-1">End Date</label>
                            <input type="date" id="filterToDate" class="form-control form-control-sm" name="to_date">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-muted mb-1">Date Range</label>
                            <select id="filterDateRange" class="form-select form-select-sm">
                                <option value="">Custom</option>
                                <option value="today">Today</option>
                                <option value="this_week">This Week</option>
                                <option value="this_month">This Month</option>
                                <option value="this_year">This Year</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
<button type="button" id="clearFiltersBtn" class="btn btn-sm btn-dark text-white px-3 w-100" style="background-color: #0f172a; border-color: #0f172a;">Clear</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 responsive-table">
                    <thead>
                        <tr>
                            <th class="ps-4">Purpose & Target</th>
                            <th class="text-start">Follow Up At</th>
                            <th class="d-none d-md-table-cell">Priority</th>
                            <th class="d-none d-md-table-cell">Staff</th>
                            <th class="d-none d-md-table-cell">Status</th>
                            <th class="text-end pe-4 d-none d-md-table-cell" style="width: 120px;">Actions</th>
                            <th class="text-center d-md-none" style="width: 80px;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="followUpsTable"></tbody>
                </table>
            </div>
            <div id="followupPaginationContainer" class="px-4 pb-3 pt-0"></div>
        </div>
    </div>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" accesskey=""href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/main.css' : '/css/main.css') }}?v={{ filemtime(public_path('css/main.css')) }}">
@endpush

@push('scripts')
    @include('crm.partials.module-permissions', [
        'module' => 'followups',
        'permissions' => [
            'view' => auth()->user()?->hasMatrixPermission('view_followups'),
            'create' => auth()->user()?->hasMatrixPermission('create_followups'),
            'edit' => auth()->user()?->hasMatrixPermission('edit_followups'),
            'delete' => auth()->user()?->hasMatrixPermission('delete_followups'),
        ],
    ])
    <script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/js/followup.js' : '/js/followup.js') }}"></script>
@endpush
