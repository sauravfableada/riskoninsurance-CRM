@extends('layouts.app')

@section('page_title', 'Deals - Edit')

@section('content')
    <div class="container-fluid p-0">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden deal-form-card">
            <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h1 class="h4 mb-1 fw-semibold">Edit Deal</h1>
                        <p class="text-muted small mb-0">Update deal details for the customer.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-lg-end justify-content-md-end">
                        @can('deals.view')
                            <a href="{{ route('deals.show', $deal) }}" class="btn btn-outline-dark-blue flex-grow-1 flex-md-grow-0">
                                <i class="bi bi-eye me-1"></i>View
                            </a>
                        @endcan
                        <a href="{{ route('deals.index') }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                            <i class="fa-solid fa-angle-left pe-1"></i>
                            <span>Back</span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body p-3 p-md-4">
                @php
                    if (!function_exists('field_label') && file_exists(app_path('Helpers/FieldCustomizationHelper.php'))) {
                        require_once app_path('Helpers/FieldCustomizationHelper.php');
                    }
                    $customService = app(\App\Services\FieldCustomizationService::class);
                    $fieldLabel = function(string $field, string $default) use ($customService) {
                        return $customService->getFieldLabel('deals', $field, $default);
                    };
                    $isRequired = function(string $field, bool $default = false) use ($customService) {
                        return $customService->isFieldRequired('deals', $field);
                    };
                    $isVisible = function(string $field, bool $default = true) use ($customService) {
                        return $customService->isFieldVisible('deals', $field);
                    };
                @endphp
                <form method="POST" action="/api/deals/{{ $deal->id }}" id="dealForm" class="needs-validation ajax-deal-form js-status-comment-form" novalidate>
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        @if($isVisible('customer_id', true))
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ $fieldLabel('customer_id', 'Customer') }} @if($isRequired('customer_id', true))<span class="text-danger">*</span>@endif</label>
                            <select name="customer_id" id="customer_id" class="form-select"
                                data-search-url="{{ route('api.customers.search') }}" data-search-type="customer"
                                data-search-placeholder="-- {{ $fieldLabel('customer_id', 'Search Customer') }} --" {{ $isRequired('customer_id', true) ? 'required' : '' }}>
                                <option value="">-- {{ $fieldLabel('customer_id', 'Search Customer') }} --</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}" data-email="{{ $customer->email }}" data-phone="{{ $customer->phone }}" @selected(old('customer_id', $deal->customer_id) == $customer->id)>{{ $customer->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="customer_id-error"></div>
                        </div>
                        @endif

                        @if($isVisible('assigned_user_id', true))
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ $fieldLabel('assigned_user_id', 'Assigned To') }} @if($isRequired('assigned_user_id', false))<span class="text-danger">*</span>@endif</label>
                            @if(auth()->user()->isAdmin())
                                <select name="assigned_user_id" id="assigned_user_id" class="form-select"
                                    data-search-url="{{ route('api.users.search') }}" data-search-type="user"
                                    data-search-placeholder="Select Staff" {{ $isRequired('assigned_user_id', false) ? 'required' : '' }}>
                                    <option value="">Select Staff</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}" data-email="{{ $user->email }}"
                                            @selected(old('assigned_user_id', $deal->assigned_user_id) == $user->id)>{{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <input type="hidden" name="assigned_user_id" value="{{ old('assigned_user_id', auth()->id()) }}">
                                <input type="text" class="form-control" value="{{ auth()->user()->name }}" readonly>
                            @endif
                            <div class="invalid-feedback" id="assigned_user_id-error"></div>
                        </div>
                        @endif

                        @if($isVisible('title', true))
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ $fieldLabel('title', 'Deal Name') }} @if($isRequired('title', true))<span class="text-danger">*</span>@endif</label>
                            <input name="title" id="title" value="{{ old('title', $deal->title) }}" placeholder="{{ $fieldLabel('title', 'Deal Name') }}" class="form-control" {{ $isRequired('title', true) ? 'required' : '' }}>
                            <div class="invalid-feedback" id="title-error"></div>
                        </div>
                        @endif

                        @if($isVisible('amount', true))
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ $fieldLabel('amount', 'Deal Value') }} @if($isRequired('amount', false))<span class="text-danger">*</span>@endif</label>
                            <input type="number" step="0.01" min="0" name="amount" id="amount" value="{{ old('amount', $deal->amount) }}" placeholder="{{ $fieldLabel('amount', '0.00') }}" class="form-control" {{ $isRequired('amount', false) ? 'required' : '' }}>
                            <div class="invalid-feedback" id="amount-error"></div>
                        </div>
                        @endif

                        @if($isVisible('probability', true))
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ $fieldLabel('probability', 'Probability (%)') }} @if($isRequired('probability', false))<span class="text-danger">*</span>@endif</label>
                            <input type="number" step="0.01" min="0" max="100" name="probability" id="probability" value="{{ old('probability', $deal->probability) }}" placeholder="{{ $fieldLabel('probability', '0 - 100') }}" class="form-control" {{ $isRequired('probability', false) ? 'required' : '' }}>
                            <div class="invalid-feedback" id="probability-error"></div>
                        </div>
                        @endif

                        @if($isVisible('stage_id', true))
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ $fieldLabel('stage_id', 'Stage') }} @if($isRequired('stage_id', true))<span class="text-danger">*</span>@endif</label>
                            <select name="stage_id" id="stage_id" class="form-select" {{ $isRequired('stage_id', true) ? 'required' : '' }}>
                                <option value="">-- {{ $fieldLabel('stage_id', 'Select Stage') }} --</option>
                                @foreach ($stages as $stage)
                                    <option value="{{ $stage->id }}" @selected(old('stage_id', $deal->stage_id) == $stage->id)>
                                        {{ $stage->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="stage_id-error"></div>
                        </div>
                        @endif

                        @if($isVisible('status_id', true))
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ $fieldLabel('status_id', 'Deal Status') }} @if($isRequired('status_id', true))<span class="text-danger">*</span>@endif</label>
                            <select name="status_id" id="status_id" class="form-select js-status-comment-trigger" {{ $isRequired('status_id', true) ? 'required' : '' }}>
                                @foreach ($statuses as $status)
                                    <option value="{{ $status->id }}" @selected(old('status_id', $deal->status_id) == $status->id)>{{ $status->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="status_id-error"></div>
                        </div>
                        @endif
                    </div>

                    <div class="mt-4 pt-4 border-top d-flex flex-sm-row justify-content-end gap-2 form-actions">
                        <a href="{{ route('deals.index') }}" class="btn btn-outline-dark-blue">Cancel</a>
                        <button type="submit" class="btn btn-dark-blue" id="submitBtn">
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true" id="btnSpinner"></span>
                            <span id="btnText">Update</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        @include('crm.partials.status-history-table', ['histories' => $deal->statusHistories])
    </div>

    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1050" id="toastContainer"></div>
@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <!-- <script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/js/deal.js' : '/js/deal.js') }}"></script> -->
    <script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/js/deal.js' : '/js/deal.js') }}"></script>
@endpush
