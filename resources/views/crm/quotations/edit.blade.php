@extends('layouts.app')

@section('page_title', 'Quotations - Edit')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-1">Edit Quotation: {{ $quotation->reference }}</h1>
            <p class="text-muted small mb-0">Update proposal details and costs.</p>
        </div>
        <div>
            @if($quotation->itinerary)
                <a href="{{ route('bookings.itinerary', $quotation) }}" class="btn crm-action-btn btn-sm me-2">
                    <i class="bi bi-calendar-event"></i> Customize Attached Itinerary
                </a>
            @endif
            <a href="{{ route('quotations.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left pe-2"></i>Back
            </a>
        </div>
    </div>

    <form id="quotationForm" action="{{ route('quotations.update', $quotation) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                        <h6 class="mb-0 fw-bold">Cost Breakdown / Line Items</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-borderless align-middle" id="itemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Description</th>
                                        <th style="width: 120px;">Qty</th>
                                        <th style="width: 150px;">Unit Price</th>
                                        <th style="width: 150px;">Total</th>
                                        <th style="width: 50px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="itemsContainer">
                                    @forelse($quotation->items as $index => $item)
                                    <tr class="item-row">
                                        <td>
                                            <input type="text" name="items[{{ $index }}][description]" class="form-control" value="{{ $item->description }}" required>
                                        </td>
                                        <td>
                                            <input type="number" name="items[{{ $index }}][quantity]" class="form-control qty" value="{{ $item->quantity }}" min="1" required>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" name="items[{{ $index }}][unit_price]" class="form-control price" value="{{ $item->unit_price }}" min="0" required>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" name="items[{{ $index }}][total_price]" class="form-control total-price" value="{{ $item->total_price }}" readonly>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm crm-action-btn remove-item"><i class="bi bi-trash text-danger"></i></button>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr class="item-row">
                                        <td>
                                            <input type="text" name="items[0][description]" class="form-control" placeholder="e.g. Flight Tickets" required>
                                        </td>
                                        <td>
                                            <input type="number" name="items[0][quantity]" class="form-control qty" value="1" min="1" required>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" name="items[0][unit_price]" class="form-control price" value="0.00" min="0" required>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" name="items[0][total_price]" class="form-control total-price" value="0.00" readonly>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm crm-action-btn remove-item"><i class="bi bi-trash text-danger"></i></button>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="5">
                                            <button type="button" class="btn btn-sm crm-action-btn" id="addItemBtn">
                                                <i class="bi bi-plus-circle me-1"></i> Add Line Item
                                            </button>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h6 class="mb-3 fw-bold">Terms & Notes</h6>
                        <textarea name="notes" rows="4" class="form-control">{{ $quotation->notes }}</textarea>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm border-0 sticky-top" style="top: 80px; z-index: 1;">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                        <h6 class="mb-0 fw-bold">Quotation Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Reference</label>
                            <input type="text" name="reference" class="form-control" value="{{ $quotation->reference }}" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Lead / Customer</label>
                            <select name="lead_id" class="form-select" required>
                                @foreach ($leads as $lead)
                                    <option value="{{ $lead->id }}" @selected($quotation->lead_id == $lead->id)>{{ $lead->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tour Package</label>
                            <select name="tour_package_id" class="form-select">
                                <option value="">-- Custom (No Package) --</option>
                                @foreach ($packages as $pkg)
                                    <option value="{{ $pkg->id }}" @selected($quotation->tour_package_id == $pkg->id)>{{ $pkg->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Valid Until</label>
                            <input type="date" name="valid_until" class="form-control" value="{{ $quotation->valid_until ? date('Y-m-d', strtotime($quotation->valid_until)) : '' }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" class="form-select">
                                <option value="quotation" @selected($quotation->status == 'quotation')>Quotation (Draft)</option>
                                <option value="estimate" @selected($quotation->status == 'estimate')>Estimate (Sent)</option>
                                <option value="confirmed" @selected($quotation->status == 'confirmed')>Confirmed</option>
                                <option value="cancelled" @selected($quotation->status == 'cancelled')>Cancelled</option>
                            </select>
                        </div>

                        <div class="border-top pt-3 mt-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="fw-bold">Grand Total</span>
                                <h4 class="mb-0 text-primary">
                                    <span class="fs-6 text-muted">INR</span> <span id="grandTotalDisplay">{{ number_format($quotation->total_amount, 2, '.', '') }}</span>
                                </h4>
                                <input type="hidden" name="total_amount" id="grandTotalInput" value="{{ $quotation->total_amount }}">
                            </div>
                            <button type="submit" class="btn btn-dark-blue w-100 py-2 fw-semibold" id="saveBtn">
                                Update Quotation
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function () {
        let itemIndex = {{ max(count($quotation->items), 1) }};

        function calculateRowAndTotal() {
            let grandTotal = 0;
            $('.item-row').each(function() {
                let qty = parseFloat($(this).find('.qty').val()) || 0;
                let price = parseFloat($(this).find('.price').val()) || 0;
                let total = qty * price;
                $(this).find('.total-price').val(total.toFixed(2));
                grandTotal += total;
            });

            $('#grandTotalDisplay').text(grandTotal.toFixed(2));
            $('#grandTotalInput').val(grandTotal.toFixed(2));
        }

        $('#itemsContainer').on('input', '.qty, .price', function() {
            calculateRowAndTotal();
        });

        $('#addItemBtn').click(function() {
            let rowHtml = `
                <tr class="item-row">
                    <td>
                        <input type="text" name="items[${itemIndex}][description]" class="form-control" placeholder="Description" required>
                    </td>
                    <td>
                        <input type="number" name="items[${itemIndex}][quantity]" class="form-control qty" value="1" min="1" required>
                    </td>
                    <td>
                        <input type="number" step="0.01" name="items[${itemIndex}][unit_price]" class="form-control price" value="0.00" min="0" required>
                    </td>
                    <td>
                        <input type="number" step="0.01" name="items[${itemIndex}][total_price]" class="form-control total-price" value="0.00" readonly>
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm crm-action-btn remove-item"><i class="bi bi-trash text-danger"></i></button>
                    </td>
                </tr>
            `;
            $('#itemsContainer').append(rowHtml);
            itemIndex++;
        });

        $('#itemsContainer').on('click', '.remove-item', function() {
            if ($('.item-row').length > 1) {
                $(this).closest('tr').remove();
                calculateRowAndTotal();
            } else {
                window.crmAlert('At least one item is required.');
            }
        });

        $("#quotationForm").submit(function (e) {
            e.preventDefault();
            let btn = $('#saveBtn');
            btn.prop('disabled', true).text('Saving...');

            var formData = new FormData(this);
            let updateUrl = "{{ route('quotations.update', $quotation) }}";
            // window.crmAlert('Updating at: ' + updateUrl); // Uncomment for deep debug

            $.ajax({
                type: "POST",
                url: updateUrl,
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    window.location.href = "{{ route('quotations.index') }}";
                },
                error: function (response) {
                    btn.prop('disabled', false).text('Update Quotation');
                    if(response.responseJSON && response.responseJSON.errors) {
                        window.crmAlert('Validation Error. Please check fields.');
                        console.error(response.responseJSON.errors);
                    } else if(response.responseJSON && response.responseJSON.message) {
                        window.crmAlert('Server Error: ' + response.responseJSON.message);
                    } else {
                        window.crmAlert('Something went wrong!');
                    }
                }
            });
        });
    });
</script>
@endpush
