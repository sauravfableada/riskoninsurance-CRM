@extends('layouts.app')

@section('page_title', 'Rooming List')

@section('content')
<div class="container-fluid py-4">
    <!-- Premium Header & Action Bar -->
    <div class="row mb-5">
        <div class="col-md-6">
            <h1 class="h2 fw-bold text-dark mb-1">Rooming List</h1>
            <p class="text-muted">Manage daily hotel check-ins and guest allocations.</p>
        </div>
        <div class="col-md-6 text-md-end">
            <div class="d-inline-flex flex-column flex-md-row gap-3 p-3 bg-white rounded-4 shadow-sm border border-light-subtle align-items-center">
                <form action="{{ route('operations.rooming_list') }}" method="GET" class="d-flex align-items-center gap-2 m-0">
                    <label class="small fw-bold text-muted text-uppercase mb-0 me-1" style="font-size: 0.65rem; letter-spacing: 0.05em;">Viewing For</label>
                    <div class="position-relative">
                        <i class="bi bi-calendar-event position-absolute top-50 start-0 translate-middle-y ms-3 text-primary" style="z-index: 5;"></i>
                        <input type="date" name="date" class="form-control form-control-lg ps-5 border-0 bg-light-subtle rounded-3 fw-bold" 
                               value="{{ $date }}" onchange="this.form.submit()" style="font-size: 0.9rem; min-width: 180px;">
                    </div>
                </form>
                <div class="vr d-none d-md-block mx-1"></div>
                <button type="button" class="btn btn-dark-blue btn-lg px-4 rounded-3 d-flex align-items-center gap-2" onclick="window.print()">
                    <i class="bi bi-printer fs-5"></i>
                    <span class="fw-bold" style="font-size: 0.9rem;">Print List</span>
                </button>
            </div>
        </div>
    </div>

    @php
        $groupedItems = $items->groupBy(function($item) {
            return $item->supplier ? $item->supplier->name : 'Unassigned Hotel';
        });
    @endphp

    @forelse($groupedItems as $hotelName => $hotelItems)
    <div class="card border-0 shadow-sm mb-5 rounded-4 overflow-hidden">
        <div class="card-header bg-white border-0 py-4 px-4 d-flex justify-content-between align-items-center" 
             style="background: linear-gradient(90deg, #f8f9fa 0%, #ffffff 100%);">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary bg-opacity-10 p-3 rounded-circle text-primary">
                    <i class="bi bi-building-fill fs-4"></i>
                </div>
                <div>
                    <h5 class="fw-bold text-dark mb-0">{{ $hotelName }}</h5>
                    <span class="badge bg-soft-primary text-primary text-uppercase px-2 py-1 mt-1" style="font-size: 0.65rem; background-color: rgba(13, 110, 253, 0.1);">
                        {{ $hotelItems->count() }} Check-ins
                    </span>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light-subtle border-bottom">
                        <tr>
                            <th class="ps-4 text-muted text-uppercase small py-3" style="font-size: 0.7rem; letter-spacing: 0.05em;">Guest Details</th>
                            <th class="text-muted text-uppercase small py-3" style="font-size: 0.7rem; letter-spacing: 0.05em;">Booking</th>
                            <th class="text-muted text-uppercase small py-3" style="font-size: 0.7rem; letter-spacing: 0.05em;">Pax Count</th>
                            <th class="text-muted text-uppercase small py-3" style="font-size: 0.7rem; letter-spacing: 0.05em;">Accommodation Info</th>
                            <th class="pe-4 text-muted text-uppercase small py-3" style="font-size: 0.7rem; letter-spacing: 0.05em;">Operational Notes</th>
                        </tr>
                    </thead>
                    <tbody class="border-0">
                        @foreach($hotelItems as $item)
                        @php
                            $booking = $item->day->itinerary->booking;
                        @endphp
                        <tr class="hover-shadow">
                            <td class="ps-4 py-4">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="avatar-circle py-2 px-3 bg-secondary bg-opacity-10 rounded-pill text-secondary fw-bold">
                                        {{ substr($booking->customer->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark fs-6">{{ $booking->customer->name }}</div>
                                        <div class="text-muted small">{{ $booking->customer->phone ?? 'No phone' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="badge border border-dark-subtle text-dark fw-bold px-2 py-1" style="font-size: 0.75rem;">
                                    {{ $booking->booking_no }}
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="fw-bold text-dark">{{ $booking->adults }} Adults</span>
                                    @if($booking->children > 0)
                                    <span class="text-muted small">{{ $booking->children }} Children</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-start gap-2">
                                    <i class="bi bi-door-open text-primary mt-1"></i>
                                    <div>
                                        <div class="fw-bold text-dark">{{ $item->title }}</div>
                                        <div class="text-muted small lh-sm">{{ $item->description }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="pe-4">
                                @if($booking->notes)
                                <div class="bg-warning bg-opacity-10 p-2 rounded-3 text-warning-emphasis small border-start border-warning border-3">
                                    <i class="bi bi-info-circle me-1"></i> {{ $booking->notes }}
                                </div>
                                @else
                                <span class="text-muted small fst-italic">No special notes</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @empty
    <div class="card border-0 shadow-sm rounded-4 py-5 text-center">
        <div class="card-body py-5">
            <div class="mb-4">
                <div class="bg-light d-inline-block p-4 rounded-circle mb-3">
                    <i class="bi bi-calendar2-x display-4 text-muted"></i>
                </div>
                <h4 class="fw-bold text-dark">No Check-ins Scheduled</h4>
                <p class="text-muted mx-auto" style="max-width: 400px;">
                    There are no hotel check-ins scheduled for **{{ Carbon\Carbon::parse($date)->format('d M, Y') }}**. 
                    Try selecting another date to view the operational schedule.
                </p>
            </div>
            <a href="{{ route('bookings.index') }}" class="btn crm-action-btn px-4 rounded-pill">View All Bookings</a>
        </div>
    </div>
    @endforelse
</div>

<style>
    .hover-shadow:hover {
        background-color: rgba(248, 249, 250, 0.5) !important;
        transition: background-color 0.2s ease;
    }
    
    .avatar-circle {
        width: 45px;
        height: 45px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }

    @media print {
        .btn, form, footer, .sidebar, .nav-heading, .nav-link, .vr, header {
            display: none !important;
        }
        body {
            background-color: white !important;
        }
        .container-fluid {
            width: 100% !important;
            padding: 0 !important;
        }
        .card {
            border: 1px solid #eee !important;
            box-shadow: none !important;
            break-inside: avoid;
            margin-bottom: 2rem !important;
        }
        .card-header {
            background: #f8f9fa !important;
            border-bottom: 1px solid #ddd !important;
            -webkit-print-color-adjust: exact;
        }
        .badge {
            border: 1px solid #ccc !important;
            color: black !important;
        }
    }
</style>
@endsection
