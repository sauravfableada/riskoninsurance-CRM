@extends('layouts.app')

@section('page_title', 'Follow Up Details')

@section('content')
    <div class="container-fluid p-0">
        <div class="row g-4">
            <div class="col-lg-12">
                <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                    <div class="card-body p-0">
                        {{-- Header Section --}}
                        <div class="p-4 border-bottom bg-light bg-opacity-50">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 w-100">
                                <div class="flex-grow-1 w-100">
                                    <h1 class="h4 mb-1 fw-bold">Follow Up Details</h1>
                                    <p class="text-muted small mb-0">Complete information about this scheduled follow up.</p>
                                </div>
                                <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-lg-end justify-content-md-end">
                                    @can('followups.edit')
                                        <a href="{{ route('followups.edit', $followUp) }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                                            <i class="bi bi-pencil me-1"></i>Edit
                                        </a>
                                    @endcan
                                    <a href="{{ route('followups.index') }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                                        <i class="fa-solid fa-angle-left pe-2"></i>Back
                                    </a>
                                </div>
                            </div>
                        </div>

                        {{-- Info Grid --}}
                        <div class="p-4">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label class="text-muted small text-uppercase fw-bold mb-2 d-block" style="letter-spacing: 0.05em;">
                                            <i class="bi bi-card-text text-primary me-1"></i>Purpose
                                        </label>
                                        <div class="h6 fw-bold mb-0 text-dark">{{ $followUp->purpose }}</div>
                                    </div>
                                    <div class="mb-4">
                                        <label class="text-muted small text-uppercase fw-bold mb-2 d-block" style="letter-spacing: 0.05em;">
                                            <i class="bi bi-person-circle text-info me-1"></i>Lead
                                        </label>
                                        <div class="h6 fw-bold mb-0 text-dark">{{ $followUp->lead->name ?? '--' }}</div>
                                    </div>
                                    <div class="mb-4">
                                        <label class="text-muted small text-uppercase fw-bold mb-2 d-block" style="letter-spacing: 0.05em;">
                                            <i class="bi bi-person-badge text-success me-1"></i>Assigned Staff
                                        </label>
                                        <div class="h6 fw-bold mb-0 text-muted small">
                                            {{ $followUp->assignedUser?->name ?? 'Unassigned' }}
                                        </div>
                                    </div>
                                    <div class="mb-4">
                                        <label class="text-muted small text-uppercase fw-bold mb-2 d-block" style="letter-spacing: 0.05em;">
                                            <i class="bi bi-flag text-warning me-1"></i>Priority & Status
                                        </label>
                                        <div class="d-flex flex-wrap gap-2">
                                            @php
                                                $priorityClass = [
                                                    'low' => 'bg-info',
                                                    'medium' => 'bg-primary',
                                                    'high' => 'bg-danger'
                                                ][$followUp->priority] ?? 'bg-secondary';
                                            @endphp
                                            <span class="badge {{ $priorityClass }} opacity-75 rounded-pill px-3 py-2 text-capitalize">
                                                {{ $followUp->priority }} Priority
                                            </span>

                                            @php
                                                $statusClass = [
                                                    'pending' => 'bg-warning text-dark',
                                                    'resheduled' => 'bg-info',
                                                    'completed' => 'bg-success',
                                                    'cancelled' => 'bg-danger'
                                                ][$followUp->status] ?? 'bg-secondary';
                                            @endphp
                                            <span class="badge {{ $statusClass }} opacity-75 rounded-pill px-3 py-2 text-capitalize">
                                                {{ $followUp->status }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label class="text-muted small text-uppercase fw-bold mb-2 d-block" style="letter-spacing: 0.05em;">
                                            <i class="bi bi-calendar-event text-danger me-1"></i>Follow Up At
                                        </label>
                                        <div class="h6 fw-bold mb-0 text-primary">
                                            {{ $followUp->follow_up_at ? \Illuminate\Support\Carbon::parse($followUp->follow_up_at)->format('d M, Y h:i A') : 'Not set' }}
                                        </div>
                                    </div>
                                    <div class="mb-4">
                                        <label class="text-muted small text-uppercase fw-bold mb-2 d-block" style="letter-spacing: 0.05em;">
                                            <i class="bi bi-clock-history text-muted me-1"></i>Audit Details
                                        </label>
                                        <div class="small text-muted mb-1">
                                            <strong>Created By:</strong> {{ $followUp->creator?->name ?? '--' }}
                                            <span class="mx-1">|</span>
                                            {{ $followUp->created_at?->format('d M, Y') ?? '--' }}
                                        </div>
                                        <div class="small text-muted">
                                            <strong>Last Update:</strong>
                                            {{ $followUp->updated_at?->format('d M, Y') ?? '--' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if($followUp->comment)
                                <div class="mt-2 pt-4 border-top">
                                    <label class="text-muted small text-uppercase fw-bold mb-3 d-block" style="letter-spacing: 0.05em;">
                                        <i class="bi bi-chat-left-text text-muted me-1"></i>Internal Comment
                                    </label>
                                    <p class="mb-0 bg-light p-4 rounded-3 border-start border-primary border-4 shadow-sm text-dark">
                                        {{ $followUp->comment }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                @if(isset($followUp) && $followUp->statusHistories()->exists())
                    @include('crm.partials.status-history-table', ['histories' => $followUp->statusHistories])
                @endif
            </div>
        </div>
    </div>

    @push('styles')
        <link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/main.css' : '/css/main.css') }}?v={{ filemtime(public_path('css/main.css')) }}">
    @endpush

@endsection