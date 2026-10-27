@extends('layouts.app')

@section('page_title', 'Sub Agents')

@push('styles')
    <link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/main.css' : '/css/main.css') }}?v={{ filemtime(public_path('css/main.css')) }}">
    <link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/users.css' : '/css/users.css') }}?v={{ filemtime(public_path('css/users.css')) }}">
@endpush

@section('content')
    @php
        $canCreateSubAgents = auth()->user()?->hasMatrixPermission('create_users') ?? false;
        $canEditSubAgents = auth()->user()?->hasMatrixPermission('edit_users') ?? false;
        $canDeleteSubAgents = auth()->user()?->hasMatrixPermission('delete_users') ?? false;
    @endphp
    <div class="container-fluid p-0">
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-header border-bottom-0 py-3 px-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                    <div>
                        <h4 class="fw-bold mb-0">Sub Agents</h4>
                        <p class="text-muted small mb-0">Manage sub-agent accounts and permissions.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        @if($canCreateSubAgents)
                            <button type="button" class="btn btn-outline-dark-blue" onclick="showImportDialog()">
                                <i class="fa-solid fa-upload me-1"></i>Import CSV
                            </button>
                        @endif
                        <a href="{{ route('users.export') }}" class="btn btn-outline-dark-blue">
                            <i class="fa-solid fa-download me-1"></i>Export
                        </a>
                        @if($canCreateSubAgents)
                            <a href="{{ route('users.create') }}" class="btn btn-dark-blue">
                                <i class="bi bi-person-plus me-1"></i>Add Sub Agent
                            </a>
                        @endif
                    </div>
                </div>
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="input-group input-group-sm" style="width: 250px;">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                            <input type="text" id="usersSearch" class="form-control border-start-0" placeholder="Search sub agents...">
                        </div>
                        <button class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1 bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse" aria-expanded="true" aria-controls="filterCollapse">
                            <i class="bi bi-funnel"></i> Filters
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
                            <div class="col-md-3">
                                <label class="form-label small text-muted mb-1">Status</label>
                                <select id="filterStatus" class="form-select form-select-sm">
                                    <option value="">All Statuses</option>
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-3">
<button type="button" id="clearFiltersBtn" class="btn btn-sm btn-dark text-white px-3" style="background-color: #0f172a; border-color: #0f172a;">Clear</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <form action="{{ route('users.import') }}" method="POST" enctype="multipart/form-data" class="d-none"
                id="usersImportForm">
                @csrf
                <input type="file" name="import_file" id="usersImportFile" accept=".csv,text/csv"
                    onchange="if(this.files.length){ document.getElementById('usersImportForm').submit(); }">
            </form>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="usersTable">
                        <thead>
                            <tr>
                                <th style="width: 80px;">Sr.No</th>
                                <th>Name</th>
                                <th class="d-none d-md-table-cell">Email</th>
                                <th class="text-center d-none d-md-table-cell">Status</th>
                                <th class="d-none d-md-table-cell">Created At</th>
                                <th class="text-center d-none d-md-table-cell" style="min-width: 220px;">Action</th>
                                <th class="text-center d-md-none" style="width: 80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                <div class="card-footer border-top-0 py-4 px-4" id="usersPagination"></div>
            </div>
        </div>
    </div>

    <div class="modal fade permissions-modal" id="permissionsModal" tabindex="-1" aria-labelledby="permissionsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header">
                    <h5 class="modal-title" id="permissionsModalLabel">Sub Agent Permissions</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="permissions-table-wrap">
                        <table class="table permissions-matrix-table align-middle mb-0">
                            <colgroup>
                                <col class="module-col">
                                <col class="action-col">
                                <col class="action-col">
                                <col class="action-col">
                                <col class="action-col">
                                <col class="action-col">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Module</th>
                                    <th class="check-cell">View</th>
                                    <th class="check-cell">Insert</th>
                                    <th class="check-cell">Edit</th>
                                    <th class="check-cell">Delete</th>
                                    <th class="check-cell">All</th>
                                </tr>
                            </thead>
                            <tbody id="permissionsModalBody"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.usersPermissionsConfig = {
            modules: @json(collect(config('crm_permissions.modules', []))->except(['users', 'roles'])->all()),
            actions: @json(config('crm_permissions.actions', [])),
        };

        window.crmUserPermissions = {
            ...(window.crmUserPermissions || {}),
            users: {
                view: true,
                create: @json($canCreateSubAgents),
                edit: @json($canEditSubAgents),
                delete: @json($canDeleteSubAgents),
                permissions: @json($canEditSubAgents),
            }
        };
    </script>
    <script
        src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/js/users.js' : '/js/users.js') }}?v={{ filemtime(public_path('js/users.js')) }}"></script>
@endpush
