@extends('layouts.app')

@section('page_title', 'Lead Profile')

@section('content')
<div class="container-fluid p-0">
    {{-- Header Bar --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3 w-100">
        <div class="flex-grow-1 w-100">
            <h1 class="h4 mb-1">Lead Profile</h1>
            <p class="text-muted small mb-0">{{ $lead->name }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-lg-end justify-content-md-end">
            @if($lead->is_converted && $lead->converted_customer_id)
                <a href="{{ route('masters.customers.edit', $lead->converted_customer_id) }}" class="btn btn-success flex-grow-1 flex-md-grow-0">
                    <i class="bi bi-person-check me-1"></i>View Customer
                </a>
            @else
                <form method="POST" action="{{ route('leads.convert', $lead) }}" class="flex-grow-1 flex-md-grow-0">
                    @csrf
                    <button type="submit" class="btn btn-success w-100"><i class="bi bi-person-plus me-1"></i>Convert</button>
                </form>
            @endif
            @can('leads.edit')
            <a href="{{ route('leads.edit', $lead) }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                <i class="bi bi-pencil me-1"></i>Edit
            </a>
            @endcan
            <a href="{{ route('leads.index') }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                <i class="fa-solid fa-angle-left me-1"></i>Back
            </a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            {{-- Basic Info Card --}}
            <div class="card shadow-sm border-0 rounded-4 overflow-hidden detail-view-card">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="fw-bold mb-0 text-primary small text-uppercase" style="letter-spacing: 0.05em;">Lead Information</h6>
                </div>
                <div class="card-body p-4">
                    <div class="detail-view-block">
                        <div class="detail-view-grid">
                            <div class="detail-view-row mb-3">
                                <span class="detail-view-label text-muted small d-block mb-1">Email</span> 
                                <span class="detail-view-value fw-bold text-dark">{{ $lead->email ?? '--' }}</span>
                            </div>
                            <div class="detail-view-row mb-3">
                                <span class="detail-view-label text-muted small d-block mb-1">Phone</span> 
                                <span class="detail-view-value fw-bold text-dark">{{ $lead->phone ?? '--' }}</span>
                            </div>
                            <div class="detail-view-row mb-3">
                                <span class="detail-view-label text-muted small d-block mb-1">Status</span> 
                                <span class="detail-view-value">
                                    <span class="badge bg-light text-dark border">{{ ucfirst(str_replace('_', ' ', $lead->status)) }}</span>
                                </span>
                            </div>
                            <div class="detail-view-row mb-3">
                                <span class="detail-view-label text-muted small d-block mb-1">Source</span> 
                                <span class="detail-view-value text-muted">{{ $lead->leadSource?->name ?? $lead->source ?? '--' }}</span>
                            </div>
                            <div class="detail-view-row">
                                <span class="detail-view-label text-muted small d-block mb-1">Stage</span> 
                                <span class="detail-view-value text-muted">{{ $lead->leadStage?->name ?? '--' }}</span>
                            </div>
                        </div>
                    </div>

                    @php
                        $customFields = \App\Models\CustomField::where('module', 'Lead')->where('is_active', true)->get();
                    @endphp
                    @if($customFields->count() > 0)
                        <hr class="my-4">
                        <h6 class="fw-bold small text-primary text-uppercase mb-3" style="letter-spacing: 0.05em;">Additional Data</h6>
                        @foreach($customFields as $field)
                            @php $val = $lead->getCustomFieldValue($field->name); @endphp
                            @if($val)
                                <div class="bg-light p-3 rounded-3 border mb-2">
                                    <strong class="text-muted small d-block mb-1" style="font-size: 0.65rem;">{{ $field->label }}</strong> 
                                    <span class="text-dark small fw-semibold">{{ $val }}</span>
                                </div>
                            @endif
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm border-0 rounded-4 mb-4 timeline-card">
                <div class="card-body p-3 p-md-4">
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-4 gap-3">
                        <h6 class="fw-bold mb-0 d-flex align-items-center gap-2">
                             <i class="bi bi-clock-history text-primary"></i> Activity Timeline
                        </h6>
                        @can('followups.create')
                        <a href="{{ route('followups.create', ['lead_id' => $lead->id]) }}" class="btn btn-dark-blue btn-sm rounded-pill px-3 shadow-sm w-100 w-sm-auto">
                            <i class="bi bi-plus-lg me-1"></i>Add Activity
                        </a>
                        @endcan
                    </div>

                    <div class="timeline ps-2">
                        {{-- Creation Entry --}}
                        <div class="timeline-item d-flex gap-3 mb-4 position-relative">
                            <div class="timeline-line position-absolute h-100 border-start" style="left: 16px; top: 32px; z-index: 0;"></div>
                            <div class="timeline-icon bg-light text-primary rounded-circle d-flex align-items-center justify-content-center border" style="width: 34px; height: 34px; flex-shrink: 0; min-width: 34px; z-index: 1;">
                                <i class="bi bi-stars"></i>
                            </div>
                            <div class="timeline-content pb-3 border-bottom w-100">
                                <div class="d-flex flex-column flex-sm-row justify-content-between mb-1">
                                    <h6 class="fw-bold mb-0">Lead Created</h6>
                                    <span class="small text-muted">{{ $lead->created_at?->format('d M, Y h:i A') }}</span>
                                </div>
                                <p class="text-muted small mb-0 opacity-75">System initialized the lead profile automatically.</p>
                            </div>
                        </div>

                        {{-- Follow-ups --}}
                        @forelse ($lead->followUps->sortByDesc('scheduled_at') as $fu)
                            <div class="timeline-item d-flex gap-3 mb-4 position-relative">
                                @if(!$loop->last)
                                <div class="timeline-line position-absolute h-100 border-start" style="left: 16px; top: 32px; z-index: 0;"></div>
                                @endif
                                <div class="timeline-icon bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center border" style="width: 34px; height: 34px; flex-shrink: 0; min-width: 34px; z-index: 1;">
                                    @switch($fu->channel)
                                        @case('Call') <i class="bi bi-telephone"></i> @break
                                        @case('WhatsApp') <i class="bi bi-whatsapp"></i> @break
                                        @case('Email') <i class="bi bi-envelope"></i> @break
                                        @case('Meeting') <i class="bi bi-people"></i> @break
                                        @default <i class="bi bi-chat-dots"></i>
                                    @endswitch
                                </div>
                                <div class="timeline-content pb-3 border-bottom w-100">
                                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3">
                                        <div class="flex-grow-1">
                                            <h6 class="fw-bold mb-1 d-flex flex-wrap align-items-center gap-2">
                                                {{ $fu->channel }} Follow-Up
                                                @if($fu->assignedUser)
                                                    <span class="badge bg-light text-muted border fw-normal small">by {{ $fu->assignedUser->name }}</span>
                                                @endif
                                            </h6>
                                            <div class="text-muted small mb-2 opacity-75">
                                                <i class="bi bi-calendar-event me-1"></i>{{ \Illuminate\Support\Carbon::parse($fu->scheduled_at)->format('d M, Y h:i A') }}
                                            </div>
                                            @if($fu->notes)
                                                <div class="bg-light p-3 rounded-3 small text-dark mb-2 border-start border-primary border-3 shadow-sm">
                                                    {{ $fu->notes }}
                                                </div>
                                            @endif
                                            <div class="small fw-semibold text-primary opacity-75">
                                                <i class="bi bi-clock-history me-1"></i>Next: {{ $fu->next_follow_up_at ? \Illuminate\Support\Carbon::parse($fu->next_follow_up_at)->format('d M, Y h:i A') : 'None scheduled' }}
                                            </div>
                                        </div>
                                        <div class="d-flex flex-row flex-md-column align-items-center align-items-md-end gap-2 mt-2 mt-md-0">
                                            <div class="d-flex align-items-center gap-2">
                                                @php
                                                    $rawStatus = $fu->status ?: 'pending';
                                                    $badgeClass = 'bg-warning text-dark';
                                                    $displayStatus = 'Pending';
                                                    if ($rawStatus === 'completed') { $badgeClass = 'bg-success'; $displayStatus = 'Done'; }
                                                    elseif ($rawStatus === 'resheduled') { $badgeClass = 'bg-info text-dark'; $displayStatus = 'Rescheduled'; }
                                                    elseif ($rawStatus === 'cancelled') { $badgeClass = 'bg-danger'; $displayStatus = 'Cancelled'; }
                                                @endphp
                                                <span class="badge {{ $badgeClass }} status-badge px-3 py-2 cursor-pointer" 
                                                      data-id="{{ $fu->id }}" 
                                                      data-status="{{ $rawStatus }}"
                                                      style="cursor: pointer;"
                                                      title="Click to update status">
                                                    {{ $displayStatus }}
                                                </span>
                                            </div>
                                            <div class="btn-group btn-group-sm ms-auto ms-md-0 shadow-sm">
                                                @can('followups.edit')
                                                <a href="{{ route('followups.edit', $fu) }}" class="btn btn-white border" title="Edit"><i class="bi bi-pencil small"></i></a>
                                                @endcan
                                                <form action="{{ route('api.follow_ups.destroy', $fu) }}" method="POST" class="d-inline" data-confirm-message="Delete activity?">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-white border text-danger"><i class="bi bi-trash text-danger small"></i></button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-5 text-muted small px-3">
                                <i class="bi bi-chat-left-dots display-6 d-block mb-3 opacity-25"></i>
                                <p class="mb-0 fw-semibold">No follow-up activities recorded yet.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@push('styles')
    <link rel="stylesheet"
        href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/main.css' : '/css/main.css') }}?v={{ filemtime(public_path('css/main.css')) }}">
@endpush

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const timeline = document.querySelector('.timeline');
        if (!timeline) return;

        timeline.addEventListener('click', function(e) {
            if (e.target.classList.contains('status-badge')) {
                const badge = e.target;
                const id = badge.dataset.id;
                const currentStatus = badge.dataset.status;
                Swal.fire({
                    title: 'Update Status',
                    html: `
                        <div class="mb-3 text-start">
                            <label class="form-label fw-bold small">Status <span class="text-danger">*</span></label>
                            <select id="swal-status-input" class="form-select rounded-3">
                                <option value="pending" ${currentStatus === 'pending' ? 'selected' : ''}>Pending</option>
                                <option value="completed" ${currentStatus === 'completed' || currentStatus === '1' ? 'selected' : ''}>Completed</option>
                                <option value="resheduled" ${currentStatus === 'resheduled' ? 'selected' : ''}>Rescheduled</option>
                                <option value="cancelled" ${currentStatus === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                            </select>
                        </div>
                        <div class="text-start">
                            <label class="form-label fw-bold small">Message <span class="text-danger">*</span></label>
                            <textarea id="swal-comment-input" class="form-control rounded-3" placeholder="Enter your message" rows="3"></textarea>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Save',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#0f172a',
                    cancelButtonColor: '#6c757d',
                    customClass: {
                        popup: 'rounded-4 shadow'
                    },
                    preConfirm: () => {
                        const status = document.getElementById('swal-status-input').value;
                        const comment = document.getElementById('swal-comment-input').value.trim();
                        if (!comment) {
                            Swal.showValidationMessage('You need to write a message!');
                            return false;
                        }
                        return { status: status, comment: comment };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const nextStatus = result.value.status;
                        const comment = result.value.comment;
                        
                        Swal.fire({
                            title: 'Saving...',
                            allowOutsideClick: false,
                            didOpen: () => Swal.showLoading()
                        });

                        fetch('/api/status-history', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                module: 'followup',
                                record_id: id,
                                status: nextStatus,
                                comment: comment
                            })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                const isCompleted = nextStatus === 'completed';
                                
                                // Map internal status value to display label
                                const statusLabels = {
                                    'pending': 'Pending',
                                    'completed': 'Done',
                                    'resheduled': 'Rescheduled',
                                    'cancelled': 'Cancelled'
                                };
                                const displayLabel = statusLabels[nextStatus] || nextStatus;
                                
                                badge.textContent = displayLabel;
                                
                                let badgeClass = 'bg-light text-dark';
                                if (isCompleted) badgeClass = 'bg-success';
                                else if (nextStatus === 'pending') badgeClass = 'bg-warning text-dark';
                                else if (nextStatus === 'cancelled') badgeClass = 'bg-danger';
                                else if (nextStatus === 'resheduled') badgeClass = 'bg-info text-dark';

                                badge.className = `badge ${badgeClass} status-badge px-3 py-2 cursor-pointer`;
                                badge.dataset.status = nextStatus;
                                
                                Swal.close();
                                if (typeof window.showAlert === 'function') {
                                    window.showAlert('success', 'Status updated successfully');
                                }
                            } else {
                                throw new Error(data.message || 'Failed to update status');
                            }
                        })
                        .catch(err => {
                            Swal.close();
                            if (typeof window.showAlert === 'function') {
                                window.showAlert('error', err.message || 'Failed to update status');
                            }
                        });
                    }
                });
            }
        });
    });
</script>
@endpush
