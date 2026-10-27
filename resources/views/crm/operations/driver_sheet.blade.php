@extends('layouts.app')

@section('page_title', 'Driver / Transit Sheet')

@section('content')
<div class="container-fluid py-4">
    <!-- Premium Header & Action Bar -->
    <div class="row mb-5">
        <div class="col-md-6">
            <h1 class="h2 fw-bold text-dark mb-1">Driver & Transit Sheet</h1>
            <p class="text-muted">Coordinate pickups, transfers, and daily sightseeing schedules.</p>
        </div>
        <div class="col-md-6 text-md-end">
            <div class="d-inline-flex flex-column flex-md-row gap-3 p-3 bg-white rounded-4 shadow-sm border border-light-subtle align-items-center">
                <form action="{{ route('operations.driver_sheet') }}" method="GET" class="d-flex align-items-center gap-2 m-0">
                    <label class="small fw-bold text-muted text-uppercase mb-0 me-1" style="font-size: 0.65rem; letter-spacing: 0.05em;">Duty Date</label>
                    <div class="position-relative">
                        <i class="bi bi-calendar-check position-absolute top-50 start-0 translate-middle-y ms-3 text-success" style="z-index: 5;"></i>
                        <input type="date" name="date" class="form-control form-control-lg ps-5 border-0 bg-light-subtle rounded-3 fw-bold" 
                               value="{{ $date }}" onchange="this.form.submit()" style="font-size: 0.9rem; min-width: 180px;">
                    </div>
                </form>
                <div class="vr d-none d-md-block mx-1"></div>
                <button type="button" class="btn btn-dark btn-lg px-4 rounded-3 d-flex align-items-center gap-2" onclick="window.print()">
                    <i class="bi bi-printer fs-5"></i>
                    <span class="fw-bold" style="font-size: 0.9rem;">Print Duty Sheet</span>
                </button>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
        <div class="card-header bg-dark text-white py-4 px-4 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-white bg-opacity-20 p-3 rounded-circle text-white">
                    <i class="bi bi-car-front-fill fs-4"></i>
                </div>
                <div>
                    <h5 class="fw-bold text-white mb-0">Transit Schedule</h5>
                    <span class="text-white text-opacity-75 small">{{ Carbon\Carbon::parse($date)->format('l, d M Y') }}</span>
                </div>
            </div>
            <div class="text-end d-none d-md-block">
                <div class="text-white text-opacity-50 small text-uppercase fw-bold" style="font-size: 0.6rem; letter-spacing: 0.1em;">Total Movements</div>
                <div class="fs-3 fw-bold">{{ $items->count() }}</div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light-subtle border-bottom">
                        <tr>
                            <th class="ps-4 text-muted text-uppercase small py-3" style="font-size: 0.7rem; letter-spacing: 0.05em; width: 120px;">Time</th>
                            <th class="text-muted text-uppercase small py-3" style="font-size: 0.7rem; letter-spacing: 0.05em; width: 150px;">Duty Type</th>
                            <th class="text-muted text-uppercase small py-3" style="font-size: 0.7rem; letter-spacing: 0.05em;">Guest / Contact</th>
                            <th class="text-muted text-uppercase small py-3" style="font-size: 0.7rem; letter-spacing: 0.05em;">Vehicle & Service</th>
                            <th class="pe-4 text-muted text-uppercase small py-3" style="font-size: 0.7rem; letter-spacing: 0.05em;">Pickup Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                        @php
                            $booking = $item->day->itinerary->booking;
                        @endphp
                        <tr class="hover-shadow">
                            <td class="ps-4 py-4">
                                <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-3 text-center fw-bold" style="font-size: 0.9rem;">
                                    {{ $item->time ?? 'TBA' }}
                                </div>
                            </td>
                            <td>
                                <span class="badge border border-secondary text-secondary rounded-pill px-3 py-1 text-uppercase" style="font-size: 0.65rem;">
                                    {{ $item->item_type }}
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark fs-6">{{ $booking->customer->name }}</div>
                                <div class="d-flex align-items-center gap-2 text-muted small mt-1">
                                    <i class="bi bi-people-fill text-primary"></i> {{ $booking->adults + $booking->children }} Pax
                                    <span class="mx-1 text-opacity-25 opacity-50">|</span>
                                    <i class="bi bi-telephone text-success"></i> {{ $booking->customer->phone ?? '--' }}
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $item->title }}</div>
                                <div class="text-muted small">{{ $item->supplier ? $item->supplier->name : 'Self/Vendor TBD' }}</div>
                            </td>
                            <td class="pe-4">
                                <div class="bg-light border rounded-3 p-2 small text-dark-emphasis">
                                    {{ $item->description ?? 'No specific instructions provided.' }}
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="py-5">
                                    <div class="bg-light d-inline-block p-4 rounded-circle mb-3">
                                        <i class="bi bi-car-front display-4 text-muted"></i>
                                    </div>
                                    <h4 class="fw-bold text-dark">No Duty Scheduled</h4>
                                    <p class="text-muted">All clear! No transport movements recorded for this date.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .hover-shadow:hover {
        background-color: rgba(248, 249, 250, 0.5) !important;
        transition: background-color 0.2s ease;
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
        }
        .card-header {
            background-color: #212529 !important;
            color: white !important;
            -webkit-print-color-adjust: exact;
        }
        .badge {
            border: 1px solid #666 !important;
            color: black !important;
        }
    }
</style>
@endsection
