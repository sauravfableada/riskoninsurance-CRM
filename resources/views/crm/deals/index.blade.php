@extends('layouts.app')

@section('page_title', 'Deals / Opportunities')

@section('content')
    <div class="container-fluid p-0">
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-header border-bottom-0 py-3 px-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                    <div>
                        <h4 class="fw-bold mb-0">Deals / Opportunities</h4>
                        <p class="text-muted small mb-0">Track active customer deals and expected closes.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        @can('deals.view')
                            <a href="{{ route('deals.export') }}" class="btn btn-outline-dark-blue">
                                <i class="fa-solid fa-download me-1"></i>Export
                            </a>
                        @endcan
                        @can('deals.create')
                            <a href="{{ route('deals.create') }}" class="btn btn-dark-blue">
                                <i class="bi bi-plus-lg me-1"></i>Add Deal
                            </a>
                        @endcan
                    </div>
                </div>
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <h6 class="fw-bold mb-0">Active Deals</h6>
                    <div class="input-group input-group-sm" style="max-width: 300px; width: 100%;">
                        <span class="input-group-text crm-search-icon border-0"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control crm-search-input border-0" placeholder="Search deals..." id="dealsSearch" value="{{ request('search') }}">
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 responsive-table" id="dealsTable">
                        <thead>
                            <tr>
                                <th class="ps-4">Sr.No</th>
                                <th class="d-none d-md-table-cell">Created By</th>
                                <th>Deal Name</th>
                                <th class="d-none d-md-table-cell">Stage</th>
                                <th class="d-none d-md-table-cell">Deal Value</th>
                                <th class="d-none d-md-table-cell">Status</th>
                                <th class="text-end pe-4 d-none d-md-table-cell" style="width: 120px;">Actions</th>
                                <th class="text-center d-md-none" style="width: 80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                <div id="paginationContainer" class="px-4 pb-3 pt-0"></div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/main.css' : '/css/main.css') }}?v={{ filemtime(public_path('css/main.css')) }}">
@endpush

@push('scripts')
    <script>
        window.crmUserPermissions = {
            deals: {
                view: @json(auth()->user()?->hasMatrixPermission('view_deals')),
                create: @json(auth()->user()?->hasMatrixPermission('create_deals')),
                edit: @json(auth()->user()?->hasMatrixPermission('edit_deals')),
                delete: @json(auth()->user()?->hasMatrixPermission('delete_deals')),
            }
        };
    </script>
    <script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/js/deal.js' : '/js/deal.js') }}"></script>

@endpush
