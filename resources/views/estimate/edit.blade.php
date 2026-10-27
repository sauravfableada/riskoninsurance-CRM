@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card">
                <div class="card-header pb-0 pt-2 p-3">
                    <div class="row">
                        <div class="col-lg-6">
                            <h6 class="mt-2">UPDATE ESTIMATE DETAILS</h6>
                        </div>
                        <div class="col-lg-6 text-end">
                            <a href="{{ route('estimates.index') }}" class="btn btn-dark-blue btn-sm">BACK</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form id="edit_estimate_form" action="{{ route('estimates.update', $estimate->estimate_id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fa fa-user me-1"></i> Select Customer</label>
                                <select class="form-control" name="customer_id" id="customer_id" required>
                                    <option value="">Select Customer</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" {{ $customer->id == $estimate->customer_id ? 'selected' : '' }}>{{ $customer->name }}</option>
                                    @endforeach
                                </select>
                                @error('customer_id') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fa fa-calendar me-1"></i> Estimate Date</label>
                                <input class="form-control" type="date" name="estimate_date" value="{{ $estimate->estimate_date }}" required>
                                @error('estimate_date') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fa fa-money-bill me-1"></i> Currency</label>
                                <input class="form-control" type="text" name="currency" value="{{ $estimate->currency }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fa fa-file-alt me-1"></i> Estimate No</label>
                                <input class="form-control" type="text" value="{{ $estimate->estimate_no }}" disabled>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label"><i class="fa fa-comment me-1"></i> Comment</label>
                                <textarea class="form-control" name="comment" rows="2">{{ $estimate->comment }}</textarea>
                            </div>
                        </div>

                        <hr class="horizontal dark my-4">

                        <div id="product-container">
                            @foreach($selectedProducts as $index => $selected)
                                <div class="row product-row mb-3 align-items-end">
                                    <div class="col-md-4">
                                        <label class="form-label">Product Name</label>
                                        <select class="form-control product-select" name="products[{{ $index }}][product_id]" required>
                                            <option value="">Select Product</option>
                                            @foreach($products as $product)
                                                <option value="{{ $product->id }}" data-price="{{ $product->price }}" {{ $product->id == $selected['product_id'] ? 'selected' : '' }}>{{ $product->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Amount</label>
                                        <input type="number" class="form-control amount-field" name="products[{{ $index }}][amount]" value="{{ $selected['amount'] }}" step="0.01" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Quantity</label>
                                        <input type="number" class="form-control quantity-field" name="products[{{ $index }}][quantity]" value="{{ $selected['quantity'] }}" min="1" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Total Price</label>
                                        <input type="text" class="form-control total-price-field" value="{{ number_format($selected['quantity'] * $selected['amount'], 2) }}" readonly>
                                    </div>
                                    <div class="col-md-1">
                                        @if($index == 0)
                                            <button type="button" class="btn btn-dark-blue add-more mb-0"><i class="fa fa-plus"></i></button>
                                        @else
                                            <button type="button" class="btn btn-danger remove-row mb-0"><i class="fa fa-trash"></i></button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="row mt-4">
                            <div class="col-12 text-end">
                                <a href="{{ route('estimates.index') }}" class="btn btn-secondary mb-0">Cancel</a>
                                <button type="submit" class="btn btn-dark-blue mb-0">Update Estimate</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let productIndex = {{ count($selectedProducts) }};

    $(document).on('change', '.product-select', function() {
        let price = $(this).find(':selected').data('price') || 0;
        let row = $(this).closest('.product-row');
        row.find('.amount-field').val(price);
        calculateRowTotal(row);
    });

    $(document).on('input', '.amount-field, .quantity-field', function() {
        calculateRowTotal($(this).closest('.product-row'));
    });

    function calculateRowTotal(row) {
        let amount = parseFloat(row.find('.amount-field').val()) || 0;
        let quantity = parseFloat(row.find('.quantity-field').val()) || 0;
        row.find('.total-price-field').val((amount * quantity).toFixed(2));
    }

    $('.add-more').click(function() {
        let html = `
            <div class="row product-row mb-3 align-items-end">
                <div class="col-md-4">
                    <select class="form-control product-select" name="products[${productIndex}][product_id]" required>
                        <option value="">Select Product</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" data-price="{{ $product->price }}">{{ $product->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="number" class="form-control amount-field" name="products[${productIndex}][amount]" step="0.01" required>
                </div>
                <div class="col-md-2">
                    <input type="number" class="form-control quantity-field" name="products[${productIndex}][quantity]" value="1" min="1" required>
                </div>
                <div class="col-md-3">
                    <input type="text" class="form-control total-price-field" readonly>
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-danger remove-row mb-0"><i class="fa fa-trash"></i></button>
                </div>
            </div>
        `;
        $('#product-container').append(html);
        productIndex++;
    });

    $(document).on('click', '.remove-row', function() {
        $(this).closest('.product-row').remove();
    });
</script>
@endsection
