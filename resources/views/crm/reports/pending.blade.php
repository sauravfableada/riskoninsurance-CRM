@extends('layouts.app')

@section('page_title', 'Pending Accounts')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Pending Accounts</h1>
            <p class="text-muted small">Tracking outstanding payments and supplier dues.</p>
        </div>
        <div>
            <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-2"></i>Back to Analytics
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Receivables (Customer Due) -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-danger">Amount Due from Customers</h6>
                    <span class="badge bg-danger">Receivables</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-3">Booking / Customer</th>
                                    <th>Total</th>
                                    <th>Pending</th>
                                    <th class="text-end pe-3">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($receivables as $booking)
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-bold text-dark">{{ $booking->booking_no }}</div>
                                        <div class="text-muted small">{{ $booking->customer->name }}</div>
                                    </td>
                                    <td>₹{{ number_format($booking->total_amount, 2) }}</td>
                                    <td>
                                        <span class="text-danger fw-bold">
                                            ₹{{ number_format($booking->total_amount - $booking->invoices->flatMap->payments->sum('amount'), 2) }}
                                        </span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <a href="{{ route('bookings.show', $booking) }}" class="btn btn-sm btn-light">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">No pending receivables found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payables (Supplier Due) -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-warning">Amount Owed to Suppliers</h6>
                    <span class="badge bg-warning text-dark">Payables</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-3">Supplier / Booking</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th class="text-end pe-3">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($payables as $payable)
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-bold text-dark">{{ $payable->supplier->name }}</div>
                                        <div class="text-muted small">{{ $payable->booking ? $payable->booking->booking_no : '--' }}</div>
                                    </td>
                                    <td>₹{{ number_format($payable->amount, 2) }}</td>
                                    <td>
                                        <span class="badge bg-soft-warning text-warning text-uppercase" style="background-color: #fff3e0;">
                                            {{ $payable->status }}
                                        </span>
                                    </td>
                                    <td class="text-end pe-3">
                                        @if($payable->booking)
                                        <a href="{{ route('bookings.costs', $payable->booking) }}" class="btn btn-sm btn-light">
                                            <i class="bi bi-wallet2"></i>
                                        </a>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">No pending payables found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
