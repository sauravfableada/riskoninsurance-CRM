@extends('layouts.app')

@section('page_title', 'Quotations - Create')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-1">New Quotation Builder</h1>
            <p class="text-muted small mb-0">Create a detailed proposal with cost breakdown.</p>
        </div>
        <a href="{{ route('quotations.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left pe-2"></i>Back
        </a>
    </div>

    <form id="quotationForm">
        @csrf
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
                        <textarea name="notes" rows="4" class="form-control" placeholder="Add terms and conditions or special notes for the client..."></textarea>
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
                            <label class="form-label fw-semibold">Lead / Customer</label>
                            <select name="lead_id" class="form-select" required>
                                <option value="">-- Select Lead --</option>
                                @foreach ($leads as $lead)
                                    <option value="{{ $lead->id }}" @selected($selectedLead == $lead->id)>{{ $lead->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tour Package (Base)</label>
                            <select name="tour_package_id" class="form-select">
                                <option value="">-- Custom (No Package) --</option>
                                @foreach ($packages as $pkg)
                                    <option value="{{ $pkg->id }}">{{ $pkg->name }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">Selecting a package will copy its itinerary if one exists.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Valid Until</label>
                            <input type="date" name="valid_until" class="form-control" value="{{ date('Y-m-d', strtotime('+14 days')) }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" class="form-select">
                                <option value="quotation" selected>Quotation (Draft)</option>
                                <option value="estimate">Estimate (Sent)</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>

                        <div class="border-top pt-3 mt-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="fw-bold">Grand Total</span>
                                <h4 class="mb-0 text-primary">
                                    <span class="fs-6 text-muted">INR</span> <span id="grandTotalDisplay">0.00</span>
                                </h4>
                                <input type="hidden" name="total_amount" id="grandTotalInput" value="0.00">
                            </div>
                            <button type="submit" class="btn btn-dark-blue w-100 py-2 fw-semibold" id="saveBtn">
                                Generate Quotation
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
        let itemIndex = 1;

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

            $.ajax({
                type: "POST",
                url: "{{ route('quotations.store') }}",
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    window.location.href = "{{ route('quotations.index') }}";
                },
                error: function (response) {
                    btn.prop('disabled', false).text('Generate Quotation');
                    if(response.responseJSON && response.responseJSON.errors) {
                        window.crmAlert('Validation Error. Please check fields.');
                        console.error(response.responseJSON.errors);
                    } else {
                        window.crmAlert('Something went wrong!');
                    }
                }
            });
        });
    });
</script>
@endpush
