@extends('layouts.app')

@section('page_title', 'Ticket Details')

@section('content')
<div class="container-fluid p-0">
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden detail-view-card">
        <div class="card-body p-0">
            {{-- Header Section --}}
            <div class="p-4 border-bottom bg-light bg-opacity-50">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 w-100">
                    <div class="flex-grow-1 w-100">
                        <h1 class="h4 mb-1 fw-bold">Ticket Details</h1>
                        <p class="text-muted small mb-0">Complete information about this support ticket</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-lg-end justify-content-md-end">
                        @can('tickets.edit')
                            <a href="{{ route('tickets.edit', $ticket) }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                                <i class="bi bi-pencil me-1"></i>Edit
                            </a>
                        @endcan
                        <a href="{{ route('tickets.index') }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                            <i class="fa-solid fa-angle-left pe-2"></i>Back
                        </a>
                    </div>
                </div>
            </div>

            <div class="p-4">

            @php
                $priorityBadge = match ($ticket->priority) {
                    'Low' => 'bg-info text-dark',
                    'Medium' => 'bg-primary',
                    'High' => 'bg-warning text-dark',
                    'Urgent' => 'bg-danger',
                    default => 'bg-secondary',
                };

                $statusBadge = match ($ticket->status) {
                    'Open' => 'bg-info text-dark',
                    'In Progress' => 'bg-primary',
                    'Resolved' => 'bg-success',
                    'Closed' => 'bg-secondary',
                    default => 'bg-secondary',
                };
            @endphp

            <div class="detail-view-block px-md-5">
                <h2 class="detail-view-title">{{ $ticket->ticket_name ?? '--' }}</h2>

                <div class="row g-0 detail-view-grid">
                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label">Created By:</span>
                        <span class="detail-view-value">{{ $ticket->creator?->name ?? auth()->user()?->name ?? 'Admin' }}</span>
                    </div>

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label">Customer Name:</span>
                        <span class="detail-view-value">{{ $ticket->customer?->name ?? '--' }}</span>
                    </div>

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label">Created At:</span>
                        <span class="detail-view-value">{{ $ticket->created_at?->format('d M Y h:i A') ?? '--' }}</span>
                    </div>

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label">Ticket Description:</span>
                        <span class="detail-view-value">{{ $ticket->description ?? '--' }}</span>
                    </div>

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label">Priority:</span>
                        <span class="badge rounded-pill px-3 ms-1 {{ $priorityBadge }}">{{ $ticket->priority }}</span>
                    </div>

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label">Status:</span>
                        <span class="badge rounded-pill px-3 ms-1 {{ $statusBadge }}">{{ $ticket->status }}</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
@endpush
