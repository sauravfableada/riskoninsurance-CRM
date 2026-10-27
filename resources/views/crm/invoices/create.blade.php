@extends('layouts.app')

@section('page_title', 'Create Invoice')

@section('content')
    <div class="container-fluid p-0">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden invoice-form-card">
            <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h1 class="h4 mb-1 fw-semibold">Create Invoice</h1>
                        <p class="text-muted small mb-0">Create new invoice with products and amounts.</p>
                    </div>
                    <a href="{{ route('invoices.index') }}" class="btn btn-dark-blue invoice-back-btn">
                        <i class="fa-solid fa-angle-left pe-1"></i>
                        <span>Back</span>
                    </a>
                </div>
            </div>
            <div class="card-body p-3 p-md-4">
            <form action="/api/invoices" method="POST" class="ajax-invoice-form needs-validation" novalidate>
                @csrf
                <div class="row gy-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Customer <span class="text-danger">*</span></label>
                        <select name="customer_id" id="customer_id" class="form-select" data-search-url="{{ route('api.customers.search') }}" data-search-type="customer" data-search-placeholder="-- Search Customer --" required>
                            <option value="">-- Search Customer --</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}"
                                    data-email="{{ $customer->email }}"
                                    data-phone="{{ $customer->phone }}"
                                    @selected((string) old('customer_id', $booking->customer_id ?? request('customer_id')) === (string) $customer->id)>
                                    {{ $customer->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Invoice Date <span class="text-danger">*</span></label>
                        <input type="date" name="invoice_date" class="form-control" value="{{ old('invoice_date', now()->toDateString()) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Due Date <span class="text-danger">*</span></label>
                        <input type="date" name="due_date" class="form-control" value="{{ old('due_date', $booking->travel_start_date ?? now()->toDateString()) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Currency <span class="text-danger">*</span></label>
    
                        <select name="currency_id" class="form-select" required>
                            <option value="">Select currency</option>
                            @foreach($currencies as $currency)
                                <option value="{{ $currency->id }}" @selected((string) old('currency_id', $booking->currency_id ?? null) === (string) $currency->id)>{{ $currency->symbol }} - {{ $currency->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Comment</label>
                        <textarea name="comment" class="form-control" rows="2">{{ old('comment', isset($booking) ? 'Invoice for booking ' . $booking->booking_no : '') }}</textarea>
                    </div>
                </div>

                <div class="mt-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="mb-0">Line Items</h5>
                        <button type="button" id="addItemBtn" class="btn btn-sm crm-action-btn">Add Item</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 35%;">Product Name</th>
                                    <th style="width: 20%;">Amount</th>
                                    <th style="width: 15%;">Quantity</th>
                                    <th style="width: 20%;">Total</th>
                                    <th style="width: 10%;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="invoiceItemsBody">
                                <tr>
                                    <td>
                                        <select name="items[0][product_id]" class="form-select product-select" required>
                                            <option value="">Select product</option>
                                            @foreach($products as $product)
                                                <option value="{{ $product->id }}" data-name="{{ $product->name }}" data-price="{{ $product->price }}">{{ $product->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="number" step="0.01" min="0" name="items[0][amount]" class="form-control item-amount" required></td>
                                    <td><input type="number" min="1" name="items[0][quantity]" class="form-control item-quantity" value="1" required></td>
                                    <td><input type="text" class="form-control item-total" readonly value="0.00"></td>
                                    <td><button type="button" class="btn btn-danger btn-sm remove-item">Delete</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-top d-flex flex-sm-row justify-content-end gap-2 form-actions">
                    <a href="{{ route('invoices.index') }}" class="btn btn-outline-dark-blue">Cancel</a>
                    <button type="submit" class="btn btn-dark-blue">Submit</button>
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
    window.invoiceProducts = @json($products->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'price' => $p->price]));
</script>
<script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/js/invoice.js' : '/js/invoice.js') }}"></script>
@endpush
