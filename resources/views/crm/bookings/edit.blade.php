@extends('layouts.app')

@section('page_title', 'Bookings - Edit')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h4 mb-1">Edit Booking</h1>
                <p class="text-muted small mb-0">{{ $booking->booking_no }}</p>
            </div>
            <a href="{{ route('bookings.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-angle-left pe-2"></i>Back</a>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-5">
                <form id="bookingForm" method="POST" action="{{ route('bookings.update', $booking->id) }}">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="border-bottom border-2 text-uppercase fw-semibold pb-2 mb-3">Reference Info</div>
                        <div class="col-md-4">
                            <label class="form-label">Booking No</label>
                            <input name="booking_no" value="{{ old('booking_no', $booking->booking_no) }}"
                                class="form-control">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Lead</label>
                            <select name="lead_id" class="form-select">
                                <option value="">-- Select --</option>
                                @foreach ($leads as $lead)
                                    <option value="{{ $lead->id }}" @selected(old('lead_id', $booking->lead_id) == $lead->id)>{{ $lead->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Quotation</label>
                            <input type="number" name="quotation_id" value="{{ old('quotation_id', $booking->quotation_id) }}" class="form-control" placeholder="Quotation ID">
                        </div>

                        <div class="border-bottom border-2 text-uppercase fw-semibold pb-2 mb-3 mt-5">Booking Info</div>
                        <div class="col-md-4">
                            <label class="form-label">Customer</label>
                            <select name="customer_id" id="customer_id" class="form-select" data-search-url="{{ route('api.customers.search') }}" data-search-type="customer" data-search-placeholder="-- Search Customer --">
                                <option value="">-- Select --</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}" data-email="{{ $customer->email }}" data-phone="{{ $customer->phone }}" @selected(old('customer_id', $booking->customer_id) == $customer->id)>{{ $customer->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Agent</label>
                            <select name="agent_id" class="form-select">
                                <option value="">-- Select --</option>
                                @foreach ($agents as $agent)
                                    <option value="{{ $agent->id }}" @selected(old('agent_id', $booking->agent_id) == $agent->id)>{{ $agent->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Tour Package</label>
                            <select name="tour_package_id" class="form-select">
                                <option value="">-- Select --</option>
                                @foreach ($packages as $pkg)
                                    <option value="{{ $pkg->id }}" @selected(old('tour_package_id', $booking->tour_package_id) == $pkg->id)>{{ $pkg->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Currency</label>
                            <select name="currency_id" class="form-select">
                                <option value="">-- Select --</option>
                                @foreach ($currencies as $cur)
                                    <option value="{{ $cur->id }}" @selected(old('currency_id', $booking->currency_id) == $cur->id)>{{ $cur->code }} -
                                        {{ $cur->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="border-bottom border-2 text-uppercase fw-semibold pb-2 mb-3 mt-5">Travel Details</div>
                        <div class="col-md-4">
                            <label class="form-label">Travel Start Date</label>
                            <input type="date" name="travel_start_date" value="{{ old('travel_start_date', $booking->travel_start_date) }}" class="form-control">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Travel End Date</label>
                            <input type="date" name="travel_end_date" value="{{ old('travel_end_date', $booking->travel_end_date) }}" class="form-control">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Adults</label>
                            <input type="number" name="adults" value="{{ old('adults', $booking->adults) }}" class="form-control">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Children</label>
                            <input type="number" name="children" value="{{ old('children', $booking->children) }}" class="form-control">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Rooms</label>
                            <input type="number" name="rooms" value="{{ old('rooms', $booking->rooms) }}" class="form-control">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Total Amount</label>
                            <input type="number" step="0.01" name="total_amount" value="{{ old('total_amount', $booking->total_amount) }}" class="form-control">
                        </div>

                        <div class="border-bottom border-2 text-uppercase fw-semibold pb-2 mb-3 mt-5">Status & Notes</div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                @foreach (['pending' => 'Pending', 'confirmed' => 'Confirmed', 'cancelled' => 'Cancelled', 'completed' => 'Completed'] as $k => $v)
                                    <option value="{{ $k }}" @selected(old('status', $booking->status) === $k)>{{ $v }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" @checked(old('is_active', $booking->is_active))>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" rows="3" class="form-control">{{ old('notes', $booking->notes) }}</textarea>
                        </div>

                        <div class="border-bottom border-2 text-uppercase fw-semibold pb-2 mb-3 mt-5">Travelers / Pax Details</div>
                        <div id="passengerList">
                            @foreach($booking->passengers as $index => $pax)
                                <div class="passenger-item border rounded p-3 mb-3 bg-light shadow-sm" data-index="{{ $index }}">
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label small fw-bold">First Name</label>
                                            <input type="text" name="passengers[{{ $index }}][first_name]" class="form-control form-control-sm" value="{{ $pax->first_name }}" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small fw-bold">Last Name</label>
                                            <input type="text" name="passengers[{{ $index }}][last_name]" class="form-control form-control-sm" value="{{ $pax->last_name }}">
                                        </div>
                                        <div class="col-md-1">
                                            <label class="form-label small fw-bold">Age</label>
                                            <input type="number" name="passengers[{{ $index }}][age]" class="form-control form-control-sm" value="{{ $pax->age }}">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small fw-bold">Passport #</label>
                                            <input type="text" name="passengers[{{ $index }}][passport_no]" class="form-control form-control-sm" value="{{ $pax->passport_no }}">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small fw-bold">Nationality</label>
                                            <input type="text" name="passengers[{{ $index }}][nationality]" class="form-control form-control-sm" value="{{ $pax->nationality }}">
                                        </div>
                                        <div class="col-md-1 d-flex align-items-end justify-content-center">
                                            <button type="button" class="btn btn-sm crm-action-btn remove-pax"><i class="bi bi-trash text-danger"></i></button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="mb-3">
                            <button type="button" class="btn btn-sm crm-action-btn" id="addPaxBtn">
                                <i class="bi bi-person-plus me-1"></i>Add Passenger
                            </button>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-dark-blue">Update Booking</button>
                        <a href="{{ route('bookings.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <script>
        $(document).ready(function() {
            // Add Pax
            $('#addPaxBtn').click(function() {
                var index = $('.passenger-item').length;
                var html = `
                    <div class="passenger-item border rounded p-3 mb-3 bg-light shadow-sm" data-index="${index}">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">First Name</label>
                                <input type="text" name="passengers[${index}][first_name]" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Last Name</label>
                                <input type="text" name="passengers[${index}][last_name]" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-1">
                                <label class="form-label small fw-bold">Age</label>
                                <input type="number" name="passengers[${index}][age]" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Passport #</label>
                                <input type="text" name="passengers[${index}][passport_no]" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Nationality</label>
                                <input type="text" name="passengers[${index}][nationality]" class="form-control form-control-sm" value="Indian">
                            </div>
                            <div class="col-md-1 d-flex align-items-end justify-content-center">
                                <button type="button" class="btn btn-sm crm-action-btn remove-pax"><i class="bi bi-trash text-danger"></i></button>
                            </div>
                        </div>
                    </div>
                `;
                $('#passengerList').append(html);
            });

            // Remove Pax
            $(document).on('click', '.remove-pax', function() {
                $(this).closest('.passenger-item').remove();
            });

            $("#bookingForm").submit(function(e) {
                e.preventDefault();

                var formData = new FormData(this);

                // remove previous errors
                $('.is-invalid').removeClass('is-invalid');
                $('.ts-wrapper.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').remove();

                // Re-index passengers
                $('.passenger-item').each(function(index) {
                    $(this).find('input').each(function() {
                        var name = $(this).attr('name');
                        if (name) {
                            var newName = name.replace(/passengers\[\d+\]/, 'passengers[' + index + ']');
                            $(this).attr('name', newName);
                        }
                    });
                });

                $.ajax({
                    type: "POST",
                    url: "{{ route('bookings.update', $booking->id) }}",
                    data: formData,
                    processData: false,
                    contentType: false,

                    success: function(response) {
                        // console.log(response);
                        // redirect after success
                        window.location.href = "{{ route('bookings.index') }}";
                    },

                    error: function(error) {

                        if (error.status === 422) {

                            $.each(error.responseJSON.errors, function(key, value) {

                                var input = $('[name="' + key + '"]');

                                input.addClass('is-invalid');
                                if (input.is('select')) {
                                    input.next('.ts-wrapper').addClass('is-invalid');
                                }
                                input.after('<div class="invalid-feedback">' + value[
                                    0] + '</div>');
                            });

                        }
                    }
                });

            });

        });

        $(document).on('input change', '#bookingForm input, #bookingForm select, #bookingForm textarea', function() {
            $(this).removeClass('is-invalid');
            if ($(this).is('select')) {
                $(this).next('.ts-wrapper').removeClass('is-invalid');
            }
            $(this).siblings('.invalid-feedback').remove();
        });
    </script>
@endpush
