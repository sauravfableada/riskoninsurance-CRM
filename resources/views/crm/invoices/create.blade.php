@extends('layouts.app')

@section('page_title', 'Create Invoice')

@push('styles')
    <style>
        .invoice-type-card {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
            cursor: pointer;
            transition: all 0.2s ease;
            background: #fff;
        }
        .invoice-type-card:hover {
            border-color: #0d6efd;
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.08);
        }
        .invoice-type-card.active {
            border-color: #0d6efd;
            background-color: #f0f7ff;
        }
        .billing-account-box {
            background: linear-gradient(135deg, #f0fdf4 0%, #f8fafc 100%);
            border: 1px solid #bbf7d0;
            border-radius: 12px;
        }
        .policy-select-table th {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background-color: #f8fafc;
        }
        .policy-select-table td {
            vertical-align: middle;
            font-size: 0.85rem;
        }
    </style>
@endpush

@section('content')
<div class="container-fluid p-0">
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden invoice-form-card mb-4">
        {{-- Card Header --}}
        <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1 class="h4 mb-1 fw-bold text-dark">
                        <i class="fa-solid fa-file-invoice-dollar text-primary me-2"></i>Create Invoice
                    </h1>
                    <p class="text-muted small mb-0">Generate Company Pay-In Commission Invoices or direct client invoices.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('invoices.index') }}" class="btn btn-dark-blue invoice-back-btn">
                        <i class="fa-solid fa-angle-left pe-1"></i>
                        <span>Back to Invoices</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body p-3 p-md-4">
            <form action="{{ route('invoices.store') }}" method="POST" id="invoiceForm" class="ajax-invoice-form needs-validation" novalidate>
                @csrf

                {{-- 1. Invoice Type Selector --}}
                <div class="mb-4">
                    <label class="form-label fw-bold text-uppercase small text-muted mb-2">Select Invoice Type</label>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="invoice-type-card active" id="cardCompanyPayIn" onclick="switchInvoiceType('company_pay_in')">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="form-check mt-1">
                                        <input class="form-check-input" type="radio" name="invoice_type" id="type_company_pay_in" value="company_pay_in" checked>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <h6 class="mb-0 fw-bold text-dark">
                                                <i class="fa-solid fa-building-columns text-primary me-2"></i>Company Pay-In Invoice
                                            </h6>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Recommended</span>
                                        </div>
                                        <p class="text-muted small mb-0">
                                            Bill Insurance Companies for policy Pay-In commissions earned by Riskon Insurance brokerage.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="invoice-type-card" id="cardCustomer" onclick="switchInvoiceType('customer')">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="form-check mt-1">
                                        <input class="form-check-input" type="radio" name="invoice_type" id="type_customer" value="customer">
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <h6 class="mb-0 fw-bold text-dark">
                                                <i class="fa-solid fa-user-tag text-secondary me-2"></i>Customer Invoice
                                            </h6>
                                            <span class="badge bg-secondary-subtle text-secondary">Client Billing</span>
                                        </div>
                                        <p class="text-muted small mb-0">
                                            Invoice direct clients or policy holders for policy payments, service fees, or charges.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 2. Remittance Billing Account Details (From Accounts.csv) --}}
                <div id="billingAccountSection" class="billing-account-box p-3 p-md-4 mb-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-success text-white px-2 py-1">
                                <i class="fa-solid fa-shield-check me-1"></i>Remittance Billing Account
                            </span>
                            <!-- <span class="text-muted small">(Configured from Accounts.csv)</span> -->
                        </div>
                        <span class="badge bg-white text-success border border-success-subtle px-2 py-1">
                            <i class="fa-solid fa-circle-check text-success me-1"></i>Active Account
                        </span>
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label text-muted small fw-semibold text-uppercase mb-1">Account Name</label>
                            <input type="text" name="account_name" class="form-control form-control-sm bg-white" 
                                value="{{ old('account_name', $defaultAccount['name'] ?? 'My Billing Account') }}" required>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label text-muted small fw-semibold text-uppercase mb-1">Account ID Code</label>
                            <input type="text" name="account_id_code" class="form-control form-control-sm font-monospace bg-white fw-bold text-primary" 
                                value="{{ old('account_id_code', $defaultAccount['id_code'] ?? '01E2F9-74AFE8-B222F4') }}" required>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label text-muted small fw-semibold text-uppercase mb-1">Account Type</label>
                            <input type="text" name="account_type" class="form-control form-control-sm bg-white" 
                                value="{{ old('account_type', $defaultAccount['type'] ?? 'Direct') }}" required>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label text-muted small fw-semibold text-uppercase mb-1">Organization</label>
                            <input type="text" name="organization" class="form-control form-control-sm bg-white fw-semibold" 
                                value="{{ old('organization', $defaultAccount['organization'] ?? 'Riskon Insurance') }}" required>
                        </div>
                    </div>
                </div>

                {{-- 3. Invoice Target & Meta Information --}}
                <div class="row gy-3 mb-4">
                    {{-- Company Pay-In Target: Insurance Company --}}
                    <div class="col-md-6" id="fieldCompany">
                        <label class="form-label fw-bold">Insurance Company <span class="text-danger">*</span></label>
                        <select name="company_id" id="company_id" class="form-select select2" required>
                            <option value="">-- Select Insurance Company to Bill --</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}" @selected((string) old('company_id') === (string) $company->id)>
                                    {{ $company->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text text-muted small">
                            <i class="fa-solid fa-circle-info text-primary me-1"></i>Selecting an insurer loads their policies and Pay-In commissions.
                        </div>
                    </div>

                    {{-- Customer Invoice Target: Customer --}}
                    <div class="col-md-6 d-none" id="fieldCustomer">
                        <label class="form-label fw-bold">Customer <span class="text-danger">*</span></label>
                        <select name="customer_id" id="customer_id" class="form-select select2">
                            <option value="">-- Search Customer --</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" @selected((string) old('customer_id') === (string) $customer->id)>
                                    {{ $customer->name }} ({{ $customer->email ?? 'No Email' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Invoice Number --}}
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Invoice Number <span class="text-danger">*</span></label>
                        <input type="text" name="number" id="invoiceNumberInput" class="form-control font-monospace" 
                            value="{{ old('number', $nextInvoiceNumber) }}" required>
                        <div class="form-text text-muted small">Auto-generated sequence, editable if required.</div>
                    </div>

                    {{-- Invoice Date --}}
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Invoice Date <span class="text-danger">*</span></label>
                        <input type="date" name="invoice_date" class="form-control" value="{{ old('invoice_date', date('Y-m-d')) }}" required>
                    </div>

                    {{-- Due Date --}}
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Due Date</label>
                        <input type="date" name="due_date" class="form-control" value="{{ old('due_date', date('Y-m-d', strtotime('+15 days'))) }}">
                    </div>

                    {{-- Status --}}
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Payment Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="unpaid" selected>Unpaid (Pending Remittance)</option>
                            <option value="paid">Paid (Remitted)</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>

                {{-- 4. Company Policies Pay-In Loader Panel --}}
                <div id="companyPoliciesPanel" class="card border rounded-3 mb-4 shadow-none">
                    <div class="card-header bg-light py-2 px-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary text-white"><i class="fa-solid fa-list-check me-1"></i>Company Policies</span>
                            <span class="fw-semibold text-dark small">Select Unbilled Policies for Pay-In Commission Invoicing</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-light text-muted border small"><i class="fa-solid fa-filter me-1"></i>Only Unbilled / Pending</span>
                            <div id="policyCounterBadge" class="badge bg-secondary-subtle text-secondary d-none">0 Available</div>
                        </div>
                    </div>

                    <div class="card-body p-3">
                        <div id="policyEmptyNotice" class="text-center py-4 text-muted">
                            <i class="fa-solid fa-building-circle-arrow-right fs-2 mb-2 text-primary opacity-50 d-block"></i>
                            Select an Insurance Company above to fetch policies and their calculated Pay-In commissions.
                        </div>

                        <div id="policyLoadingSpinner" class="text-center py-4 d-none">
                            <span class="spinner-border spinner-border-sm text-primary me-2"></span>
                            <span class="text-muted">Loading company policies and commission rates...</span>
                        </div>

                        <div id="policyTableContainer" class="table-responsive d-none">
                            <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="selectAllPolicies">
                                    <label class="form-check-label small fw-semibold" for="selectAllPolicies">Select All Policies</label>
                                </div>
                                <span class="text-muted small">Checked policies are automatically added to line items</span>
                            </div>
                            <table class="table table-sm table-hover table-bordered policy-select-table mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width: 40px;">Select</th>
                                        <th>Policy Number</th>
                                        <th>Policy Holder</th>
                                        <th>Policy Type</th>
                                        <th>Issue Date</th>
                                        <th class="text-end">Gross Premium</th>
                                        <th class="text-center">Pay-In %</th>
                                        <th class="text-end">Pay-In Commission</th>
                                    </tr>
                                </thead>
                                <tbody id="policiesTableBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- 5. Invoice Line Items Table --}}
                <div class="card border rounded-3 mb-4 shadow-none">
                    <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="fa-solid fa-receipt text-primary me-2"></i>Invoice Line Items
                        </h6>
                        <button type="button" id="addCustomItemBtn" class="btn btn-sm btn-outline-dark-blue">
                            <i class="fa-solid fa-plus me-1"></i>Add Custom Line Item
                        </button>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0" id="invoiceItemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="min-width: 250px;">Item / Policy Description <span class="text-danger">*</span></th>
                                        <th style="width: 110px;" class="text-center">HSN/SAC</th>
                                        <th style="width: 140px;" class="text-end">Gross Premium (₹)</th>
                                        <th style="width: 110px;" class="text-center">Pay-In %</th>
                                        <th style="width: 140px;" class="text-end">Pay-In Amount (₹) <span class="text-danger">*</span></th>
                                        <th style="width: 90px;" class="text-center">Qty <span class="text-danger">*</span></th>
                                        <th style="width: 150px;" class="text-end">Total (₹)</th>
                                        <th style="width: 60px;" class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="invoiceItemsBody">
                                    {{-- Dynamically populated rows --}}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- 6. Summary & Notes Calculation --}}
                <div class="row g-4 mb-4">
                    <div class="col-lg-6">
                        @php
                            $defBank = \App\Models\Setting::query()->where('key', 'company_bank_name')->value('value') ?: 'ICICI Bank (Tagore Garden)';
                            $defAcc = \App\Models\Setting::query()->where('key', 'company_bank_account_no')->value('value') ?: '335905001025';
                            $defIfsc = \App\Models\Setting::query()->where('key', 'company_bank_ifsc')->value('value') ?: 'ICIC0003359';
                            $defaultTermsText = "1. We declare that this invoice shows the actual price of the goods described and that all particulars are true and correct.\n2. Subject to 'Delhi' Jurisdiction Only\n3. Bank Details :\n   Bank Name: {$defBank}\n   A/C No: {$defAcc}\n   IFSC CODE: {$defIfsc}";
                        @endphp
                        <div class="mb-3">
                            <label class="form-label fw-bold d-flex align-items-center justify-content-between mb-1">
                                <span><i class="fa-solid fa-file-contract text-primary me-1"></i> Terms and Conditions</span>
                                <span class="badge bg-light text-muted border fw-normal small">Appears on PDF</span>
                            </label>
                            <textarea name="terms_and_conditions" class="form-control font-monospace small" rows="5" 
                                placeholder="Enter terms, declarations, and bank details...">{{ old('terms_and_conditions', $defaultTermsText) }}</textarea>
                            <div class="form-text text-muted small">Standard declarations, legal jurisdiction, and company payout bank details.</div>
                        </div>

                        <div>
                            <label class="form-label fw-bold mb-1"><i class="fa-solid fa-comment-dots text-secondary me-1"></i> Comments / Remittance Instructions</label>
                            <textarea name="comment" class="form-control" rows="3" 
                                placeholder="e.g. Tax invoice for insurance Pay-In brokerage commission. Direct remittance to Account 01E2F9-74AFE8-B222F4.">{{ old('comment', 'Tax invoice for brokerage pay-in commission as per agreement.') }}</textarea>
                            <div class="form-text text-muted small">These notes will appear on the printed/PDF invoice.</div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card border bg-light bg-opacity-50 rounded-3">
                            <div class="card-body p-3">
                                <h6 class="fw-bold mb-3 border-bottom pb-2">Financial Summary</h6>
                                
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted">Subtotal (Commission):</span>
                                    <span class="fw-semibold">₹ <span id="displaySubtotal">0.00</span></span>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="text-muted">GST / Tax:</span>
                                        <div class="input-group input-group-sm" style="width: 100px;">
                                            <input type="number" step="0.01" min="0" max="100" name="tax_percentage" id="taxPercentageInput" 
                                                class="form-control form-control-sm text-end" value="{{ old('tax_percentage', 18) }}">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <span class="fw-semibold">₹ <span id="displayTaxAmount">0.00</span></span>
                                </div>

                                {{-- CGST & SGST Breakdown --}}
                                <div class="ps-3 border-start border-2 border-primary-subtle my-2 bg-light bg-opacity-50 py-1 rounded-end">
                                    <div class="d-flex justify-content-between align-items-center small py-1">
                                        <span class="text-secondary"><i class="fa-solid fa-angle-right me-1 text-primary"></i>CGST (<span id="displayCgstRate">9</span>%):</span>
                                        <span class="text-dark fw-medium">₹ <span id="displayCgstAmount">0.00</span></span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center small py-1">
                                        <span class="text-secondary"><i class="fa-solid fa-angle-right me-1 text-primary"></i>SGST (<span id="displaySgstRate">9</span>%):</span>
                                        <span class="text-dark fw-medium">₹ <span id="displaySgstAmount">0.00</span></span>
                                    </div>
                                </div>

                                <div class="border-top pt-3 d-flex justify-content-between align-items-center">
                                    <span class="fw-bold fs-5 text-dark">Grand Total:</span>
                                    <span class="fw-bold fs-4 text-primary">₹ <span id="displayGrandTotal">0.00</span></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 7. Form Action Buttons --}}
                <div class="pt-3 border-top d-flex justify-content-end gap-2 form-actions">
                    <a href="{{ route('invoices.index') }}" class="btn btn-outline-dark-blue px-4">Cancel</a>
                    <button type="submit" id="submitInvoiceBtn" class="btn btn-dark-blue px-4">
                        <i class="fa-solid fa-check me-1"></i>Generate Invoice
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let itemRowCounter = 0;
    let fetchedCompanyPolicies = [];

    // Switch between Company Pay-In Invoice and Customer Invoice
    function switchInvoiceType(type) {
        const isCompanyPayIn = (type === 'company_pay_in');
        
        document.getElementById('type_company_pay_in').checked = isCompanyPayIn;
        document.getElementById('type_customer').checked = !isCompanyPayIn;

        document.getElementById('cardCompanyPayIn').classList.toggle('active', isCompanyPayIn);
        document.getElementById('cardCustomer').classList.toggle('active', !isCompanyPayIn);

        document.getElementById('fieldCompany').classList.toggle('d-none', !isCompanyPayIn);
        document.getElementById('fieldCustomer').classList.toggle('d-none', isCompanyPayIn);
        document.getElementById('companyPoliciesPanel').classList.toggle('d-none', !isCompanyPayIn);
        document.getElementById('billingAccountSection').classList.toggle('d-none', !isCompanyPayIn);

        const companySelect = document.getElementById('company_id');
        const customerSelect = document.getElementById('customer_id');
        if (companySelect) companySelect.required = isCompanyPayIn;
        if (customerSelect) customerSelect.required = !isCompanyPayIn;

        // Adjust default invoice prefix preview if empty or standard
        const numberInput = document.getElementById('invoiceNumberInput');
        if (numberInput) {
            const today = new Date().toISOString().slice(0, 10).replace(/-/g, '');
            if (isCompanyPayIn) {
                if (!numberInput.value || numberInput.value.startsWith('INV-CUST-') || numberInput.value.startsWith('INV-20')) {
                    numberInput.value = `INV-PAYIN-${today}-0001`;
                }
            } else {
                if (!numberInput.value || numberInput.value.startsWith('INV-PAYIN-')) {
                    numberInput.value = `INV-CUST-${today}-0001`;
                }
            }
        }
    }

    // Company Policy Fetcher
    function loadCompanyPolicies(companyId) {
        const notice = document.getElementById('policyEmptyNotice');
        const spinner = document.getElementById('policyLoadingSpinner');
        const container = document.getElementById('policyTableContainer');
        const tbody = document.getElementById('policiesTableBody');
        const counter = document.getElementById('policyCounterBadge');

        if (!companyId) {
            notice.classList.remove('d-none');
            spinner.classList.add('d-none');
            container.classList.add('d-none');
            counter.classList.add('d-none');
            tbody.innerHTML = '';
            fetchedCompanyPolicies = [];
            return;
        }

        notice.classList.add('d-none');
        spinner.classList.remove('d-none');
        container.classList.add('d-none');
        counter.classList.add('d-none');

        $.ajax({
            url: `/invoices/company-policies/${companyId}`,
            type: 'GET',
            success: function (res) {
                spinner.classList.add('d-none');
                if (res.status && res.policies && res.policies.length > 0) {
                    fetchedCompanyPolicies = res.policies;
                    counter.textContent = `${res.policies.length} Unbilled Policies`;
                    counter.classList.remove('d-none');
                    container.classList.remove('d-none');

                    tbody.innerHTML = res.policies.map((p, idx) => `
                        <tr>
                            <td class="text-center">
                                <input type="checkbox" class="form-check-input policy-check" 
                                    data-idx="${idx}" data-policy-id="${p.id}">
                            </td>
                            <td class="fw-bold font-monospace text-dark">${p.policy_number}</td>
                            <td>${p.holder_name}</td>
                            <td><span class="badge bg-light text-dark border">${p.policy_type_name}</span></td>
                            <td class="text-muted small">${p.start_date}</td>
                            <td class="text-end fw-semibold">₹${parseFloat(p.premium_amount).toLocaleString('en-IN', {minimumFractionDigits: 2})}</td>
                            <td class="text-center fw-bold text-primary">${parseFloat(p.pay_in_percentage).toFixed(2)}%</td>
                            <td class="text-end fw-bold text-success">₹${parseFloat(p.pay_in_amount).toLocaleString('en-IN', {minimumFractionDigits: 2})}</td>
                        </tr>
                    `).join('');
                } else {
                    notice.innerHTML = `<i class="fa-solid fa-circle-check text-success fs-3 mb-2 d-block"></i> No unbilled policies found for this insurance company. All existing policies have already been invoiced or paid. You can add manual Pay-In line items below if required.`;
                    notice.classList.remove('d-none');
                }
            },
            error: function () {
                spinner.classList.add('d-none');
                notice.innerHTML = `<i class="fa-solid fa-triangle-exclamation text-danger fs-3 mb-2 d-block"></i> Failed to fetch policies for this insurance company.`;
                notice.classList.remove('d-none');
            }
        });
    }

    // Add row to Line Items table
    function addLineItem(item = {}) {
        const tbody = document.getElementById('invoiceItemsBody');
        const idx = itemRowCounter++;

        const tr = document.createElement('tr');
        tr.id = `itemRow_${idx}`;
        tr.dataset.policyId = item.policy_id || '';

        const grossPremium = item.gross_premium !== undefined ? parseFloat(item.gross_premium) : 0;
        const payInPct = item.pay_in_percentage !== undefined ? parseFloat(item.pay_in_percentage) : 20;
        const unitAmount = item.amount !== undefined ? parseFloat(item.amount) : (grossPremium > 0 ? (grossPremium * payInPct / 100) : 0);
        const qty = item.quantity || 1;
        const total = (unitAmount * qty).toFixed(2);

        tr.innerHTML = `
            <td>
                <input type="text" name="items[${idx}][product_name]" class="form-control form-control-sm item-desc" 
                    value="${item.product_name || (item.policy_number ? `Pay-In Commission for Policy ${item.policy_number}` : 'Commission Remittance')}" required>
                ${item.policy_id ? `<input type="hidden" name="items[${idx}][policy_id]" value="${item.policy_id}">` : ''}
                ${item.policy_number ? `<input type="hidden" name="items[${idx}][policy_number]" value="${item.policy_number}">` : ''}
                ${item.holder_name ? `<input type="hidden" name="items[${idx}][holder_name]" value="${item.holder_name}">` : ''}
                ${item.policy_type_name ? `<input type="hidden" name="items[${idx}][policy_type_name]" value="${item.policy_type_name}">` : ''}
            </td>
            <td>
                <input type="text" name="items[${idx}][hsn_sac]" class="form-control form-control-sm text-center font-monospace item-hsn" 
                    value="${item.hsn_sac || '997161'}" placeholder="997161">
            </td>
            <td>
                <input type="number" step="0.01" min="0" name="items[${idx}][gross_premium]" class="form-control form-control-sm text-end item-premium" 
                    value="${grossPremium > 0 ? grossPremium.toFixed(2) : ''}" placeholder="0.00">
            </td>
            <td>
                <input type="number" step="0.01" min="0" max="100" name="items[${idx}][pay_in_percentage]" class="form-control form-control-sm text-center item-pct" 
                    value="${payInPct > 0 ? payInPct.toFixed(2) : ''}" placeholder="%">
            </td>
            <td>
                <input type="number" step="0.01" min="0" name="items[${idx}][amount]" class="form-control form-control-sm text-end item-amount" 
                    value="${unitAmount.toFixed(2)}" required>
            </td>
            <td>
                <input type="number" min="1" name="items[${idx}][quantity]" class="form-control form-control-sm text-center item-qty" 
                    value="${qty}" required>
            </td>
            <td>
                <input type="text" class="form-control form-control-sm text-end item-total fw-bold text-dark" readonly value="${total}">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm text-danger remove-item-btn" title="Remove Item">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
            </td>
        `;

        tbody.appendChild(tr);
        recalcTotals();
    }

    // Remove row by policy ID
    function removeLineItemByPolicyId(policyId) {
        const row = document.querySelector(`#invoiceItemsBody tr[data-policy-id="${policyId}"]`);
        if (row) {
            row.remove();
            recalcTotals();
        }
    }

    // Recalculate totals
    function recalcTotals() {
        let subtotal = 0;
        document.querySelectorAll('#invoiceItemsBody tr').forEach(tr => {
            const amountInput = tr.querySelector('.item-amount');
            const qtyInput = tr.querySelector('.item-qty');
            const totalInput = tr.querySelector('.item-total');

            const amt = parseFloat(amountInput?.value) || 0;
            const qty = parseInt(qtyInput?.value, 10) || 1;
            const lineTot = Math.round(amt * qty * 100) / 100;

            if (totalInput) totalInput.value = lineTot.toFixed(2);
            subtotal += lineTot;
        });

        const taxPct = parseFloat(document.getElementById('taxPercentageInput')?.value) || 0;
        const taxAmount = Math.round((subtotal * taxPct / 100) * 100) / 100;
        const grandTotal = Math.round((subtotal + taxAmount) * 100) / 100;

        const halfPct = Math.round((taxPct / 2) * 100) / 100;
        const cgstAmount = Math.round((taxAmount / 2) * 100) / 100;
        const sgstAmount = Math.round((taxAmount - cgstAmount) * 100) / 100;

        const cgstRateEl = document.getElementById('displayCgstRate');
        const sgstRateEl = document.getElementById('displaySgstRate');
        const cgstAmountEl = document.getElementById('displayCgstAmount');
        const sgstAmountEl = document.getElementById('displaySgstAmount');

        if (cgstRateEl) cgstRateEl.textContent = halfPct;
        if (sgstRateEl) sgstRateEl.textContent = halfPct;
        if (cgstAmountEl) cgstAmountEl.textContent = cgstAmount.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        if (sgstAmountEl) sgstAmountEl.textContent = sgstAmount.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});

        document.getElementById('displaySubtotal').textContent = subtotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('displayTaxAmount').textContent = taxAmount.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('displayGrandTotal').textContent = grandTotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    $(document).ready(function () {
        // Init with one default line item
        addLineItem({
            product_name: 'Brokerage Pay-In Commission',
            amount: 0,
            quantity: 1
        });

        // Company dropdown change
        $('#company_id').on('change', function () {
            loadCompanyPolicies(this.value);
        });

        // Checkbox events in policies table
        $(document).on('change', '.policy-check', function () {
            const idx = $(this).data('idx');
            const policy = fetchedCompanyPolicies[idx];
            if (!policy) return;

            if (this.checked) {
                addLineItem({
                    policy_id: policy.id,
                    policy_number: policy.policy_number,
                    holder_name: policy.holder_name,
                    policy_type_name: policy.policy_type_name,
                    gross_premium: policy.premium_amount,
                    pay_in_percentage: policy.pay_in_percentage,
                    amount: policy.pay_in_amount,
                    quantity: 1,
                    product_name: `Pay-In Commission (${policy.policy_number} - ${policy.holder_name})`
                });
            } else {
                removeLineItemByPolicyId(policy.id);
            }
        });

        // Select All Policies
        $('#selectAllPolicies').on('change', function () {
            const checked = this.checked;
            $('.policy-check').each(function () {
                if (this.checked !== checked) {
                    this.checked = checked;
                    $(this).trigger('change');
                }
            });
        });

        // Add custom line item button
        $('#addCustomItemBtn').on('click', function () {
            addLineItem({
                product_name: 'Custom Commission / Adjustment',
                amount: 0,
                quantity: 1
            });
        });

        // Remove row
        $(document).on('click', '.remove-item-btn', function () {
            const tr = $(this).closest('tr');
            const policyId = tr.data('policyId');
            if (policyId) {
                $(`.policy-check[data-policy-id="${policyId}"]`).prop('checked', false);
            }
            tr.remove();
            recalcTotals();
        });

        // When gross premium or pay-in % changes in row, recompute unit amount
        $(document).on('input', '.item-premium, .item-pct', function () {
            const tr = $(this).closest('tr');
            const premium = parseFloat(tr.find('.item-premium').val()) || 0;
            const pct = parseFloat(tr.find('.item-pct').val()) || 0;
            if (premium > 0 && pct > 0) {
                const calculated = Math.round((premium * pct / 100) * 100) / 100;
                tr.find('.item-amount').val(calculated.toFixed(2));
            }
            recalcTotals();
        });

        // When unit amount or qty changes, recalculate
        $(document).on('input', '.item-amount, .item-qty, #taxPercentageInput', function () {
            recalcTotals();
        });

        // Auto-sync HSN/SAC across rows if first row is changed
        $(document).on('input', '.item-hsn', function () {
            const val = $(this).val();
            if ($(this).closest('tr').index() === 0) {
                $('.item-hsn').each(function (i) {
                    if (i > 0 && (!$(this).val() || $(this).val() === '997161')) {
                        $(this).val(val);
                    }
                });
            }
        });
    });
</script>
@endpush
