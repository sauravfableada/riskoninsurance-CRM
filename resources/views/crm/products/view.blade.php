@extends('layouts.app')

@section('page_title', 'Products - View')

@push('styles')
    <style>
        .product-detail-image-card {
            border: 1px solid #e5e7eb;
            border-radius: 1rem;
            background: #f8fafc;
            overflow: hidden;
            min-height: 220px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .product-detail-image-card img {
            width: 100%;
            height: 100%;
            max-height: 280px;
            object-fit: cover;
        }

        .product-detail-image-empty {
            color: #64748b;
            text-align: center;
            padding: 1.5rem;
        }

        .product-detail-side .detail-view-grid {
            border-top: 0;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid p-0">
        <div class="row g-4">
            <div class="col-lg-12">
                <div class="card shadow-sm border-0 rounded-4 overflow-hidden detail-view-card">
                    <div class="card-body p-0">
                        {{-- Header Section --}}
                        <div class="p-4 border-bottom bg-light bg-opacity-50">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 w-100">
                                <div class="flex-grow-1 w-100">
                                    <h1 class="h4 mb-1 fw-bold">Product Details</h1>
                                    <p class="text-muted small mb-0">Complete information about this product</p>
                                </div>
                                <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-lg-end justify-content-md-end">
                                    @can('products.edit')
                                        <a href="{{ route('products.edit', $product) }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                                            <i class="bi bi-pencil me-1"></i>Edit
                                        </a>
                                    @endcan
                                    <a href="{{ route('products.index') }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                                        <i class="fa-solid fa-angle-left pe-2"></i>Back
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="p-4">

                        <div class="detail-view-block px-md-4">
                            <div class="row g-4 align-items-start">
                                <div class="col-lg-3 col-md-4">
                                    <div class="product-detail-image-card">
                                        @if($product->image_path)
                                            <img src="{{ route('products.image', $product) }}?v={{ optional($product->updated_at)?->timestamp ?? time() }}" alt="{{ $product->name }}">
                                        @else
                                            <div class="product-detail-image-empty">
                                                <i class="bi bi-image fs-1 d-block mb-2"></i>
                                                <span>No image uploaded</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="col-lg-9 col-md-8 product-detail-side">
                                    <div class="row g-0 detail-view-grid">
                                        <div class="col-md-6 detail-view-row">
                                            <span class="detail-view-label">Product Name:</span>
                                            <span class="detail-view-value">{{ $product->name ?: '--' }}</span>
                                        </div>

                                        <div class="col-md-6 detail-view-row">
                                            <span class="detail-view-label">Created At:</span>
                                            <span class="detail-view-value">{{ optional($product->created_at)?->format('d M, Y h:i A') ?? '--' }}</span>
                                        </div>

                                        <div class="col-md-6 detail-view-row">
                                            <span class="detail-view-label">Created By:</span>
                                            <span class="detail-view-value">{{ optional($product->creator)->name ?? '--' }}</span>
                                        </div>

                                        <div class="col-md-6 detail-view-row">
                                            <span class="detail-view-label">Category:</span>
                                            <span class="detail-view-value">{{ optional($product->category)->name ?? '--' }}</span>
                                        </div>

                                        <div class="col-md-6 detail-view-row">
                                            <span class="detail-view-label">Product Price:</span>
                                            <span class="detail-view-value">{{ number_format((float) $product->price, 2) }}</span>
                                        </div>

                                        <div class="col-md-6 detail-view-row">
                                            <span class="detail-view-label">Description:</span>
                                            <span class="detail-view-value">{{ $product->description ?: '--' }}</span>
                                        </div>
                                    </div>

                                    @php
                                        $customFields = $product->customFieldValues ?? collect();
                                    @endphp
                                    @if($customFields->count())
                                        <div class="mt-4 pt-3 border-top">
                                            <div class="row g-0 detail-view-grid">
                                                @foreach($customFields as $fieldValue)
                                                    <div class="col-md-6 detail-view-row">
                                                        <span class="detail-view-label">{{ $fieldValue->customField->label ?? 'Custom Field' }}:</span>
                                                        <span class="detail-view-value">{{ $fieldValue->value ?: '--' }}</span>
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
            </div>
        </div>
    </div>
@endsection
