@extends('layouts.masters')

@section('page_title', 'Masters - Family Group Master')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <style>
        #familyGroupModal .policy-holder-control { gap: 0.5rem; }
        #familyGroupModal .policy-holder-select-wrap { min-width: 0; }
        #familyGroupModal .select2-container { width: 100% !important; }
        #familyGroupModal .select2-container .select2-selection--single {
            height: 38px;
            border-color: #dee2e6;
            border-radius: 0.375rem;
        }
        #familyGroupModal .select2-container .select2-selection--single .select2-selection__rendered {
            line-height: 36px;
            padding-left: 0.75rem;
        }
        #familyGroupModal .select2-container .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }
        #familyGroupModal #addPolicyHolderBtn { height: 38px; flex: 0 0 auto; }
        #filterCollapse .select2-container { width: 100% !important; }
        #filterCollapse .select2-container .select2-selection--single {
            height: 31px;
            border-color: #dee2e6;
            border-radius: 0.25rem;
        }
        #filterCollapse .select2-container .select2-selection--single .select2-selection__rendered {
            line-height: 29px;
            padding-left: 0.5rem;
        }
        #filterCollapse .select2-container .select2-selection--single .select2-selection__arrow {
            height: 29px;
        }
    </style>
@endpush

@section('masters_content')
    <div class="container-fluid p-0">
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-header bg-white border-bottom-0 py-3 px-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start mb-4 gap-3">
                    <div class="pe-3">
                        <h4 class="fw-bold mb-0">Manage Family Group Master</h4>
                        <p class="text-muted small mb-0">Track and manage your family group master.</p>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-2 ms-md-auto">
                        <a href="{{ route('masters.masters.index') }}" class="btn btn-sm btn-dark-blue px-3 master-header-btn">
                            <i class="fa-solid fa-arrow-left me-1"></i> Back
                        </a>
                    </div>
                </div>
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="input-group input-group-sm" style="width: 250px;">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                            <input type="text" id="leadsSearch" class="form-control border-start-0"
                                placeholder="Search by name..." name="search" value="{{ request('search') }}">
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
                                <label class="form-label small text-muted mb-1" for="filterHolderId">Policy Holder</label>
                                <select id="filterHolderId" class="form-select form-select-sm">
                                    <option value="">All Policy Holders</option>
                                </select>
                            </div>
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
                                <input type="date" id="filterFromDate" class="form-control form-control-sm" name="from_date" value="{{ request('from_date') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small text-muted mb-1">End Date</label>
                                <input type="date" id="filterToDate" class="form-control form-control-sm" name="to_date" value="{{ request('to_date') }}">
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
<button type="button" id="clearFiltersBtn" class="btn btn-sm btn-dark text-white px-3" style="background-color: #0f172a; border-color: #0f172a;">Clear</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="leadsTable">
                        <thead>
                            <tr>
                                <th class="ps-4" style="width: 80px;">Sr.No</th>
                                <th>Groups Name</th>
                                <th class="d-none d-md-table-cell">Policy Holder Name</th>
                                <th class="d-none d-md-table-cell">Remarks</th>
                                <th class="d-none d-md-table-cell">Created By</th>
                                <th class="d-none d-md-table-cell">Modified By</th>
                                <th class="text-end pe-4 d-none d-md-table-cell" style="width: 120px;">Actions</th>
                                <th class="text-center d-md-none" style="width: 80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div class="card-footer border-top-0 py-4 px-4" id="leadsPagination"></div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .master-header-btn {
            min-height: 40px;
            display: inline-flex;
            align-items: center;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/assets/js/family_groups.js' : '/assets/js/family_groups.js') }}?v={{ filemtime(public_path('assets/js/family_groups.js')) }}"></script>
@endpush
