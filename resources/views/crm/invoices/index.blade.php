@extends('layouts.app')

@section('page_title', 'Invoices')

@push('styles')
    <link rel="stylesheet"
        href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/main.css' : '/css/main.css') }}?v={{ filemtime(public_path('css/main.css')) }}">
@endpush

@section('content')
    <div class="container-fluid p-0">

        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-header bg-white border-bottom-0 py-3 px-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                    <div>
                        <h4 class="fw-bold mb-0">Manage Invoices</h4>
                        <p class="text-muted small mb-0">Track billing records, due dates, and payment status.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        @can('invoices.view')
                            <a href="{{ route('invoices.export') }}" class="btn btn-outline-dark-blue">
                                <i class="fa-solid fa-download me-1"></i>Export
                            </a>
                        @endcan
                        @can('invoices.create')
                            <a href="{{ route('invoices.create') }}" class="btn btn-dark-blue">
                                <i class="bi bi-plus-lg me-1"></i>Create Invoice
                            </a>
                        @endcan
                    </div>
                </div>
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <h6 class="fw-bold mb-0">Active Invoices</h6>
                    <div class="input-group input-group-sm" style="max-width: 300px; width: 100%;">
                        <span class="input-group-text crm-search-icon border-0"><i class="bi bi-search"></i></span>
                        <input type="text" id="invoiceSearch" class="form-control crm-search-input border-0"
                            placeholder="Search invoices..." value="{{ request('search') }}">
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 responsive-table">
                        <thead>
                            <tr>
                                <th class="ps-4" style="width: 80px;">Sr.No</th>
                                <th>Invoice Info</th>
                                <th class="d-none d-md-table-cell">Date</th>
                                <th class="d-none d-md-table-cell">Due Date</th>
                                <th class="d-none d-md-table-cell">Status</th>
                                <th class="text-center" style="width: 80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="invoicesTable"></tbody>
                    </table>
                </div>
                <div id="invoicePaginationContainer" class="card-footer border-top-0 py-4 px-4"></div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.crmUserPermissions = {
            ...(window.crmUserPermissions || {}),
            invoices: {
                view: @json(auth()->user()?->hasMatrixPermission('view_invoices')),
                create: @json(auth()->user()?->hasMatrixPermission('create_invoices')),
                edit: @json(auth()->user()?->hasMatrixPermission('edit_invoices')),
                delete: @json(auth()->user()?->hasMatrixPermission('delete_invoices')),
            }
        };
    </script>
    <script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/js/invoice.js' : '/js/invoice.js') }}"></script>
@endpush