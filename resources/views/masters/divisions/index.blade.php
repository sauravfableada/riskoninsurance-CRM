@extends('layouts.masters')

@section('page_title', 'Division Master')

@section('masters_content')
    <div class="card-header border-bottom-0 py-3 px-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
            <div class="pe-3">
                <h4 class="fw-bold mb-0">Division Master</h4>
                <p class="text-muted small mb-0">Create and manage division records.</p>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2 ms-md-auto">
                <a href="{{ route('masters.masters.index') }}" class="btn btn-sm btn-dark-blue px-3 division-header-btn">
                    <i class="fa-solid fa-arrow-left me-1"></i> Back
                </a>
                <div class="d-flex flex-wrap gap-2" id="divisionActions"></div>
            </div>
        </div>
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div class="d-flex align-items-center gap-2">
                <span class="small text-muted">Show</span>
                <select class="form-select form-select-sm" id="divisionPerPage" style="width: 72px;">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span class="small text-muted">entries</span>
            </div>
            <div class="input-group input-group-sm" style="max-width: 300px; width: 100%;">
                <span class="input-group-text crm-search-icon border-0">Search:</span>
                <input type="text" class="form-control crm-search-input border-0" id="divisionSearch">
            </div>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 policy-master-table division-master-table" id="divisionTable">
                <thead>
                    <tr>
                        <th class="ps-4" style="width: 90px;">Sr.No</th>
                        <th>Name</th>
                        <th style="width: 140px;">Status</th>
                        <th class="d-none d-md-table-cell">Created By</th>
                        <th class="text-end pe-4 d-none d-md-table-cell" style="width: 180px;">Action</th>
                        <th class="text-center d-md-none" style="width: 80px;">Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <div class="card-footer border-top-0 py-4 px-4" id="divisionPagination"></div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet"
        href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/policy-master.css' : '/css/policy-master.css') }}?v={{ filemtime(public_path('css/policy-master.css')) }}">
    <style>
        .division-status {
            width: 2.5rem;
            height: 1.25rem;
            cursor: pointer;
        }

        .division-action-btn {
            min-width: 64px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .division-action-btn.btn-edit {
            background: #003c82;
            border-color: #003c82;
            color: #ffffff;
        }

        .division-action-btn.btn-delete {
            background: #ff2d55;
            border-color: #ff2d55;
            color: #ffffff;
        }

        .division-header-btn {
            min-height: 40px;
            display: inline-flex;
            align-items: center;
        }

        #divisionModal .modal-header {
            background: #4454b8;
            color: #ffffff;
        }

        #divisionModal .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/assets/js/divisions.js' : '/assets/js/divisions.js') }}?v={{ filemtime(public_path('assets/js/divisions.js')) }}"></script>
@endpush
