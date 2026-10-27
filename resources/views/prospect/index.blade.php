@extends('layouts.app')

@section('page_title', 'Prospects')

@push('styles')
    <link rel="stylesheet"
        href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/main.css' : '/css/main.css') }}?v={{ filemtime(public_path('css/main.css')) }}">
    <link rel="stylesheet"
        href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/users.css' : '/css/users.css') }}?v={{ filemtime(public_path('css/users.css')) }}">
    <style>
        .prospect-action-disabled,
        .prospect-action-disabled:hover,
        .prospect-action-disabled:focus {
            background-color: #f8fafc;
            border-color: #e2e8f0;
            color: #94a3b8;
            opacity: 1;
            box-shadow: none;
        }

        [data-theme="dark"] .prospect-action-disabled,
        [data-theme="dark"] .prospect-action-disabled:hover,
        [data-theme="dark"] .prospect-action-disabled:focus {
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
                        <h4 class="fw-bold mb-0">Manage Prospects</h4>
                        <p class="text-muted small mb-0">Track potential customers and quickly convert them to clients.</p>
                    </div>

                    @php
                        $legacyRole = data_get(auth()->user(), 'role');
                        $canCreateProspect = (string) $legacyRole === '1'
                            ? (int) data_get($permission, 'create', 0) === 1
                            : true;
                    @endphp

                    @if ($canCreateProspect)
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('prospects.create') }}" class="btn btn-dark-blue">
                                <i class="fa-solid fa-plus me-1"></i>Add Prospect
                            </a>
                        </div>
                    @endif
                </div>

                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="input-group input-group-sm" style="width: 250px;">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa-solid fa-search"></i></span>
                            <input type="text" id="prospectSearch" class="form-control border-start-0" placeholder="Search prospects...">
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
                                <label class="form-label small text-muted mb-1">Created By</label>
                                <select id="filterCreatedBy" class="form-select form-select-sm">
                                    <option value="">All Users</option>
                                    @foreach($users ?? [] as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
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
                            <div class="col-md-2">
                                <label class="form-label small text-muted mb-1">Status</label>
                                <select id="filterStatus" class="form-select form-select-sm">
                                    <option value="">All Statuses</option>
                                    <option value="prospect">Prospect</option>
                                    <option value="client">Client</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="button" id="clearFiltersBtn" class="btn btn-sm btn-dark text-white px-3" style="background-color: #0f172a; border-color: #0f172a;">Clear</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                <input type="hidden" id="base_url" value="{{ url('/') }}/">
                <input type="hidden" id="csrf_token" value="{{ csrf_token() }}">

                <div class="table-responsive">
                    <table id="prospectTable" class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4" style="width: 80px;">Sr.No</th>
                                <th>Prospect Name</th>
                                <th class="d-none d-md-table-cell">Mobile No</th>
                                <th class="d-none d-md-table-cell">Area</th>
                                <th class="text-center d-none d-md-table-cell">Status</th>
                                <th class="text-end pe-4 d-none d-md-table-cell" style="width: 120px;">Actions</th>
                                <th class="text-center d-md-none" style="width: 80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="prospectsTable"></tbody>
                    </table>
                </div>

                <div id="prospectPaginationContainer" class="card-footer border-top-0 py-4 px-4"></div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script
        src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/js/prospect.js' : '/js/prospect.js') }}?v={{ filemtime(public_path('js/prospect.js')) }}"></script>
@endpush
