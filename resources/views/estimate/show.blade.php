@extends('layouts.app')

@section('content')
<style>
    .invoice-box { max-width: 800px; margin: 20px auto; padding: 20px; border: 1px solid #eee; background-color: #fff; box-shadow: 0 0 10px rgba(0, 0, 0, 0.15); }
    .estimate-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    .estimate-table thead th { background-color: #f4f4f4; padding: 10px; text-align: left; border-bottom: 2px solid #ddd; }
    .estimate-table tbody td { padding: 10px; border-bottom: 1px solid #ddd; }
    .estimate-table tfoot td { padding: 10px; text-align: right; border-top: 2px solid #ddd; font-weight: bold; }
</style>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card">
                <div class="card-header pb-0 p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">VIEW ESTIMATE</h6>
                        <div>
                            <a href="{{ route('estimates.index') }}" class="btn btn-secondary btn-sm mb-0">
                                <i class="fas fa-chevron-left me-1"></i> BACK
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-6">
                            <h5 class="mb-0">Estimate #{{ $estimate->estimate_no }}</h5>
                            <p class="text-sm mb-0">Date: {{ $estimate->estimate_date }}</p>
                            <p class="text-sm mb-0">Status: <span class="badge badge-sm {{ $estimate->status == 'approved' ? 'bg-gradient-success' : 'bg-gradient-secondary' }}">{{ strtoupper($estimate->status) }}</span></p>
                        </div>
                        <div class="col-6 text-end">
                            @php
                                $settings = \App\Models\Setting::first();
                                $companyLogo = $settings->company_logo ?? 'default_logo.png';
                            @endphp
                            <img src="{{ asset('storage/' . $companyLogo) }}" class="mb-2" style="max-height: 50px;" alt="company_logo">
                            <p class="text-sm mb-0"><strong>{{ $settings->company_name ?? 'Insurance CRM' }}</strong></p>
                        </div>
                    </div>

                    <hr class="horizontal dark my-4">

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6>From:</h6>
                            <p class="text-sm mb-0"><strong>{{ $settings->company_name ?? 'Insurance CRM' }}</strong></p>
                            <p class="text-sm mb-0">{{ $settings->address ?? '--' }}</p>
                        </div>
                        <div class="col-md-6 text-end">
                            <h6>To:</h6>
                            <p class="text-sm mb-0"><strong>{{ $estimate->customer->name ?? '--' }}</strong></p>
                            <p class="text-sm mb-0">{{ $estimate->customer->email ?? '--' }}</p>
                            <p class="text-sm mb-0">{{ $estimate->customer->address ?? '--' }}</p>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table estimate-table">
                            <thead>
                                <tr>
                                    <th>Product Name</th>
                                    <th class="text-center">Quantity</th>
                                    <th class="text-end">Price</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $subtotal = 0; @endphp
                                @foreach($products as $item)
                                    @php
                                        $product = $allProducts->get($item['product_id']);
                                        $rowTotal = $item['quantity'] * $item['amount'];
                                        $subtotal += $rowTotal;
                                    @endphp
                                    <tr>
                                        <td>{{ $product->name ?? 'Unknown Product' }}</td>
                                        <td class="text-center">{{ $item['quantity'] }}</td>
                                        <td class="text-end">{{ number_format($item['amount'], 2) }}</td>
                                        <td class="text-end">{{ number_format($rowTotal, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                @php
                                    $taxRate = 0.10; // 10% tax as per CI code
                                    $taxAmount = $subtotal * $taxRate;
                                    $grandTotal = $subtotal + $taxAmount;
                                @endphp
                                <tr>
                                    <td colspan="3" class="text-end">Subtotal</td>
                                    <td class="text-end">{{ number_format($subtotal, 2) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end">Tax (10%)</td>
                                    <td class="text-end">{{ number_format($taxAmount, 2) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Total</strong></td>
                                    <td class="text-end"><strong>{{ number_format($grandTotal, 2) }}</strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    @if($estimate->comment)
                        <div class="mt-4">
                            <h6>Comment:</h6>
                            <p class="text-sm">{{ $estimate->comment }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
