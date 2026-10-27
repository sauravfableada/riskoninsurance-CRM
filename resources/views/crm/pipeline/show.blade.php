@extends('layouts.app')

@section('page_title', 'Pipeline Details')

@section('content')
<div class="container-fluid p-0">
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden detail-view-card">
        <div class="card-body p-0">
            {{-- Header Section --}}
            <div class="p-4 border-bottom bg-light bg-opacity-50">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 w-100">
                    <div class="flex-grow-1 w-100">
                        <h1 class="h4 mb-1 fw-bold">Pipeline Details</h1>
                        <p class="text-muted small mb-0">Complete information about this pipeline</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-lg-end justify-content-md-end">
                        @can('pipeline.edit')
                            <a href="{{ route('pipeline.edit', $pipeline) }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                                <i class="bi bi-pencil me-1"></i>Edit
                            </a>
                        @endcan
                        <a href="{{ route('pipeline.index') }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                            <i class="fa-solid fa-angle-left pe-2"></i>Back
                        </a>
                    </div>
                </div>
            </div>

            <div class="p-4">
            @php
                $statusValue = $pipeline->status;
                $statusLabel = match ($statusValue) {
                    'in_progress' => 'In-Process',
                    'paused' => 'Paused',
                    'completed' => 'Completed',
                    default => ucfirst(str_replace('_', ' ', (string) $statusValue)),
                };
                $statusClass = match ($statusValue) {
                    'in_progress' => 'bg-info',
                    'paused' => 'bg-warning',
                    'completed' => 'bg-success',
                    default => 'bg-secondary',
                };
            @endphp
            <div class="detail-view-block">
                <div class="row g-0 detail-view-grid">
                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label">Customer:</span>
                        <span class="detail-view-value">{{ $pipeline->customer?->name ?? '--' }}</span>
                    </div>

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label">Pipeline Stage:</span>
                        <span class="detail-view-value">{{ $pipeline->stage?->name ?? '--' }}</span>
                    </div>

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label">Status:</span>
                        <span class="badge rounded-pill {{ $statusClass }}">{{ $statusLabel }}</span>
                    </div>

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label">Created By:</span>
                        <span class="detail-view-value">{{ $pipeline->creator?->name ?? 'Admin' }}</span>
                    </div>

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label">Created At:</span>
                        <span class="detail-view-value">{{ $pipeline->created_at ? $pipeline->created_at->format('d M, Y') : '--' }}</span>
                    </div>

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label">Description:</span>
                        <span class="detail-view-value">{{ $pipeline->description ?? '--' }}</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
