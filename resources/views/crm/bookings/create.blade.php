@extends('layouts.app')

@section('page_title', 'Bookings - Create')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h4 mb-1">Create Booking</h1>
                <p class="text-muted small mb-0">Add a new booking manually or from a quotation.</p>
            </div>
            <a href="{{ route('bookings.index') }}" class="btn btn-dark-blue"><i class="fa-solid fa-angle-left pe-2"></i>Back</a>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <form id="bookingForm" method="POST" action="{{ route('bookings.store') }}">
                    @csrf

                    @php
                        $prefillLeadId = old('lead_id', $quotation?->lead_id);
                        $prefillPackageId = old('tour_package_id', $quotation?->tour_package_id);
                        $prefillQuotationId = old('quotation_id', $quotation?->id);
                        $prefillTotal = old('total_amount', $quotation?->total_amount ?? '');
                    @endphp

                    <div class="row g-3">
                        <div class="border-bottom border-2 text-uppercase fw-semibold pb-2 mb-3">Reference Info</div>
                        <div class="col-md-4">
                            <label class="form-label">Booking No</label>
                            <input name="booking_no" value="{{ old('booking_no') }}" class="form-control" placeholder="Auto-generated if empty">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Lead</label>
                            <select name="lead_id" class="form-select">
                                <option value="">-- Select --</option>
                                @foreach ($leads as $lead)
                                    <option value="{{ $lead->id }}" @selected((string) $prefillLeadId === (string) $lead->id)>
                                        {{ $lead->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Quotation</label>
                            <input type="number" name="quotation_id" value="{{ $prefillQuotationId }}" class="form-control" placeholder="Quotation ID">
                        </div>

                        <div class="border-bottom border-2 text-uppercase fw-semibold pb-2 mb-3 mt-5">Booking Info</div>
                        <div class="col-md-4">
                            <label class="form-label">Customer</label>
                            <select name="customer_id" id="customer_id" class="form-select" data-search-url="{{ route('api.customers.search') }}" data-search-type="customer" data-search-placeholder="-- Search Customer --">
                                <option value="">-- Select --</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}" data-email="{{ $customer->email }}" data-phone="{{ $customer->phone }}" @selected(old('customer_id') == $customer->id)>
                                        {{ $customer->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Agent</label>
                            <select name="agent_id" class="form-select">
                                <option value="">-- Select --</option>
                                @foreach ($agents as $agent)
                                    <option value="{{ $agent->id }}" @selected(old('agent_id') == $agent->id)>{{ $agent->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Tour Package</label>
                            <select name="tour_package_id" class="form-select">
                                <option value="">-- Select --</option>
                                @foreach ($packages as $pkg)
                                    <option value="{{ $pkg->id }}" @selected((string) $prefillPackageId === (string) $pkg->id)>
                                        {{ $pkg->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Currency</label>
                            <select name="currency_id" class="form-select">
                                <option value="">-- Select --</option>
                                @foreach ($currencies as $cur)
                                    <option value="{{ $cur->id }}" @selected(old('currency_id') == $cur->id)>{{ $cur->code }} -
                                        {{ $cur->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="border-bottom border-2 text-uppercase fw-semibold pb-2 mb-3 mt-5">Travel Details</div>
                        <div class="col-md-4">
                            <label class="form-label">Travel Start Date</label>
                            <input type="date" name="travel_start_date" value="{{ old('travel_start_date') }}"
                                class="form-control">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Travel End Date</label>
                            <input type="date" name="travel_end_date" value="{{ old('travel_end_date') }}"
                                class="form-control">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Adults</label>
                            <input type="number" name="adults" value="{{ old('adults') }}" class="form-control">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Children</label>
                            <input type="number" name="children" value="{{ old('children') }}" class="form-control">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Rooms</label>
                            <input type="number" name="rooms" value="{{ old('rooms') }}" class="form-control">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Total Amount</label>
                            <input type="number" step="0.01" name="total_amount" value="{{ $prefillTotal ?: 0 }}" class="form-control">
                        </div>


                        <div class="border-bottom border-2 text-uppercase fw-semibold pb-2 mb-3 mt-5">Status & Notes</div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                @foreach (['pending' => 'Pending', 'confirmed' => 'Confirmed', 'cancelled' => 'Cancelled', 'completed' => 'Completed'] as $k => $v)
                                    <option value="{{ $k }}" @selected(old('status', 'pending') === $k)>{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check">
                                <label class="form-check-label order-2" for="is_active">Active</label>
                                <input class="form-check-input order-1" type="checkbox" name="is_active" id="is_active" value="1" @checked(old('is_active', true))>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" rows="3" class="form-control">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    <div class="mt-4 d-flex justify-content-end gap-2">
                        <button type="submit" class="btn btn-dark-blue">Save Booking</button>
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

        $(document).ready(function () {

            $("#bookingForm").submit(function (e) {
                e.preventDefault();

                var formData = new FormData(this);

                // remove previous errors
                $('.is-invalid').removeClass('is-invalid');
                $('.ts-wrapper.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').remove();

                $.ajax({
                    type: "POST",
                    url: "{{ route('bookings.store') }}",
                    data: formData,
                    processData: false,
                    contentType: false,

                    success: function (response) {
                        // console.log(response);
                        // redirect after success
                        window.location.href = "{{ route('bookings.index') }}";
                    },

                    error: function (error) {

                        if (error.status === 422) {

                            $.each(error.responseJSON.errors, function (key, value) {

                                var input = $('[name="' + key + '"]');
                                
                                input.addClass('is-invalid');
                                if (input.is('select')) {
                                    input.next('.ts-wrapper').addClass('is-invalid');
                                }
                                input.after('<div class="invalid-feedback">' + value[0] + '</div>');
                            });

                        }
                    }
                });

            });

        });

        $(document).on('input change', '#bookingForm input, #bookingForm select, #bookingForm textarea', function () {
            $(this).removeClass('is-invalid');
            if ($(this).is('select')) {
                $(this).next('.ts-wrapper').removeClass('is-invalid');
            }
            $(this).siblings('.invalid-feedback').remove();
        });
    </script>
@endpush
