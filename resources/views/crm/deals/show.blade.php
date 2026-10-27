@extends('layouts.app')

@section('page_title', 'Deal Details')

@section('content')
    <div class="container-fluid p-0">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden detail-view-card">
            <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h1 class="h4 mb-1 fw-semibold">Deal Details</h1>
                        <p class="text-muted small mb-0">Complete information about this deal</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-lg-end justify-content-md-end">
                        @can('deals.edit')
                            <a href="{{ route('deals.edit', $deal) }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                                <i class="bi bi-pencil me-1"></i>Edit
                            </a>
                        @endcan
                        <a href="{{ route('deals.index') }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                            <i class="fa-solid fa-angle-left pe-1"></i>
                            <span>Back</span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body p-3 p-md-4">
                <div class="row g-0 detail-view-grid">
                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label">Customer:</span>
                        <span class="detail-view-value">{{ $deal->customer?->name ?? '--' }}</span>
                    </div>

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label">Assigned To:</span>
                        <span class="detail-view-value">{{ $deal->assignedUser?->name ?? '--' }}</span>
                    </div>

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label">Stage:</span>
                        <span class="detail-view-value">{{ $deal->stage?->name ?? '--' }}</span>
                    </div>

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label">Deal Value:</span>
                        <span
                            class="detail-view-value">{{ $deal->currency?->symbol ?? ($deal->currency?->code ?? '') }}{{ number_format((float) $deal->amount, 2) }}</span>
                    </div>

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label">Customer Email:</span>
                        <span class="detail-view-value">{{ $deal->customer?->email ?? '--' }}</span>
                    </div>

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label">Probability:</span>
                        <span
                            class="detail-view-value">{{ $deal->probability !== null ? $deal->probability . '%' : '--' }}</span>
                    </div>

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label">Deal Status:</span>
                        @php
                            $statusName = strtolower((string) $deal->status?->name);
                            $statusColor = $deal->status?->color;
                            $statusBadge = match ($statusName) {
                                'new', 'open' => 'bg-primary text-white',
                                'qualified' => 'bg-info text-dark',
                                'proposal' => 'bg-warning text-dark',
                                'negotiation', 'in-process', 'in process' => 'bg-dark text-white',
                                'won' => 'bg-success text-white',
                                'lost' => 'bg-danger text-white',
                                'paused' => 'bg-secondary text-white',
                                default => 'bg-secondary text-white',
                            };
                        @endphp
                        <span class="badge rounded-pill px-3 {{ $statusColor ? '' : $statusBadge }}"
                            @if ($statusColor) style="background-color: {{ $statusColor }}; color: #fff;" @endif>
                            {{ $deal->status?->name ?? '--' }}
                        </span>
                    </div>

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label">Created:</span>
                        <span class="detail-view-value">
                            {{ $deal->created_at?->format('d M, Y') ?? '--' }}
                            @if ($deal->creator)
                                by {{ $deal->creator->name }}
                            @endif
                        </span>
                    </div>

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label">Last Updated:</span>
                        <span class="detail-view-value">{{ $deal->updated_at?->format('d M, Y') ?? '--' }}</span>
                    </div>

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label">Customer Phone no.:</span>
                        <span class="detail-view-value">{{ $deal->customer?->phone ?? '--' }}</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
