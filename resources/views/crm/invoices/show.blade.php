@extends('layouts.app')

@section('page_title', 'Invoice #' . $invoice->number)

@push('styles')
    <style>
        .invoice-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid #edf2f7;
        }
        .remittance-card {
            background: linear-gradient(135deg, #f0fdf4 0%, #e0f2fe 100%);
            border: 1px solid #bbf7d0;
            border-radius: 12px;
        }
        @media print {
            .no-print, .crm-sidebar, .crm-navbar, header, aside {
                display: none !important;
            }
            .invoice-card {
                box-shadow: none !important;
                border: none !important;
            }
        }
    </style>
@endpush

@section('content')
<div class="container-fluid p-0">
    <div class="invoice-card overflow-hidden mb-4">
        {{-- 1. Header Toolbar --}}
        <div class="p-4 border-bottom bg-light bg-opacity-50">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <h1 class="h3 fw-bold text-dark mb-0">{{ $invoice->number }}</h1>
                        @if($invoice->invoice_type === 'company_pay_in' || $invoice->company_id)
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                <i class="fa-solid fa-file-invoice-dollar me-1"></i>Company Pay-In Commission
                            </span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary px-2 py-1">
                                <i class="fa-solid fa-user me-1"></i>Customer Invoice
                            </span>
                        @endif

                        @if($invoice->status === 'paid')
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" id="statusBadge">
                                <i class="fa-solid fa-circle-check me-1"></i>Paid
                            </span>
                        @elseif($invoice->status === 'cancelled')
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1" id="statusBadge">
                                Cancelled
                            </span>
                        @else
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1" id="statusBadge">
                                <i class="fa-solid fa-clock me-1"></i>Unpaid (Pending)
                            </span>
                        @endif
                    </div>
                    <p class="text-muted small mb-0">
                        Issued on {{ optional($invoice->invoice_date)->format('d M Y') ?? '—' }} 
                        @if($invoice->creator) · Created by {{ $invoice->creator->name }} @endif
                    </p>
                </div>

                <div class="d-flex flex-wrap gap-2 no-print">
                    <a href="{{ route('invoices.pdf', $invoice->id) }}" class="btn btn-outline-dark-blue btn-sm" target="_blank">
                        <i class="fa-solid fa-file-pdf text-danger me-1"></i>Download
                    </a>
                    <!-- <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                        <i class="fa-solid fa-print me-1"></i>Print
                    </button> -->
                    <button type="button" class="btn btn-sm {{ $invoice->status === 'paid' ? 'btn-secondary' : 'btn-success' }}" 
                        id="togglePaidBtn" data-current-status="{{ $invoice->status }}">
                        <i class="fa-solid {{ $invoice->status === 'paid' ? 'fa-rotate-left' : 'fa-check-double' }} me-1"></i>
                        <span>{{ $invoice->status === 'paid' ? 'Mark as Unpaid' : 'Mark as Paid' }}</span>
                    </button>
                    @can('invoices.edit')
                        <a href="{{ route('invoices.edit', $invoice->id) }}" class="btn btn-dark-blue btn-sm">
                            <i class="bi bi-pencil me-1"></i>Edit
                        </a>
                    @endcan
                    <a href="{{ route('invoices.index') }}" class="btn btn-dark-blue btn-sm invoice-back-btn">
                        <i class="fa-solid fa-angle-left pe-1"></i>Back
                    </a>
                </div>
            </div>
        </div>

        <div class="p-4">
            {{-- 2. Remittance Billing Account Banner (From Accounts.csv) --}}
            <div class="remittance-card p-3 p-md-4 mb-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-success text-white px-2 py-1">
                            <i class="fa-solid fa-shield-halved me-1"></i>Remittance Billing Account
                        </span>
                        <span class="fw-semibold text-dark">{{ $invoice->organization ?? 'Riskon Insurance' }}</span>
                    </div>
                    <span class="badge bg-white text-success border border-success px-2 py-1">Direct Remittance</span>
                </div>
                <div class="row g-3">
                    <div class="col-sm-6 col-md-3">
                        <span class="text-muted small text-uppercase fw-semibold d-block">Account Name</span>
                        <span class="fw-bold text-dark">{{ $invoice->account_name ?? 'My Billing Account' }}</span>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <span class="text-muted small text-uppercase fw-semibold d-block">Account ID Code</span>
                        <span class="font-monospace fw-bold text-primary">{{ $invoice->account_id_code ?? '01E2F9-74AFE8-B222F4' }}</span>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <span class="text-muted small text-uppercase fw-semibold d-block">Account Type</span>
                        <span class="fw-semibold text-dark">{{ $invoice->account_type ?? 'Direct' }}</span>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <span class="text-muted small text-uppercase fw-semibold d-block">Organization</span>
                        <span class="fw-bold text-dark">{{ $invoice->organization ?? 'Riskon Insurance' }}</span>
                    </div>
                </div>
            </div>

            {{-- 3. Bill To & Invoice Info Cards --}}
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="card border rounded-3 p-3 h-100 bg-light bg-opacity-25">
                        <h6 class="fw-bold text-uppercase small text-muted mb-3">Billed To (Client / Insurer)</h6>
                        @if($invoice->invoice_type === 'company_pay_in' || $invoice->company_id)
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="fa-solid fa-building text-primary fs-4"></i>
                                <div>
                                    <h5 class="fw-bold text-dark mb-0">{{ $invoice->company?->name ?? 'Insurance Company' }}</h5>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle small">Insurance Partner</span>
                                </div>
                            </div>
                            <p class="text-muted small mb-0 mt-2">
                                Billable for policy Pay-In brokerage commissions as per standard SLA.
                            </p>
                        @else
                            <h5 class="fw-bold text-dark mb-1">{{ $invoice->customer?->name ?? 'Direct Customer' }}</h5>
                            @if($invoice->customer?->email)
                                <p class="text-muted small mb-1"><i class="fa-solid fa-envelope me-1"></i>{{ $invoice->customer->email }}</p>
                            @endif
                            @if($invoice->customer?->phone)
                                <p class="text-muted small mb-0"><i class="fa-solid fa-phone me-1"></i>{{ $invoice->customer->phone }}</p>
                            @endif
                        @endif
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card border rounded-3 p-3 h-100 bg-light bg-opacity-25">
                        <h6 class="fw-bold text-uppercase small text-muted mb-3">Invoice Details</h6>
                        <div class="row g-2">
                            <div class="col-6">
                                <span class="text-muted small d-block">Invoice Date:</span>
                                <span class="fw-semibold text-dark">{{ optional($invoice->invoice_date)->format('d M Y') ?? '—' }}</span>
                            </div>
                            <div class="col-6">
                                <span class="text-muted small d-block">Due Date:</span>
                                <span class="fw-semibold text-dark">{{ optional($invoice->due_date)->format('d M Y') ?? '—' }}</span>
                            </div>
                            <div class="col-6 mt-2">
                                <span class="text-muted small d-block">Currency:</span>
                                <span class="fw-semibold text-dark">{{ $invoice->currency ? ($invoice->currency->symbol . ' ' . $invoice->currency->code) : '₹ INR' }}</span>
                            </div>
                            <div class="col-6 mt-2">
                                <span class="text-muted small d-block">Payment Method:</span>
                                <span class="fw-semibold text-dark">Direct Bank Remittance</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 4. Line Items Table --}}
            <div class="card border rounded-3 overflow-hidden mb-4 shadow-none">
                <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="fa-solid fa-list text-primary me-2"></i>Itemized Policy / Commission Details
                    </h6>
                    <span class="badge bg-secondary rounded-pill px-3">
                        {{ $invoice->items->count() }} line {{ $invoice->items->count() === 1 ? 'item' : 'items' }}
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3" style="width: 40px;">#</th>
                                    <th>Description / Policy Info</th>
                                    <th>Holder Name</th>
                                    <th class="text-end">Gross Premium</th>
                                    <th class="text-center">Pay-In %</th>
                                    <th class="text-end">Pay-In Commission</th>
                                    <th class="text-center" style="width: 70px;">Qty</th>
                                    <th class="text-end pe-3" style="width: 140px;">Total (₹)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($invoice->items as $index => $item)
                                    <tr>
                                        <td class="ps-3 text-muted small">{{ $index + 1 }}</td>
                                        <td>
                                            <div class="fw-bold text-dark">{{ $item->product_name }}</div>
                                            @if($item->policy_number)
                                                <div class="text-muted small font-monospace">Policy: {{ $item->policy_number }}</div>
                                            @endif
                                            @if($item->policy_type_name)
                                                <span class="badge bg-light text-dark border small mt-1">{{ $item->policy_type_name }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $item->holder_name ?? '—' }}</td>
                                        <td class="text-end">
                                            @if($item->gross_premium)
                                                ₹{{ number_format($item->gross_premium, 2) }}
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($item->pay_in_percentage)
                                                <span class="badge bg-primary-subtle text-primary">{{ number_format($item->pay_in_percentage, 2) }}%</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-end fw-semibold">₹{{ number_format($item->pay_in_amount ?? $item->amount, 2) }}</td>
                                        <td class="text-center">{{ $item->quantity }}</td>
                                        <td class="text-end pe-3 fw-bold text-dark">₹{{ number_format($item->total_price ?: (($item->pay_in_amount ?? $item->amount) * $item->quantity), 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">No line items recorded.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- 5. Notes & Financial Summary --}}
            <div class="row g-4 mb-3">
                <div class="col-lg-7">
                    <div class="card border rounded-3 p-3 h-100 bg-light bg-opacity-25">
                        <h6 class="fw-bold text-dark small text-uppercase mb-2">Remittance Instructions & Notes</h6>
                        <p class="text-muted small mb-3">
                            {{ $invoice->comment ?: 'Brokerage Pay-In commission invoice payable by direct remittance to the specified Riskon Insurance billing account.' }}
                        </p>
                        <div class="border-top pt-2 mt-auto">
                            <span class="text-muted small">
                                <i class="fa-solid fa-lock text-success me-1"></i>Remittance Verification Code: 
                                <strong class="font-monospace text-dark">{{ $invoice->account_id_code ?? '01E2F9-74AFE8-B222F4' }}</strong>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card border rounded-3 p-3 bg-light bg-opacity-50">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Subtotal (Commission):</span>
                            <span class="fw-semibold">₹{{ number_format($invoice->subtotal ?: $invoice->items->sum('total_price'), 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">GST ({{ $invoice->tax_percentage ?? 18 }}%):</span>
                            <span class="fw-semibold">₹{{ number_format($invoice->tax_amount, 2) }}</span>
                        </div>
                        <div class="border-top pt-2 mt-2 d-flex justify-content-between align-items-center">
                            <span class="fw-bold fs-5 text-dark">Grand Total:</span>
                            <span class="fw-bold fs-4 text-primary">₹{{ number_format($invoice->grand_total ?: $invoice->total_amount, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $('#togglePaidBtn').on('click', function () {
        const btn = $(this);
        const current = btn.data('current-status');
        const nextStatus = (current === 'paid') ? 'unpaid' : 'paid';

        btn.prop('disabled', true);

        $.ajax({
            url: "{{ route('invoices.status', $invoice->id) }}",
            type: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                status: nextStatus
            },
            success: function (res) {
                if (res.success) {
                    btn.data('current-status', nextStatus);
                    btn.toggleClass('btn-success btn-secondary');
                    btn.find('span').text(nextStatus === 'paid' ? 'Mark as Unpaid' : 'Mark as Paid');
                    
                    const badge = $('#statusBadge');
                    if (nextStatus === 'paid') {
                        badge.removeClass('bg-warning-subtle text-warning border-warning-subtle')
                             .addClass('bg-success-subtle text-success border-success-subtle')
                             .html('<i class="fa-solid fa-circle-check me-1"></i>Paid');
                    } else {
                        badge.removeClass('bg-success-subtle text-success border-success-subtle')
                             .addClass('bg-warning-subtle text-warning border-warning-subtle')
                             .html('<i class="fa-solid fa-clock me-1"></i>Unpaid (Pending)');
                    }

                    if (typeof window.showAlert === 'function') {
                        window.showAlert('success', 'Invoice status updated to ' + nextStatus.toUpperCase());
                    }
                }
            },
            complete: function () {
                btn.prop('disabled', false);
            }
        });
    });
</script>
@endpush
