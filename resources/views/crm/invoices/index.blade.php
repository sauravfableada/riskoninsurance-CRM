@extends('layouts.app')

@section('page_title', 'Invoices')

@push('styles')
    <link rel="stylesheet"
        href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/main.css' : '/css/main.css') }}?v={{ filemtime(public_path('css/main.css')) }}">
    <style>
        .invoice-kpi-card {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            background: #ffffff;
            padding: 16px 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
            transition: transform 0.15s ease;
        }
        .invoice-kpi-card:hover {
            transform: translateY(-2px);
        }
        .invoice-tab-btn {
            border: 1px solid #e2e8f0;
            background: #fff;
            color: #475569;
            padding: 7px 18px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .invoice-tab-btn.active {
            background: #0f172a;
            color: #fff;
            border-color: #0f172a;
        }
    </style>
@endpush

@section('content')
<div class="container-fluid p-0">

    {{-- Top Header & Action Buttons --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">
                <i class="fa-solid fa-file-invoice-dollar text-primary me-2"></i>Invoices &amp; Commissions
            </h1>
            <p class="text-muted small mb-0">Track Company Pay-In commission invoices to insurance companies and direct customer billings.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @can('invoices.view')
                <button type="button" id="invoiceExportBtn" class="btn btn-outline-dark-blue">
                    <i class="fa-solid fa-download me-1"></i>Export CSV
                </button>
            @endcan
            @can('invoices.create')
                <a href="{{ route('invoices.create') }}" class="btn btn-dark-blue">
                    <i class="bi bi-plus-lg me-1"></i>Create Invoice
                </a>
            @endcan
        </div>
    </div>

    {{-- 1. KPI Financial Metrics Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="invoice-kpi-card">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Total Invoiced</span>
                    <span class="badge bg-primary-subtle text-primary p-2 rounded-circle">
                        <i class="fa-solid fa-receipt"></i>
                    </span>
                </div>
                <h4 class="fw-bold text-dark mb-0">₹{{ number_format($totalInvoiced ?? 0, 2) }}</h4>
                <div class="text-muted small mt-1">Cumulative billed volume</div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="invoice-kpi-card">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Total Paid / Remitted</span>
                    <span class="badge bg-success-subtle text-success p-2 rounded-circle">
                        <i class="fa-solid fa-circle-check"></i>
                    </span>
                </div>
                <h4 class="fw-bold text-success mb-0">₹{{ number_format($paidInvoiced ?? 0, 2) }}</h4>
                <div class="text-muted small mt-1">Successfully received</div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="invoice-kpi-card">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Pending / Due</span>
                    <span class="badge bg-warning-subtle text-warning p-2 rounded-circle">
                        <i class="fa-solid fa-clock"></i>
                    </span>
                </div>
                <h4 class="fw-bold text-warning mb-0">₹{{ number_format($pendingInvoiced ?? 0, 2) }}</h4>
                <div class="text-muted small mt-1">Awaiting insurer clearance</div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="invoice-kpi-card">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Company Pay-In Invoices</span>
                    <span class="badge bg-info-subtle text-info p-2 rounded-circle">
                        <i class="fa-solid fa-building-columns"></i>
                    </span>
                </div>
                <h4 class="fw-bold text-dark mb-0">{{ $companyPayInCount ?? 0 }}</h4>
                <div class="text-muted small mt-1">Insurance company bills</div>
            </div>
        </div>
    </div>

    {{-- 2. Filter Tabs & Search Card --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-body p-3 p-md-4">
            {{-- Tabs --}}
            <div class="d-flex flex-wrap gap-2 mb-3">
                <button type="button" class="invoice-tab-btn active" data-type="">
                    <i class="fa-solid fa-layer-group me-1"></i>All Invoices
                </button>
                <button type="button" class="invoice-tab-btn" data-type="company_pay_in">
                    <i class="fa-solid fa-building-columns me-1 text-primary"></i>Company Pay-In
                </button>
                <button type="button" class="invoice-tab-btn" data-type="customer">
                    <i class="fa-solid fa-user me-1 text-secondary"></i>Customer Invoices
                </button>
            </div>

            {{-- Filter Fields Row --}}
            <div class="row g-2 align-items-center">
                <div class="col-md-3">
                    <select id="invoiceCompanyFilter" class="form-select form-select-sm">
                        <option value="">-- All Insurance Companies --</option>
                        @foreach($companies as $comp)
                            <option value="{{ $comp->id }}">{{ $comp->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <select id="invoiceStatusFilter" class="form-select form-select-sm">
                        <option value="">-- All Statuses --</option>
                        <option value="paid">Paid</option>
                        <option value="unpaid">Unpaid</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <input type="date" id="invoiceStartDate" class="form-control form-control-sm" title="From Date">
                </div>

                <div class="col-md-2">
                    <input type="date" id="invoiceEndDate" class="form-control form-control-sm" title="To Date">
                </div>

                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text border-end-0 bg-white"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="invoiceSearch" class="form-control border-start-0" 
                            placeholder="Search by invoice #, company, customer...">
                    </div>
                </div>
            </div>
        </div>

        {{-- 3. Invoices Table --}}
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 responsive-table">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4" style="width: 70px;">Sr.No</th>
                        <th style="min-width: 180px;">Invoice &amp; Type</th>
                        <th style="min-width: 220px;">Billed To / Account ID</th>
                        <th class="d-none d-md-table-cell" style="width: 120px;">Date</th>
                        <th class="d-none d-md-table-cell" style="width: 120px;">Due Date</th>
                        <th class="text-end" style="width: 130px;">Amount (₹)</th>
                        <th class="text-center" style="width: 110px;">Status</th>
                        <th class="text-center pe-4" style="width: 120px;">Action</th>
                    </tr>
                </thead>
                <tbody id="invoicesTable"></tbody>
            </table>
        </div>

        <div id="invoicePaginationContainer" class="card-footer bg-white border-top py-3 px-4"></div>
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
    <script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/js/invoice.js' : '/js/invoice.js') }}?v={{ time() }}"></script>
@endpush
