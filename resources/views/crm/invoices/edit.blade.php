@extends('layouts.app')

@section('page_title', 'Edit Invoice #' . $invoice->number)

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
        .invoice-type-card.active {
            border-color: #0d6efd;
            background-color: #f0f7ff;
        }
        .billing-account-box {
            background: linear-gradient(135deg, #f0fdf4 0%, #f8fafc 100%);
            border: 1px solid #bbf7d0;
            border-radius: 12px;
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
                        <i class="fa-solid fa-pen-to-square text-primary me-2"></i>Edit Invoice {{ $invoice->number }}
                    </h1>
                    <p class="text-muted small mb-0">Update invoice information, line items, and payment status.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('invoices.show', $invoice->id) }}" class="btn btn-outline-dark-blue">
                        <i class="bi bi-eye me-1"></i>View
                    </a>
                    <a href="{{ route('invoices.index') }}" class="btn btn-dark-blue invoice-back-btn">
                        <i class="fa-solid fa-angle-left pe-1"></i>
                        <span>Back</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body p-3 p-md-4">
            <form action="{{ route('invoices.update', $invoice->id) }}" method="POST" id="editInvoiceForm" class="ajax-invoice-form needs-validation" novalidate>
                @csrf
                @method('PUT')

                @php
                    $isCompanyPayIn = ($invoice->invoice_type === 'company_pay_in' || !empty($invoice->company_id));
                @endphp

                {{-- 1. Invoice Type Selector --}}
                <div class="mb-4">
                    <label class="form-label fw-bold text-uppercase small text-muted mb-2">Invoice Type</label>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="invoice-type-card {{ $isCompanyPayIn ? 'active' : '' }}" id="cardCompanyPayIn" onclick="switchInvoiceType('company_pay_in')">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="form-check mt-1">
                                        <input class="form-check-input" type="radio" name="invoice_type" id="type_company_pay_in" value="company_pay_in" @checked($isCompanyPayIn)>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 fw-bold text-dark">
                                            <i class="fa-solid fa-building-columns text-primary me-2"></i>Company Pay-In Invoice
                                        </h6>
                                        <p class="text-muted small mb-0">
                                            Invoiced to Insurance Company for policy Pay-In commissions.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="invoice-type-card {{ !$isCompanyPayIn ? 'active' : '' }}" id="cardCustomer" onclick="switchInvoiceType('customer')">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="form-check mt-1">
                                        <input class="form-check-input" type="radio" name="invoice_type" id="type_customer" value="customer" @checked(!$isCompanyPayIn)>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 fw-bold text-dark">
                                            <i class="fa-solid fa-user-tag text-secondary me-2"></i>Customer Invoice
                                        </h6>
                                        <p class="text-muted small mb-0">
                                            Direct client billing for policy or service fees.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 2. Remittance Billing Account Details --}}
                <div id="billingAccountSection" class="billing-account-box p-3 p-md-4 mb-4 {{ $isCompanyPayIn ? '' : 'd-none' }}">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <span class="badge bg-success text-white px-2 py-1">
                            <i class="fa-solid fa-shield-check me-1"></i>Remittance Billing Account (Accounts.csv)
                        </span>
                        <span class="badge bg-white text-success border border-success-subtle px-2 py-1">
                            <i class="fa-solid fa-circle-check text-success me-1"></i>Active Account
                        </span>
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label text-muted small fw-semibold text-uppercase mb-1">Account Name</label>
                            <input type="text" name="account_name" class="form-control form-control-sm bg-white" 
                                value="{{ old('account_name', $invoice->account_name ?? 'My Billing Account') }}" required>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label text-muted small fw-semibold text-uppercase mb-1">Account ID Code</label>
                            <input type="text" name="account_id_code" class="form-control form-control-sm font-monospace bg-white fw-bold text-primary" 
                                value="{{ old('account_id_code', $invoice->account_id_code ?? '01E2F9-74AFE8-B222F4') }}" required>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label text-muted small fw-semibold text-uppercase mb-1">Account Type</label>
                            <input type="text" name="account_type" class="form-control form-control-sm bg-white" 
                                value="{{ old('account_type', $invoice->account_type ?? 'Direct') }}" required>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label text-muted small fw-semibold text-uppercase mb-1">Organization</label>
                            <input type="text" name="organization" class="form-control form-control-sm bg-white fw-semibold" 
                                value="{{ old('organization', $invoice->organization ?? 'Riskon Insurance') }}" required>
                        </div>
                    </div>
                </div>

                {{-- 3. Invoice Target & Meta Information --}}
                <div class="row gy-3 mb-4">
                    {{-- Company Pay-In Target --}}
                    <div class="col-md-6 {{ $isCompanyPayIn ? '' : 'd-none' }}" id="fieldCompany">
                        <label class="form-label fw-bold">Insurance Company <span class="text-danger">*</span></label>
                        <select name="company_id" id="company_id" class="form-select select2" {{ $isCompanyPayIn ? 'required' : '' }}>
                            <option value="">-- Select Insurance Company --</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}" @selected((string) old('company_id', $invoice->company_id) === (string) $company->id)>
                                    {{ $company->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Customer Target --}}
                    <div class="col-md-6 {{ !$isCompanyPayIn ? '' : 'd-none' }}" id="fieldCustomer">
                        <label class="form-label fw-bold">Customer <span class="text-danger">*</span></label>
                        <select name="customer_id" id="customer_id" class="form-select select2" {{ !$isCompanyPayIn ? 'required' : '' }}>
                            <option value="">-- Search Customer --</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" @selected((string) old('customer_id', $invoice->customer_id) === (string) $customer->id)>
                                    {{ $customer->name }} ({{ $customer->email ?? 'No Email' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Invoice Number --}}
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Invoice Number <span class="text-danger">*</span></label>
                        <input type="text" name="number" id="invoiceNumberInput" class="form-control font-monospace" 
                            value="{{ old('number', $invoice->number) }}" required>
                    </div>

                    {{-- Invoice Date --}}
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Invoice Date <span class="text-danger">*</span></label>
                        <input type="date" name="invoice_date" class="form-control" 
                            value="{{ old('invoice_date', optional($invoice->invoice_date)->format('Y-m-d')) }}" required>
                    </div>

                    {{-- Due Date --}}
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Due Date</label>
                        <input type="date" name="due_date" class="form-control" 
                            value="{{ old('due_date', optional($invoice->due_date)->format('Y-m-d')) }}">
                    </div>

                    {{-- Status --}}
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Payment Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="unpaid" @selected($invoice->status === 'unpaid')>Unpaid (Pending Remittance)</option>
                            <option value="paid" @selected($invoice->status === 'paid')>Paid (Remitted)</option>
                            <option value="cancelled" @selected($invoice->status === 'cancelled')>Cancelled</option>
                        </select>
                    </div>
                </div>

                {{-- 4. Line Items Table --}}
                <div class="card border rounded-3 mb-4 shadow-none">
                    <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="fa-solid fa-receipt text-primary me-2"></i>Invoice Line Items
                        </h6>
                        <button type="button" id="addCustomItemBtn" class="btn btn-sm btn-outline-dark-blue">
                            <i class="fa-solid fa-plus me-1"></i>Add Line Item
                        </button>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0" id="invoiceItemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="min-width: 250px;">Item / Policy Description <span class="text-danger">*</span></th>
                                        <th style="width: 140px;" class="text-end">Gross Premium (₹)</th>
                                        <th style="width: 110px;" class="text-center">Pay-In %</th>
                                        <th style="width: 140px;" class="text-end">Pay-In Amount (₹) <span class="text-danger">*</span></th>
                                        <th style="width: 90px;" class="text-center">Qty <span class="text-danger">*</span></th>
                                        <th style="width: 150px;" class="text-end">Total (₹)</th>
                                        <th style="width: 60px;" class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="invoiceItemsBody">
                                    @foreach($invoice->items as $idx => $item)
                                        @php
                                            $premium = (float) ($item->gross_premium ?? 0);
                                            $pct = (float) ($item->pay_in_percentage ?? 0);
                                            $amt = (float) ($item->pay_in_amount ?? $item->amount);
                                            $qty = max(1, (int) $item->quantity);
                                            $tot = (float) ($item->total_price ?: ($amt * $qty));
                                        @endphp
                                        <tr id="itemRow_{{ $idx }}" data-policy-id="{{ $item->policy_id }}">
                                            <td>
                                                <input type="text" name="items[{{ $idx }}][product_name]" class="form-control form-control-sm item-desc" 
                                                    value="{{ $item->product_name }}" required>
                                                @if($item->policy_id)
                                                    <input type="hidden" name="items[{{ $idx }}][policy_id]" value="{{ $item->policy_id }}">
                                                @endif
                                                @if($item->policy_number)
                                                    <input type="hidden" name="items[{{ $idx }}][policy_number]" value="{{ $item->policy_number }}">
                                                @endif
                                                @if($item->holder_name)
                                                    <input type="hidden" name="items[{{ $idx }}][holder_name]" value="{{ $item->holder_name }}">
                                                @endif
                                                @if($item->policy_type_name)
                                                    <input type="hidden" name="items[{{ $idx }}][policy_type_name]" value="{{ $item->policy_type_name }}">
                                                @endif
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0" name="items[{{ $idx }}][gross_premium]" class="form-control form-control-sm text-end item-premium" 
                                                    value="{{ $premium > 0 ? number_format($premium, 2, '.', '') : '' }}" placeholder="0.00">
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0" max="100" name="items[{{ $idx }}][pay_in_percentage]" class="form-control form-control-sm text-center item-pct" 
                                                    value="{{ $pct > 0 ? number_format($pct, 2, '.', '') : '' }}" placeholder="%">
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0" name="items[{{ $idx }}][amount]" class="form-control form-control-sm text-end item-amount" 
                                                    value="{{ number_format($amt, 2, '.', '') }}" required>
                                            </td>
                                            <td>
                                                <input type="number" min="1" name="items[{{ $idx }}][quantity]" class="form-control form-control-sm text-center item-qty" 
                                                    value="{{ $qty }}" required>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm text-end item-total fw-bold text-dark" readonly value="{{ number_format($tot, 2, '.', '') }}">
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm text-danger remove-item-btn" title="Remove Item">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- 5. Summary & Notes Calculation --}}
                <div class="row g-4 mb-4">
                    <div class="col-lg-6">
                        <label class="form-label fw-bold">Comments / Remittance Instructions</label>
                        <textarea name="comment" class="form-control" rows="4">{{ old('comment', $invoice->comment) }}</textarea>
                    </div>

                    <div class="col-lg-6">
                        <div class="card border bg-light bg-opacity-50 rounded-3">
                            <div class="card-body p-3">
                                <h6 class="fw-bold mb-3 border-bottom pb-2">Financial Summary</h6>
                                
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted">Subtotal (Commission):</span>
                                    <span class="fw-semibold">₹ <span id="displaySubtotal">{{ number_format($invoice->subtotal ?? 0, 2) }}</span></span>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="text-muted">GST / Tax:</span>
                                        <div class="input-group input-group-sm" style="width: 100px;">
                                            <input type="number" step="0.01" min="0" max="100" name="tax_percentage" id="taxPercentageInput" 
                                                class="form-control form-control-sm text-end" value="{{ old('tax_percentage', $invoice->tax_percentage ?? 18) }}">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <span class="fw-semibold">₹ <span id="displayTaxAmount">{{ number_format($invoice->tax_amount ?? 0, 2) }}</span></span>
                                </div>

                                <div class="border-top pt-3 d-flex justify-content-between align-items-center">
                                    <span class="fw-bold fs-5 text-dark">Grand Total:</span>
                                    <span class="fw-bold fs-4 text-primary">₹ <span id="displayGrandTotal">{{ number_format($invoice->grand_total ?: $invoice->total_amount, 2) }}</span></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 6. Form Action Buttons --}}
                <div class="pt-3 border-top d-flex justify-content-end gap-2 form-actions">
                    <a href="{{ route('invoices.show', $invoice->id) }}" class="btn btn-outline-dark-blue px-4">Cancel</a>
                    <button type="submit" id="submitInvoiceBtn" class="btn btn-dark-blue px-4">
                        <i class="fa-solid fa-check me-1"></i>Update Invoice
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let itemRowCounter = {{ $invoice->items->count() + 10 }};

    function switchInvoiceType(type) {
        const isCompanyPayIn = (type === 'company_pay_in');
        
        document.getElementById('type_company_pay_in').checked = isCompanyPayIn;
        document.getElementById('type_customer').checked = !isCompanyPayIn;

        document.getElementById('cardCompanyPayIn').classList.toggle('active', isCompanyPayIn);
        document.getElementById('cardCustomer').classList.toggle('active', !isCompanyPayIn);

        document.getElementById('fieldCompany').classList.toggle('d-none', !isCompanyPayIn);
        document.getElementById('fieldCustomer').classList.toggle('d-none', isCompanyPayIn);
        document.getElementById('billingAccountSection').classList.toggle('d-none', !isCompanyPayIn);

        const companySelect = document.getElementById('company_id');
        const customerSelect = document.getElementById('customer_id');
        if (companySelect) companySelect.required = isCompanyPayIn;
        if (customerSelect) customerSelect.required = !isCompanyPayIn;
    }

    function addLineItem(item = {}) {
        const tbody = document.getElementById('invoiceItemsBody');
        const idx = itemRowCounter++;

        const tr = document.createElement('tr');
        tr.id = `itemRow_${idx}`;
        tr.dataset.policyId = item.policy_id || '';

        const grossPremium = item.gross_premium !== undefined ? parseFloat(item.gross_premium) : 0;
        const payInPct = item.pay_in_percentage !== undefined ? parseFloat(item.pay_in_percentage) : 20;
        const unitAmount = item.amount !== undefined ? parseFloat(item.amount) : 0;
        const qty = item.quantity || 1;
        const total = (unitAmount * qty).toFixed(2);

        tr.innerHTML = `
            <td>
                <input type="text" name="items[${idx}][product_name]" class="form-control form-control-sm item-desc" 
                    value="${item.product_name || 'Commission Remittance'}" required>
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

        document.getElementById('displaySubtotal').textContent = subtotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('displayTaxAmount').textContent = taxAmount.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('displayGrandTotal').textContent = grandTotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    $(document).ready(function () {
        $('#addCustomItemBtn').on('click', function () {
            addLineItem({
                product_name: 'Custom Line Item',
                amount: 0,
                quantity: 1
            });
        });

        $(document).on('click', '.remove-item-btn', function () {
            $(this).closest('tr').remove();
            recalcTotals();
        });

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

        $(document).on('input', '.item-amount, .item-qty, #taxPercentageInput', function () {
            recalcTotals();
        });

        recalcTotals();
    });
</script>
@endpush
