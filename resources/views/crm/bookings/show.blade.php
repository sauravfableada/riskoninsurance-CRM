@extends('layouts.app')

@section('page_title', 'Bookings - Show')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-1">Bookings</h1>
            <p class="text-muted small mb-0">Convert confirmed quotations into bookings and manage operations.</p>
        </div>
        <div class="d-flex gap-2">
            @if($booking->status !== 'cancelled')
                <button type="button" class="btn crm-action-btn btn-sm" data-bs-toggle="modal" data-bs-target="#amendBookingModal">
                    <i class="bi bi-pencil-square me-1"></i> Amend Booking
                </button>
                <button type="button" class="btn crm-action-btn btn-sm" data-bs-toggle="modal" data-bs-target="#cancelBookingModal">
                    Cancel Booking
                </button>
            @endif
            <a href="{{ route('bookings.refunds', $booking) }}" class="btn btn-outline-warning btn-sm">
                Manage Refunds
            </a>
            <a href="{{ route('bookings.costs', $booking) }}" class="btn btn-outline-dark-blue btn-sm">
                Manage Supplier Costs
            </a>
            <a href="{{ route('bookings.edit', $booking) }}" class="btn btn-dark-blue btn-sm">
                Edit
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body p-5">
            <div class="row">
                <div class="col-12">
                    <h2 class="h4 mb-3">Booking Details</h2>
                </div>
            </div>
            <div class="row g-4 mt-3">
                <!-- Main Info -->
                <div class="col-lg-8">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <p class="text-muted small mb-0">Booking Number</p>
                            <h5 class="fw-bold">{{ $booking->booking_no }}</h5>
                        </div>
                        <div class="col-md-6">
                            <p class="text-muted small mb-0">Status</p>
                            @php
                                $bookingStatusClass = match ($booking->status) {
                                    'confirmed' => 'bg-success',
                                    'cancelled' => 'bg-danger',
                                    default => 'bg-warning text-dark',
                                };
                            @endphp
                            <span class="badge crm-status-pill {{ $bookingStatusClass }} rounded-pill">
                                {{ ucfirst($booking->status) }}
                            </span>
                        </div>
                        <div class="col-md-6">
                            <p class="text-muted small mb-0">Travel Dates</p>
                            <h6 class="fw-bold">{{ $booking->travel_start_date }} to {{ $booking->travel_end_date }}</h6>
                        </div>
                        <div class="col-md-6">
                            <p class="text-muted small mb-0">Pax Count</p>
                            <h6 class="fw-bold">{{ $booking->adults }} Adults, {{ $booking->children }} Children</h6>
                        </div>
                    </div>

                    <div class="mt-5">
                        <h6 class="fw-bold border-bottom pb-2 mb-3">Traveler List</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover border small">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Name</th>
                                        <th>Age</th>
                                        <th>Passport</th>
                                        <th>Nationality</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($booking->passengers as $pax)
                                        <tr>
                                            <td>{{ $pax->first_name }} {{ $pax->last_name }}</td>
                                            <td>{{ $pax->age }}</td>
                                            <td>{{ $pax->passport_no }}</td>
                                            <td>{{ $pax->nationality }}</td>
                                        </tr>
                                    @endforeach
                                    @if($booking->passengers->isEmpty())
                                        <tr>
                                            <td colspan="4" class="text-center py-3 text-muted italic">No travelers listed yet.</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h6 class="fw-bold border-bottom pb-2 mb-3">Itinerary Summary</h6>
                        @if($booking->itinerary)
                            <div class="list-group">
                                @foreach($booking->itinerary->days as $day)
                                    <div class="list-group-item">
                                        <div class="fw-bold">Day {{ $day->day_number }}: {{ $day->title }}</div>
                                        <small class="text-muted">{{ $day->description }}</small>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="alert alert-light border italic small text-muted">
                                No itinerary built for this booking. 
                                <a href="{{ route('bookings.itinerary', $booking) }}" class="fw-bold">Create Itinerary Now</a>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Financial Sidebar -->
                <div class="col-lg-4">
                    <div class="card border bg-light shadow-none">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3">Operational Stats</h6>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small">Total Cost:</span>
                                <span class="fw-bold text-primary">{{ number_format($booking->total_amount, 2) }}</span>
                            </div>
                            
                            <hr class="my-3">
                            
                            <h6 class="fw-bold mb-3">Documents</h6>
                            <div class="d-grid gap-2">
                                <a href="{{ route('bookings.itinerary', $booking) }}" class="btn btn-sm btn-outline-dark-blue">
                                    <i class="bi bi-calendar-check me-2"></i>Day-wise Itinerary
                                </a>
                                @if($booking->invoices->isNotEmpty())
                                    @foreach($booking->invoices as $inv)
                                        <a href="{{ route('invoices.show', $inv) }}" class="btn btn-sm btn-outline-success">
                                            <i class="bi bi-receipt me-2"></i>View Invoice ({{ $inv->status }})
                                        </a>
                                    @endforeach
                                @else
                                    <a href="{{ route('bookings.invoice.create', $booking) }}" class="btn btn-sm btn-success">
                                        <i class="bi bi-receipt me-2"></i>Generate Invoice
                                    </a>
                                @endif
                                <a href="{{ route('bookings.voucher', $booking) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-printer me-2"></i>Generate Voucher
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- Trip Progress Checklist --}}
                    <div class="card border shadow-none mt-4">
                        <div class="card-header bg-white fw-bold py-3">
                            <i class="bi bi-card-checklist me-2 text-primary"></i>Trip Progress Checklist
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                @forelse($booking->checklists as $item)
                                    <div class="list-group-item d-flex align-items-center justify-content-between py-2 border-0">
                                        <div class="form-check mb-0">
                                            <input class="form-check-input checklist-toggle" type="checkbox" 
                                                id="chk_{{ $item->id }}" 
                                                data-id="{{ $item->id }}"
                                                {{ $item->is_completed ? 'checked' : '' }}>
                                            <label class="form-check-label small {{ $item->is_completed ? 'text-decoration-line-through text-muted' : 'fw-semibold' }}" for="chk_{{ $item->id }}">
                                                {{ $item->task_name }}
                                            </label>
                                        </div>
                                        <span class="badge bg-light text-muted border small completed-at-badge" style="{{ !$item->is_completed ? 'display:none;' : '' }}">
                                            {{ $item->is_completed ? $item->completed_at->format('d M') : '' }}
                                        </span>
                                    </div>
                                @empty
                                    <div class="p-3 text-center text-muted small italic">No checklist items.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                    {{-- Amendment History --}}
                    @if($booking->amendments->isNotEmpty())
                    <div class="mt-5">
                        <h6 class="fw-bold border-bottom pb-2 mb-3">Amendment History</h6>
                        <div class="timeline small">
                            @foreach($booking->amendments->sortByDesc('created_at') as $amendment)
                                <div class="mb-3 ps-3 border-start border-2 border-primary position-relative">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-bold text-dark">{{ ucfirst($amendment->type) }}</span>
                                        <span class="text-muted opacity-75">{{ $amendment->created_at->format('d M Y, h:i A') }}</span>
                                    </div>
                                    <p class="mb-1 text-dark">{{ $amendment->reason }}</p>
                                    @if(!empty($amendment->old_data) || !empty($amendment->new_data))
                                        <div class="bg-light p-2 rounded mt-2 border">
                                            @foreach($amendment->new_data as $field => $newValue)
                                                <div class="d-flex gap-2 mb-1">
                                                    <span class="text-muted">{{ str_replace('_', ' ', ucfirst($field)) }}:</span>
                                                    <span class="text-danger text-decoration-line-through">{{ $amendment->old_data[$field] ?? '--' }}</span>
                                                    <i class="bi bi-arrow-right mx-1"></i>
                                                    <span class="text-success fw-bold">{{ $newValue }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                    @if($amendment->amendment_fee > 0)
                                        <div class="small text-primary mt-1">
                                            <i class="bi bi-cash-stack me-1"></i> Amendment Fee Charged: {{ number_format($amendment->amendment_fee, 2) }}
                                        </div>
                                    @endif
                                    <div class="text-muted opacity-75 mt-1" style="font-size: 0.75rem;">
                                        Logged by: {{ $amendment->creator->name ?? 'System' }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>

{{-- Amendment Modal --}}
<div class="modal fade" id="amendBookingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="amendForm" action="{{ route('bookings.amend', $booking) }}" method="POST">
            @csrf
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Amend Booking: {{ $booking->booking_no }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Amendment Type</label>
                            <select name="type" class="form-select" required>
                                <option value="reschedule">Date Reschedule</option>
                                <option value="pax_change">Pax Count Change</option>
                                <option value="itinerary_update">Itinerary Update</option>
                                <option value="other">Other Amendment</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Amendment Fee (Optional)</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" name="amendment_fee" class="form-control" step="0.01" value="0.00">
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Reason for Amendment</label>
                            <textarea name="reason" class="form-control" rows="2" required placeholder="Explain why this change is being made..."></textarea>
                        </div>
                        
                        <div class="col-12 mt-4">
                            <h6 class="fw-bold text-primary border-bottom pb-2">Updated Booking Details</h6>
                            <p class="text-muted small mb-3">Leave fields unchanged if they are not part of this amendment.</p>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">New Start Date</label>
                            <input type="date" name="travel_start_date" class="form-control" value="{{ $booking->travel_start_date }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">New End Date</label>
                            <input type="date" name="travel_end_date" class="form-control" value="{{ $booking->travel_end_date }}">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Adults</label>
                            <input type="number" name="adults" class="form-control" value="{{ $booking->adults }}" min="1">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Children</label>
                            <input type="number" name="children" class="form-control" value="{{ $booking->children }}" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Rooms</label>
                            <input type="number" name="rooms" class="form-control" value="{{ $booking->rooms }}" min="1">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-link text-muted" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark-blue px-4">Submit Amendment</button>
                </div>
            </div>
        </form>
    </div>
</div>
@if($booking->status !== 'cancelled')
{{-- Cancellation Modal --}}
<div class="modal fade" id="cancelBookingModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="cancelForm" action="{{ route('bookings.cancel', $booking) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">Cancel Booking: {{ $booking->booking_no }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning small">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        Cancelling a booking is permanent. All associated operations will be halted.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cancellation Reason</label>
                        <textarea name="cancellation_reason" class="form-control" rows="3" required placeholder="Why is this booking being cancelled?"></textarea>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Cancellation Fee</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" name="cancellation_fee" class="form-control" step="0.01" value="0.00" required>
                        </div>
                        <small class="text-muted italic">This fee will be deducted from any potential refund.</small>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep Booking</button>
                    <button type="submit" class="btn btn-danger">Confirm Cancellation</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('cancelForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const form = this;
    const formData = new FormData(form);

    window.crmConfirm('Are you absolutely sure you want to cancel this booking?', {
        confirmButtonText: 'Yes, cancel booking'
    }).then(function (result) {
        if (!result.isConfirmed) {
            return;
        }

        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                location.reload();
            } else {
                window.crmAlert('Error cancelling booking: ' + (data.message || 'Unknown error'), 'error');
            }
        });
    });
});

// Checklist Toggle Logic
document.querySelectorAll('.checklist-toggle').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const id = this.dataset.id;
        const label = this.nextElementSibling;
        const badge = this.closest('.list-group-item').querySelector('.completed-at-badge');
        
        fetch(`/checklists/${id}/toggle`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                if (data.is_completed) {
                    label.classList.add('text-decoration-line-through', 'text-muted');
                    label.classList.remove('fw-semibold');
                    badge.innerText = data.completed_at.split(' ')[0] + ' ' + data.completed_at.split(' ')[1];
                    badge.style.display = 'inline-block';
                } else {
                    label.classList.remove('text-decoration-line-through', 'text-muted');
                    label.classList.add('fw-semibold');
                    badge.style.display = 'none';
                }
            }
        });
    });
});
// Amendment Form Submission
document.getElementById('amendForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const form = this;
    const formData = new FormData(form);
    const btn = form.querySelector('button[type="submit"]');
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
    
    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            location.reload();
        } else {
            window.crmAlert('Error: ' + (data.message || 'Unknown error occurred'));
            btn.disabled = false;
            btn.innerHTML = 'Submit Amendment';
        }
    })
    .catch(err => {
        console.error(err);
        window.crmAlert('An error occurred. Please try again.');
        btn.disabled = false;
        btn.innerHTML = 'Submit Amendment';
    });
});
</script>
@endif

@endsection
