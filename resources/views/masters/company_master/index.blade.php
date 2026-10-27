@extends('layouts.masters')

@section('page_title', 'Insurance Company')

@section('masters_content')
    <div class="card-header border-bottom-0 py-3 px-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start mb-4 gap-3">
            <div class="pe-3">
                <h4 class="fw-bold mb-0">Manage Insurance Companies</h4>
                <p class="text-muted small mb-0">Create and manage insurance company master records.</p>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2 ms-md-auto">
                <a href="{{ route('masters.masters.index') }}" class="btn btn-sm btn-dark px-3 master-header-btn">
                    <i class="fa-solid fa-arrow-left me-1"></i> Back
                </a>
                <div class="d-flex flex-wrap gap-2" id="companyMasterActions"></div>
            </div>
        </div>
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <h6 class="fw-bold mb-0">Active Insurance Companies</h6>
            <div class="input-group input-group-sm" style="max-width: 300px; width: 100%;">
                <span class="input-group-text crm-search-icon border-0"><i class="fa-solid fa-search"></i></span>
                <input type="text" class="form-control crm-search-input border-0" placeholder="Search insurance companies..."
                    id="leadsSearch" value="{{ request('search') }}">
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 policy-master-table" id="leadsTable">
                <thead>
                    <tr>
                        <th class="ps-4" style="width: 80px;">Sr.No</th>
                        <th>Company Name</th>
                        <th>Description</th>
                        <th>Status</th>
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
@endsection

@push('styles')
    <link rel="stylesheet"
        href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/policy-master.css' : '/css/policy-master.css') }}?v={{ filemtime(public_path('css/policy-master.css')) }}">
    <style>
        .master-header-btn,
        #companyMasterActions .btn {
            min-height: 40px;
            display: inline-flex;
            align-items: center;
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/assets/js/company_master.js' : '/assets/js/company_master.js') }}?v={{ filemtime(public_path('assets/js/company_master.js')) }}"></script>
@endpush
