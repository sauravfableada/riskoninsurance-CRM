@extends('layouts.app')

@section('page_title', 'Invoice #' . $invoice->number)

@section('content')
<div class="container-fluid p-0">
    <div class="row g-4">
        <div class="col-lg-12">
            <div class="card shadow-sm border-0 rounded-4 overflow-hidden detail-view-card">
                <div class="card-body p-0">
                    {{-- Header Section --}}
                    <div class="p-4 border-bottom bg-light bg-opacity-50">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 w-100">
                            <div class="flex-grow-1 w-100">
                                <h1 class="h4 mb-1 fw-bold">Invoice {{ $invoice->number }}</h1>
                                <p class="text-muted small mb-0">Issued on {{ optional($invoice->invoice_date)->format('d M Y') ?? '—' }}</p>
                            </div>
                            <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-lg-end justify-content-md-end">
                                <a href="{{ route('invoices.pdf', $invoice->id) }}" class="btn crm-action-btn flex-grow-1 flex-md-grow-0" target="_blank">
                                    <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                                </a>
                                @if($invoice->status !== 'paid' && $invoice->status !== 'cancelled')
                                    @can('invoices.edit')
                                        <a href="{{ route('invoices.edit', $invoice->id) }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                                            <i class="bi bi-pencil me-1"></i>Edit
                                        </a>
                                    @endcan
                                @endif
                                <a href="{{ route('invoices.index') }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                                    <i class="fa-solid fa-angle-left pe-2"></i>Back
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="p-4">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3 detail-view-label">Billed To</h6>
                            <p class="mb-1 fw-medium"><span class="fw-bold">Customer Name:</span> {{ $invoice->customer?->name ?? '—' }}</p>
                            @if($invoice->customer?->email)
                                <p class="mb-1 small"><span class="detail-view-label">Customer Email:</span> <span class="detail-view-value">{{ $invoice->customer->email }}</span></p>
                            @endif
                            @if($invoice->customer?->phone)
                                <p class="mb-1 small"><span class="detail-view-label">Customer Phone:</span> <span class="detail-view-value">{{ $invoice->customer->phone }}</span></p>
                            @endif
                        </div>

                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3 detail-view-label">Invoice Info</h6>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="detail-view-label">Invoice Date:</span>
                                <span>{{ optional($invoice->invoice_date)->format('d M Y') ?? '—' }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="detail-view-label">Due Date:</span>
                                <span>{{ optional($invoice->due_date)->format('d M Y') ?? '—' }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="detail-view-label">Currency:</span>
                                <span>{{ $invoice->currency ? ($invoice->currency->symbol . ' ' . $invoice->currency->code) : 'USD' }}</span>
                            </div>
                        </div>

                        <div class="col-md-6">
                    <div class="flex-grow-1">
                        <h6 class="fw-bold mb-2 detail-view-label">Comment / Notes</h6>
                        <p class="small mb-4 {{ $invoice->comment ? 'detail-view-value' : 'text-muted fst-italic' }}">
                            {{ $invoice->comment ?? 'No additional notes provided.' }}
                        </p>
                    </div>                            
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Line Items Table -->
    <div class="card mt-4 shadow-sm border-0 detail-view-card">
        <div class="card-header d-flex justify-content-between align-items-center border-bottom">
            <h5 class="mb-0 fw-semibold">Line Items</h5>
            <span class="badge bg-secondary rounded-pill px-3">
                {{ $invoice->items->count() }} item{{ $invoice->items->count() !== 1 ? 's' : '' }}
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Description</th>
                            <th class="text-end">Unit Price</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end pe-4">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoice->items as $item)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-medium">{{ $item->product_name ?? $item->description ?? '—' }}</div>
                                    @if($item->description && $item->description !== $item->product_name)
                                        <small class="detail-view-value d-block">{{ Str::limit($item->description, 90) }}</small>
                                    @endif
                                </td>
                                <td class="text-end">
                                    {{ ($invoice->currency?->symbol ?? '$') }} {{ number_format($item->amount ?? $item->unit_price ?? 0, 2) }}
                                </td>
                                <td class="text-center">{{ $item->quantity ?? 1 }}</td>
                                <td class="text-end pe-4 fw-medium">
                                    {{ ($invoice->currency?->symbol ?? '$') }} {{ number_format($item->total_price ?? 0, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted fst-italic">
                                    This invoice has no line items yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="border-top">
                        <tr>
                            <td colspan="3" class="text-end fw-bold pe-3 pt-3">Grand Total:</td>
                            <td class="text-end fw-bold fs-5 pe-4 pt-3">
                                {{ ($invoice->currency?->symbol ?? '$') }} {{ number_format($invoice->total_amount, 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
