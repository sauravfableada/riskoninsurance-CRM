@extends('layouts.app')

@section('page_title', 'Meeting Details')

@section('content')
    <div class="container-fluid p-0">
    <div class="row g-4">
        <div class="col-lg-12">
            <div class="card shadow-sm border-0 rounded-4 overflow-hidden detail-view-card">
                {{-- Header Section --}}
                <div class="p-4 border-bottom bg-light bg-opacity-50">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 w-100">
                        <div class="flex-grow-1 w-100">
                            <h1 class="h4 mb-1 fw-bold">Meeting Details</h1>
                            <p class="text-muted small mb-0">Complete information about this scheduled meeting.</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-lg-end justify-content-md-end">
                            @can('meetings.edit')
                                <a href="{{ route('meetings.edit', $meeting) }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                                    <i class="bi bi-pencil me-1"></i>Edit
                                </a>
                            @endcan
                            <a href="{{ route('meetings.index') }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                                <i class="fa-solid fa-angle-left pe-2"></i>Back
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-header bg-white border-bottom py-3">
                        <h6 class="fw-bold mb-0 text-primary small text-uppercase" style="letter-spacing: 0.05em;">Meeting
                            Information</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="detail-view-block">
                            <div class="row g-3 detail-view-grid">
                                <div class="col-md-6 col-lg-4 detail-view-row">
                                    <span class="detail-view-label text-muted small d-block mb-1">Customer</span>
                                    <span
                                        class="detail-view-value fw-bold text-dark">{{ $meeting->customer?->name ?? '--' }}</span>
                                </div>

                                <div class="col-md-6 col-lg-4 detail-view-row">
                                    <span class="detail-view-label text-muted small d-block mb-1">Assigned Staff</span>
                                    <span
                                        class="detail-view-value fw-bold text-dark">{{ $meeting->assignedUser?->name ?? 'Unassigned' }}</span>
                                </div>

                                <div class="col-md-6 col-lg-4 detail-view-row">
                                    <span class="detail-view-label text-muted small d-block mb-1">Meeting Type</span>
                                    <span
                                        class="detail-view-value fw-bold text-dark text-capitalize">{{ $meeting->meeting_type ?? '--' }}</span>
                                </div>

                                <div class="col-md-6 col-lg-4 detail-view-row">
                                    <span class="detail-view-label text-muted small d-block mb-1">Status</span>
                                    @php
                                        $badge = match ($meeting->status) {
                                            'scheduled' => 'bg-warning text-dark',
                                            'completed' => 'bg-success',
                                            'cancelled' => 'bg-danger',
                                            default => 'bg-secondary',
                                        };
                                    @endphp
                                    <span class="badge {{ $badge }} rounded-pill px-3">
                                        {{ $meeting->status ? ucfirst($meeting->status) : '--' }}
                                    </span>
                                </div>

                                <div class="col-md-6 col-lg-4 detail-view-row">
                                    <span class="detail-view-label text-muted small d-block mb-1">Scheduled At</span>
                                    <span class="detail-view-value fw-bold text-dark">
                                        <i class="bi bi-calendar-event me-1"></i>
                                        {{ $meeting->scheduled_at ? \Carbon\Carbon::parse($meeting->scheduled_at)->format('d M, Y h:i A') : 'Not set' }}
                                    </span>
                                </div>

                                <div class="col-md-6 col-lg-4 detail-view-row">
                                    <span class="detail-view-label text-muted small d-block mb-1">Address</span>
                                    <span
                                        class="detail-view-value fw-bold text-dark">{{ $meeting->address ?? '--' }}</span>
                                </div>

                                <div class="col-md-6 col-lg-4 detail-view-row border-bottom-0">
                                    <span class="detail-view-label text-muted small d-block mb-1">Created</span>
                                    <span class="detail-view-value text-muted small">
                                        {{ $meeting->created_at?->format('d M, Y') ?? '--' }}
                                        @if($meeting->creator)
                                            by <span class="text-dark fw-semibold">{{ $meeting->creator->name }}</span>
                                        @endif
                                    </span>
                                </div>

                                <div class="col-md-6 col-lg-4 detail-view-row border-bottom-0">
                                    <span class="detail-view-label text-muted small d-block mb-1">Last Updated</span>
                                    <span class="detail-view-value text-muted small">
                                        {{ $meeting->updated_at?->format('d M, Y') ?? '--' }}
                                        @if($meeting->updater)
                                            by <span class="text-dark fw-semibold">{{ $meeting->updater->name }}</span>
                                        @endif
                                    </span>
                                </div>
                            </div>

                            @if($meeting->agenda)
                                <div
                                    class="detail-view-description mt-4 p-3 bg-light rounded-3 border-start border-primary border-3">
                                    <span class="detail-view-label text-muted small d-block mb-2 text-uppercase fw-bold"
                                        style="font-size: 0.7rem; letter-spacing: 0.05em;">Meeting Agenda</span>
                                    <span class="detail-view-value text-dark">{{ $meeting->agenda }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection