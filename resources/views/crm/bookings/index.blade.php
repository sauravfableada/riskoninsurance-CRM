@extends('layouts.app')

@section('page_title', 'Bookings')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-1">Bookings</h1>
            <p class="text-muted small mb-0">Convert confirmed quotations into bookings and manage operations.</p>
        </div>
        <a href="{{ route('bookings.create') }}" class="btn btn-dark-blue px-4 py-2 rounded-pill shadow-sm">
            <i class="bi bi-plus-lg me-2"></i> Add Booking
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Booking #</th>
                            <th>Customer</th>
                            <th>Travel Dates</th>
                            <th>Status</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($bookings as $booking)
                            <tr>
                                <td class="fw-semibold">{{ $booking->booking_no }}</td>
                                <td>{{ $booking->customer?->name ?? '--' }}</td>
                                <td>
                                    <div class="small">
                                        {{ $booking->travel_start_date ? \Illuminate\Support\Carbon::parse($booking->travel_start_date)->format('d M Y') : '--' }}
                                        @if($booking->travel_end_date)
                                            <span class="text-muted">→</span>
                                            {{ \Illuminate\Support\Carbon::parse($booking->travel_end_date)->format('d M Y') }}
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $badge = match($booking->status) {
                                            'confirmed' => 'success',
                                            'completed' => 'primary',
                                            'cancelled' => 'danger',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <span class="badge crm-status-pill rounded-pill bg-{{ $badge }}">{{ ucfirst($booking->status) }}</span>
                                </td>
                                <td class="text-end">
                                    <span class="small text-muted me-1">{{ $booking->currency?->code ?? '' }}</span>
                                    {{ number_format((float)$booking->total_amount, 2) }}
                                </td>
                                <td class="text-end" data-label="Actions">
                                    <div class="d-inline-flex align-items-center gap-2">
                                        <a href="{{ route('bookings.show', $booking) }}" class="btn btn-light btn-sm text-info" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('bookings.invoice.create', $booking) }}" class="btn btn-light btn-sm text-success" title="Generate Invoice">
                                            <i class="bi bi-receipt"></i>
                                        </a>
                                        <a href="{{ route('bookings.itinerary', $booking) }}" class="btn btn-light btn-sm text-info" title="Build Itinerary">
                                            <i class="bi bi-calendar-event"></i>
                                        </a>
                                        <a href="{{ route('bookings.edit', $booking) }}" class="btn btn-light btn-sm text-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('bookings.destroy', $booking) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-light btn-sm text-danger" data-confirm-message="Are you sure?">
                                                <i class="bi bi-trash text-danger"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted small">No bookings created yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $bookings->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
