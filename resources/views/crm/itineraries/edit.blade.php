@extends('layouts.masters')

@section('page_title', 'Itinerary Builder')

@section('masters_content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h5 mb-0 text-muted fw-semibold">Itinerary Builder</h2>
        <small class="text-muted">Building for: {{ $owner->name ?? $owner->booking_no }} ({{ ucfirst($ownerType) }})</small>
    </div>
    <div>
        <button type="button" class="btn btn-dark-blue" id="saveItinerary">Save Changes</button>
        <a href="{{ $ownerType == 'package' ? route('packages.index') : ($ownerType == 'quotation' ? route('quotations.index') : route('bookings.index')) }}" class="btn btn-link text-decoration-none">Back</a>
    </div>
</div>

<div id="itineraryApp">
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label fw-bold">Itinerary Title</label>
                <input type="text" id="itineraryTitle" class="form-control" value="{{ $itinerary->title }}">
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">Overview Description</label>
                <textarea id="itineraryDesc" class="form-control" rows="2">{{ $itinerary->description }}</textarea>
            </div>
        </div>
    </div>

    <div id="daysList">
        @foreach($itinerary->days as $index => $day)
            <div class="day-card card border-0 shadow-sm mb-4" data-day="{{ $day->day_number }}">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-primary">Day {{ $day->day_number }}</h6>
                    <button type="button" class="btn btn-sm crm-action-btn remove-day">Remove Day</button>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">Day Title</label>
                            <input type="text" class="form-control day-title" value="{{ $day->title }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Meals</label>
                            <input type="text" class="form-control day-meals" value="{{ $day->meals }}" placeholder="e.g. Breakfast, Lunch">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Activities / Description</label>
                        <textarea class="form-control day-desc" rows="2">{{ $day->description }}</textarea>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="text-center mb-5">
        <button type="button" class="btn crm-action-btn btn-lg rounded-pill px-5" id="addDay">
            <i class="bi bi-plus-circle me-2"></i>Add Next Day
        </button>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const daysList = document.getElementById('daysList');
        const addDayBtn = document.getElementById('addDay');
        const saveBtn = document.getElementById('saveItinerary');
        
        // Add Day Logic
        addDayBtn.addEventListener('click', function() {
            const nextDayNum = document.querySelectorAll('.day-card').length + 1;
            const dayHtml = `
                <div class="day-card card border-0 shadow-sm mb-4" data-day="${nextDayNum}">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-primary">Day ${nextDayNum}</h6>
                        <button type="button" class="btn btn-sm crm-action-btn remove-day">Remove Day</button>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label class="form-label small fw-bold">Day Title</label>
                                <input type="text" class="form-control day-title" placeholder="e.g. Arrival and Leisure">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Meals</label>
                                <input type="text" class="form-control day-meals" placeholder="e.g. Breakfast, Dinner">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Activities / Description</label>
                            <textarea class="form-control day-desc" rows="2" placeholder="Describe the plan for this day..."></textarea>
                        </div>
                    </div>
                </div>
            `;
            daysList.insertAdjacentHTML('beforeend', dayHtml);
        });

        // Remove Day Logic
        daysList.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-day')) {
                window.crmConfirm('Are you sure you want to remove this day?').then(function (result) {
                    if (!result.isConfirmed) {
                        return;
                    }
                    e.target.closest('.day-card').remove();
                    reorderDays();
                });
            }
        });

        function reorderDays() {
            document.querySelectorAll('.day-card').forEach((card, index) => {
                const num = index + 1;
                card.dataset.day = num;
                card.querySelector('h6').textContent = 'Day ' + num;
            });
        }

        // Save Logic
        saveBtn.addEventListener('click', function() {
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

            const days = [];
            document.querySelectorAll('.day-card').forEach(card => {
                days.push({
                    title: card.querySelector('.day-title').value,
                    meals: card.querySelector('.day-meals').value,
                    description: card.querySelector('.day-desc').value,
                    items: [] // For now keeping items empty, can expand later
                });
            });

            const payload = {
                title: document.getElementById('itineraryTitle').value,
                description: document.getElementById('itineraryDesc').value,
                days: days,
                _token: '{{ csrf_token() }}'
            };

            const updateUrl = @if($ownerType == 'package') 
                                '{{ route('packages.itinerary.update', $owner) }}'
                              @elseif($ownerType == 'quotation')
                                '{{ route('quotations.itinerary.update', $owner) }}'
                              @else
                                '{{ route('bookings.itinerary.update', $owner) }}'
                              @endif;

            fetch(updateUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload)
            })
            .then(response => response.json())
            .then(data => {
                window.crmAlert('Itinerary saved successfully!');
                location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
                window.crmAlert('Failed to save itinerary.');
            })
            .finally(() => {
                saveBtn.disabled = false;
                saveBtn.innerHTML = 'Save Changes';
            });
        });
    });
</script>
@endpush
@endsection
