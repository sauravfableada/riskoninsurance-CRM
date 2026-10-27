@extends('layouts.app')

@section('page_title', 'Team Members')

@push('styles')
<link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/main.css' : '/css/main.css') }}?v={{ filemtime(public_path('css/main.css')) }}">
<link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/users.css' : '/css/users.css') }}?v={{ filemtime(public_path('css/users.css')) }}">
<style>
    .team-member-action-disabled,
    .team-member-action-disabled:hover,
    .team-member-action-disabled:focus {
        background-color: #f8fafc;
        border-color: #e2e8f0;
        color: #94a3b8;
        opacity: 1;
        box-shadow: none;
    }

    [data-theme="dark"] .team-member-action-disabled,
    [data-theme="dark"] .team-member-action-disabled:hover,
    [data-theme="dark"] .team-member-action-disabled:focus {
        background-color: #1e293b;
        border-color: rgba(255, 255, 255, .08);
        color: #64748b;
    }
</style>
@endpush

@section('content')
<div class="container-fluid p-0">
    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="card-header border-bottom-0 py-3 px-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div>
                    <h4 class="fw-bold mb-0">Manage Team Members</h4>
                    <p class="text-muted small mb-0">Create and manage team access, roles, and module permissions.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('team-members.create') }}" class="btn btn-dark-blue">
                        <i class="fa-solid fa-plus me-1"></i>Add Team Member
                    </a>
                </div>
            </div>
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="input-group input-group-sm" style="width: 250px;">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa-solid fa-search"></i></span>
                        <input type="text" id="teamMemberSearch" class="form-control border-start-0" placeholder="Search team members...">
                    </div>
                    <button class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1 bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse" aria-expanded="true" aria-controls="filterCollapse">
                        <i class="fa-solid fa-filter"></i> Filters
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
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-3">
<button type="button" id="clearFiltersBtn" class="btn btn-sm btn-dark text-white px-3" style="background-color: #0f172a; border-color: #0f172a;">Clear</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="teamMemberTable" class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4" style="width: 80px;">Sr.No</th>
                            <th>Member Name</th>
                            <th class="d-none d-md-table-cell">Email</th>
                            <th class="d-none d-md-table-cell">Mobile</th>
                            <th class="d-none d-md-table-cell">Status</th>
                            <th class="d-none d-md-table-cell">Designation</th>
                            <th class="text-end pe-4 d-none d-md-table-cell" style="width: 120px;">Actions</th>
                            <th class="text-center d-md-none" style="width: 80px;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="teamMembersTableBody"></tbody>
                </table>
            </div>

            <div id="teamMemberPaginationContainer" class="card-footer border-top-0 py-4 px-4"></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/js/team-member.js' : '/js/team-member.js') }}?v={{ filemtime(public_path('js/team-member.js')) }}"></script>
@endpush
