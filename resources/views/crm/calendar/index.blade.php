@extends('layouts.app')

@section('page_title', 'Trip Calendar')

@push('styles')
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.css' rel='stylesheet' />
<style>
    .fc-event {
        cursor: pointer;
        padding: 2px 4px;
        border-radius: 4px;
        border: none;
    }
    .fc-v-event {
        background-color: var(--crm-primary);
    }
    #calendar {
        background: white;
        padding: 20px;
        border-radius: var(--crm-border-radius);
        box-shadow: var(--crm-shadow);
    }
    .fc-toolbar-title {
        font-size: 1.25rem !important;
        font-weight: 700;
        color: var(--crm-dark);
    }
    .fc-button-primary {
        background-color: var(--crm-primary) !important;
        border-color: var(--crm-primary) !important;
    }
    .fc-button-primary:hover {
        background-color: var(--crm-primary-dark) !important;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Trip Calendar</h1>
            <p class="text-muted small">Visual schedule of all arrivals and departures.</p>
        </div>
        <div>
            <div class="d-flex gap-2">
                <span class="badge bg-primary px-3 py-2"><i class="bi bi-circle-fill me-1 tiny"></i> Arrivals</span>
                <span class="badge bg-danger px-3 py-2"><i class="bi bi-circle-fill me-1 tiny"></i> Departures</span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div id='calendar'></div>
        </div>
    </div>
</div>

<!-- Event Details Modal -->
<div class="modal fade" id="eventModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light border-0">
                <h5 class="modal-title fw-bold" id="modalTitle">Trip Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-center mb-4">
                    <div id="modalIcon" class="rounded-circle p-3 me-3">
                        <i class="bi bi-calendar-event fs-4 text-white"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 text-muted small" id="modalType">Arrival</h6>
                        <h5 class="fw-bold mb-0" id="modalCustomer">Customer Name</h5>
                    </div>
                </div>
                
                <div class="row g-3">
                    <div class="col-6">
                        <label class="text-muted small d-block">Booking No</label>
                        <span class="fw-bold" id="modalBookingNo">BK-XXXX</span>
                    </div>
                    <div class="col-6">
                        <label class="text-muted small d-block">Passengers</label>
                        <span class="fw-bold" id="modalPax">0</span>
                    </div>
                    <div class="col-12">
                        <hr class="my-2">
                    </div>
                    <div class="col-12">
                        <div class="d-grid mt-2">
                            <a href="#" id="modalUrl" class="btn btn-dark-blue">
                                <i class="bi bi-eye me-2"></i>View Full Booking
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var eventModal = new bootstrap.Modal(document.getElementById('eventModal'));
    
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,listMonth'
        },
        events: "{{ route('api.calendar.events') }}",
        eventClick: function(info) {
            info.jsEvent.preventDefault(); // don't let the browser navigate
            
            const props = info.event.extendedProps;
            const type = props.type;
            
            // Populate Modal
            document.getElementById('modalTitle').innerText = type + ' Details';
            document.getElementById('modalType').innerText = type;
            document.getElementById('modalCustomer').innerText = props.customer;
            document.getElementById('modalBookingNo').innerText = info.event.title.split('(')[1].replace(')', '');
            document.getElementById('modalPax').innerText = props.pax + ' Pax';
            document.getElementById('modalUrl').href = info.event.url;
            
            // Style Modal
            const iconBox = document.getElementById('modalIcon');
            if(type === 'Arrival') {
                iconBox.style.backgroundColor = '#2196f3';
            } else {
                iconBox.style.backgroundColor = '#f44336';
            }
            
            eventModal.show();
        },
        loading: function(isLoading) {
            if (isLoading) {
                calendarEl.style.opacity = '0.5';
            } else {
                calendarEl.style.opacity = '1';
            }
        }
    });
    calendar.render();
});
</script>
@endpush
