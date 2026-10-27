@extends('layouts.app')

@section('page_title', 'Profit & Loss Report')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Profit & Loss Report</h1>
            <p class="text-muted small">Detailed financial breakdown per booking.</p>
        </div>
        <div>
            <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-2"></i>Back to Analytics
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-3">Booking Date</th>
                            <th>Booking No / Customer</th>
                            <th>Revenue</th>
                            <th>Total Costs</th>
                            <th>Profit</th>
                            <th>ROI</th>
                            <th class="text-end pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($bookings as $booking)
                        @php
                            $revenue = $booking->total_amount;
                            $cost = $booking->payables->sum('amount');
                            $profit = $revenue - $cost;
                            $roi = $cost > 0 ? round(($profit / $cost) * 100, 1) : 100;
                        @endphp
                        <tr>
                            <td class="ps-3">{{ $booking->created_at->format('d M, Y') }}</td>
                            <td>
                                <div class="fw-bold text-dark">{{ $booking->booking_no }}</div>
                                <div class="text-muted small">{{ $booking->customer->name }}</div>
                            </td>
                            <td class="fw-bold">₹{{ number_format($revenue, 2) }}</td>
                            <td class="text-muted">₹{{ number_format($cost, 2) }}</td>
                            <td>
                                <span class="fw-bold {{ $profit >= 0 ? 'text-success' : 'text-danger' }}">
                                    ₹{{ number_format($profit, 2) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ $roi >= 20 ? 'bg-success' : ($roi >= 0 ? 'bg-info' : 'bg-danger') }}">
                                    {{ $roi }}%
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
                            <td colspan="7" class="text-center py-4 text-muted">No booking data found for reporting.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-3 py-3 border-top">
                {{ $bookings->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
