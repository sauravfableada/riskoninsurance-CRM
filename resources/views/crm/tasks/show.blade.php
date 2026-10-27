@extends('layouts.app')

@section('page_title', 'Task Details')

@section('content')
<div class="container-fluid p-0">
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden detail-view-card">
        <div class="card-body p-0">
            {{-- Header Section --}}
            <div class="p-4 border-bottom bg-light bg-opacity-50">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 w-100">
                    <div class="flex-grow-1 w-100">
                        <h1 class="h4 mb-1 fw-bold">Task Details</h1>
                        <p class="text-muted small mb-0">Complete information about this task</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-lg-end justify-content-md-end">
                        @can('tasks.edit')
                            <a href="{{ route('tasks.edit', $task) }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                                <i class="bi bi-pencil me-1"></i>Edit
                            </a>
                        @endcan
                        <a href="{{ route('tasks.index') }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                            <i class="fa-solid fa-angle-left pe-2"></i>Back
                        </a>
                    </div>
                </div>
            </div>

            <div class="p-4">

                @php
                    $statusClass = match ($task->status) {
                        'completed' => 'bg-success',
                        'in_progress' => 'bg-primary',
                        'pending' => 'bg-warning text-dark',
                        default => 'bg-secondary',
                    };
                    $createdBy = $task->project?->creator?->name ?? '--';
                @endphp

                <div class="detail-view-block">
                    <h2 class="detail-view-title">{{ $task->title ?? '--' }}</h2>

                    <div class="row g-0 detail-view-grid">
                        <div class="col-md-6 detail-view-row">
                            <span class="detail-view-label">Created By:</span>
                            <span class="detail-view-value">{{ $createdBy }}</span>
                        </div>
                        <div class="col-md-6 detail-view-row">
                            <span class="detail-view-label">Customer Name:</span>
                            <span class="detail-view-value">{{ $customer?->name ?? '--' }}</span>
                        </div>

                        <div class="col-md-6 detail-view-row">
                            <span class="detail-view-label">Project Name:</span>
                            <span class="detail-view-value">{{ $task->project?->name ?? '--' }}</span>
                        </div>
                        <div class="col-md-6 detail-view-row">
                            <span class="detail-view-label">Status:</span>
                            <span class="badge rounded-pill px-3 {{ $statusClass }}">
                                {{ strtoupper(str_replace('_', '-', $task->status ?? '--')) }}
                            </span>
                        </div>

                        <div class="col-md-6 detail-view-row">
                            <span class="detail-view-label">Priority:</span>
                            <span class="detail-view-value text-uppercase">{{ $task->priority ?? '--' }}</span>
                        </div>
                        <div class="col-md-6 detail-view-row">
                            <span class="detail-view-label">Description:</span>
                            <span class="detail-view-value">{{ $task->description ?: '--' }}</span>
                        </div>

                        <div class="col-md-6 detail-view-row">
                            <span class="detail-view-label">Due Date:</span>
                            <span class="detail-view-value">{{ $task->due_date?->format('d M Y') ?? '--' }}</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        @include('crm.partials.status-history-table', ['histories' => $task->statusHistories])
    </div>
</div>
@endsection
