@extends('layouts.masters')

@section('page_title', 'Masters - Clients')

@section('masters_content')

    @push('styles')
        <link rel="stylesheet"
            href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/main.css' : '/css/main.css') }}?v={{ filemtime(public_path('css/main.css')) }}">
        <link rel="stylesheet"
            href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/users.css' : '/css/users.css') }}?v={{ filemtime(public_path('css/users.css')) }}">
    @endpush


    <div class="card-header border-bottom-0 py-3 px-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
            <div>
                <h4 class="fw-bold mb-0">Manage Clients</h4>
                <p class="text-muted small mb-0">View and manage your client database and communication history.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @can('create', \App\Models\Customer::class)
                    <button type="button" class="btn btn-outline-dark-blue" onclick="showImportDialog()">
                        <i class="fa-solid fa-upload me-1"></i>Import CSV
                    </button>
                @endcan
                @can('viewAny', \App\Models\Customer::class)
                    <a href="{{ route('masters.customers.export') }}" class="btn btn-outline-dark-blue">
                        <i class="fa-solid fa-download me-1"></i>Export
                    </a>
                @endcan
                @can('create', \App\Models\Customer::class)
                    <a href="{{ route('masters.customers.create') }}" class="btn btn-dark-blue">
                        <i class="fa-solid fa-plus me-1"></i>Add Client
                    </a>
                @endcan
            </div>
        </div>
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div class="d-flex align-items-center gap-2">
                <div class="input-group input-group-sm" style="width: 250px;">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa-solid fa-search"></i></span>
                    <input type="text" id="customerSearch" class="form-control border-start-0"
                        placeholder="Search by name, phone no..." name="search" value="{{ request('search') }}">
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
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1">Start Date</label>
                        <input type="date" id="filterFromDate" class="form-control form-control-sm" name="from_date">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1">End Date</label>
                        <input type="date" id="filterToDate" class="form-control form-control-sm" name="to_date">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1">Date Range</label>
                        <select id="filterDateRange" class="form-select form-select-sm">
                            <option value="">Any time</option>
                            <option value="today">Today</option>
                            <option value="this_week">This Week</option>
                            <option value="this_month">This Month</option>
                            <option value="this_year">This Year</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1">Customer Type</label>
                        <select id="filterType" class="form-select form-select-sm">
                            <option value="">All types</option>
                            <option value="Individual">Individual</option>
                            <option value="Corporate">Corporate</option>
                            <option value="Government">Government</option>
                            <option value="NGO">NGO</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1">Country</label>
                        <select id="filterCountry" class="form-select form-select-sm select2">
                            <option value="">All Countries</option>
                            @foreach($countries ?? [] as $country)
                                <option value="{{ $country->id }}">{{ $country->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1">City</label>
                        <select id="filterCity" class="form-select form-select-sm select2">
                            <option value="">All Cities</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1">Status</label>
                        <select id="filterStatus" class="form-select form-select-sm">
                            <option value="">All statuses</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="button" id="clearFiltersBtn" class="btn btn-sm btn-dark text-white px-4 w-100" style="background-color: #0f172a; border-color: #0f172a;">
                            <i class="fa-solid fa-rotate-left me-1"></i> Clear Filters
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="customerTable" class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4" style="width: 80px;">Sr.No</th>
                        <th>Client name</th>
                        <th class="d-none d-md-table-cell">Email</th>
                        <th class="d-none d-md-table-cell">Phone</th>
                        <th class="d-none d-md-table-cell">Created At</th>
                        <th class="text-end pe-4 d-none d-md-table-cell" style="width: 120px;">Actions</th>
                        <th class="text-center d-md-none" style="width: 80px;">Action</th>
                    </tr>
                </thead>
                <tbody id="customersTable"></tbody>
            </table>
        </div>

        <!-- Pagination Container -->
        <div id="customerPaginationContainer" class="card-footer border-top-0 py-4 px-4"></div>
    </div>

    <form id="customersImportForm" class="d-none" enctype="multipart/form-data">
        @csrf
        <input type="file" name="import_file" id="customersImportFile" accept=".csv,text/csv">
    </form>
@endsection

@push('styles')
    <style>
        .customer-action-disabled,
        .customer-action-disabled:hover,
        .customer-action-disabled:focus {
            background-color: #f8fafc;
            border-color: #e2e8f0;
            color: #94a3b8;
            opacity: 1;
            box-shadow: none;
        }

        [data-theme="dark"] .customer-action-disabled,
        [data-theme="dark"] .customer-action-disabled:hover,
        [data-theme="dark"] .customer-action-disabled:focus {
            background-color: #1e293b;
            border-color: rgba(255, 255, 255, .08);
            color: #64748b;
        }
    </style>
@endpush

@push('scripts')
    <script>
        window.customerPageConfig = {
            canCreate: @json(auth()->user()->can('create', \App\Models\Customer::class)),
        };
    </script>
    <script
        src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/js/customer.js' : '/js/customer.js') }}?v={{ filemtime(public_path('js/customer.js')) }}"></script>
@endpush
