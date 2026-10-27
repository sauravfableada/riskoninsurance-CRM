@extends('layouts.app')

@section('page_title', 'Proposal: ' . $quotation->reference)

@push('styles')
<style>
    @media print {
        body { background-color: white !important; }
        .crm-sidebar, .crm-header, .no-print { display: none !important; }
        .container-fluid { padding: 0 !important; }
        .card { border: none !important; box-shadow: none !important; }
        .print-break { page-break-before: always; }
    }
</style>
@endpush

@section('content')
<div class="container-fluid mb-5">
    <!-- Action Bar -->
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <a href="{{ route('quotations.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back to Quotations
        </a>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn crm-action-btn btn-sm">
                <i class="bi bi-printer"></i> Print / PDF
            </button>
            <a href="{{ route('quotations.itinerary', $quotation) }}" class="btn btn-outline-dark-blue btn-sm">
                <i class="bi bi-journal-text"></i> Manage Itinerary
            </a>
            <a href="{{ route('quotations.edit', $quotation) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-pencil"></i> Edit
            </a>
            @if($quotation->status === 'confirmed')
            <form action="{{ route('quotations.convert', $quotation) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-success btn-sm" data-confirm-message="Convert this to a Booking?">
                    <i class="bi bi-check-circle"></i> Convert to Booking
                </button>
            </form>
            @endif
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Main Proposal Card -->
            <div class="card shadow border-0 overflow-hidden">
                <!-- Header -->
                <div class="card-body p-5 bg-light border-bottom">
                    <div class="row">
                        <div class="col-md-6">
                            <h2 class="fw-bold text-primary mb-1">TRAVEL PROPOSAL</h2>
                            <p class="text-muted mb-0">Ref: {{ $quotation->reference }}</p>
                        </div>
                        <div class="col-md-6 text-md-end mt-3 mt-md-0">
                            <h5 class="mb-1 fw-bold">{{ config('app.name', 'Tour CRM') }}</h5>
                            <p class="text-muted mb-0 small">
                                Date: {{ $quotation->created_at->format('d M Y') }}<br>
                                Valid Until: {{ $quotation->valid_until ? date('d M Y', strtotime($quotation->valid_until)) : '--' }}
                            </p>
                            <span class="badge crm-status-pill bg-{{ $quotation->status == 'confirmed' ? 'success' : ($quotation->status == 'cancelled' ? 'danger' : 'info') }} mt-2 text-uppercase rounded-pill">
                                {{ $quotation->status }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Client Info -->
                <div class="card-body p-5 border-bottom">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted text-uppercase fw-bold mb-3">Prepared For</h6>
                            <h5 class="fw-bold mb-1">{{ $quotation->lead->name }}</h5>
                            <p class="mb-0 text-muted">
                                @if($quotation->lead->email)<i class="bi bi-envelope me-2"></i>{{ $quotation->lead->email }}<br>@endif
                                @if($quotation->lead->phone)<i class="bi bi-telephone me-2"></i>{{ $quotation->lead->phone }}@endif
                            </p>
                        </div>
                        <div class="col-md-6 mt-4 mt-md-0">
                            @if($quotation->tourPackage)
                                <h6 class="text-muted text-uppercase fw-bold mb-3">Proposed Tour</h6>
                                <h5 class="fw-bold mb-1"><i class="bi bi-map text-primary me-2"></i>{{ $quotation->tourPackage->name }}</h5>
                                <p class="text-muted mb-0">{{ $quotation->tourPackage->duration_days }} Days / {{ $quotation->tourPackage->duration_nights }} Nights</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Cost Breakdown -->
                <div class="card-body p-5">
                    <h6 class="text-muted text-uppercase fw-bold mb-4">Investment Summary</h6>
                    <div class="table-responsive">
                        <table class="table table-borderless table-hover">
                            <thead class="table-light border-bottom">
                                <tr>
                                    <th class="py-3">Description</th>
                                    <th class="py-3 text-center">Qty</th>
                                    <th class="py-3 text-end">Unit Price</th>
                                    <th class="py-3 text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($quotation->items as $item)
                                <tr>
                                    <td class="py-3 border-bottom">{{ $item->description }}</td>
                                    <td class="py-3 border-bottom text-center text-muted">{{ $item->quantity }}</td>
                                    <td class="py-3 border-bottom text-end text-muted">{{ number_format($item->unit_price, 2) }}</td>
                                    <td class="py-3 border-bottom text-end fw-semibold">{{ number_format($item->total_price, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="2" class="border-0"></td>
                                    <td class="text-end fw-bold py-4 fs-5">Grand Total</td>
                                    <td class="text-end fw-bold text-primary py-4 fs-5">INR {{ number_format($quotation->total_amount, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    @if($quotation->notes)
                        <div class="mt-4 p-4 bg-light rounded text-muted small border">
                            <h6 class="fw-bold">Terms & Notes</h6>
                            {!! nl2br(e($quotation->notes)) !!}
                        </div>
                    @endif
                </div>
            </div>

            <!-- Attached Itinerary -->
            @if($quotation->itinerary && $quotation->itinerary->days->count() > 0)
                <div class="print-break mt-5">
                    <h3 class="fw-bold text-primary mb-4 border-bottom pb-2"><i class="bi bi-journal-text me-2"></i>Detailed Itinerary</h3>
                    
                    <p class="lead mb-4">{{ $quotation->itinerary->title }}</p>
                    
                    <div class="timeline-itinerary">
                        @foreach($quotation->itinerary->days as $day)
                            <div class="card shadow-sm border-0 mb-4 rounded-4 overflow-hidden">
                                <div class="card-header bg-primary bg-opacity-10 py-3 border-0">
                                    <h5 class="mb-0 fw-bold text-primary">Day {{ $day->day_number }} : {{ $day->title }}</h5>
                                </div>
                                <div class="card-body p-4">
                                    @if($day->description)
                                        <p class="text-muted mb-4">{{ $day->description }}</p>
                                    @endif

                                    @if($day->items && $day->items->count() > 0)
                                        <div class="bg-light rounded p-3 mb-3">
                                            <h6 class="fw-bold small text-uppercase text-muted mb-3">Scheduled Activities</h6>
                                            <div class="row g-3">
                                                @foreach($day->items as $idx => $item)
                                                    <div class="col-md-6">
                                                        <div class="d-flex align-items-center">
                                                            <div class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center border shadow-sm me-3" style="width: 32px; height: 32px;">
                                                                <small class="fw-bold">{{ $idx + 1 }}</small>
                                                            </div>
                                                            <div>
                                                                <p class="mb-0 fw-semibold">{{ $item->activity }}</p>
                                                                <small class="text-muted">{{ $item->time ? \Carbon\Carbon::parse($item->time)->format('h:i A') . ' • ' : '' }}{{ ucfirst($item->item_type) }}</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    @if($day->meals)
                                        <div class="d-flex align-items-center text-muted small mt-2">
                                            <i class="bi bi-cup-hot me-2 text-warning fs-5"></i>
                                            <strong>Meals Included:</strong> <span class="ms-2">{{ $day->meals }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
