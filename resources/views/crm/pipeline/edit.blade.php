@extends('layouts.app')

@section('page_title', 'Pipeline - Edit')

@section('content')
    <div class="container-fluid p-0">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden pipeline-form-card">
            <div class="card-body p-0">
                {{-- Header Section --}}
                <div class="p-4 border-bottom bg-light bg-opacity-50">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 w-100">
                        <div class="flex-grow-1 w-100">
                            <h1 class="h4 mb-1 fw-bold">Edit Pipeline</h1>
                            <p class="text-muted small mb-0">Update pipeline details.</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-lg-end justify-content-md-end">
                            @can('pipeline.view')
                                <a href="{{ route('pipeline.show', $pipeline) }}" class="btn btn-outline-dark-blue flex-grow-1 flex-md-grow-0">
                                    <i class="bi bi-eye me-1"></i>View
                                </a>
                            @endcan
                            <a href="{{ route('pipeline.index') }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                                <i class="fa-solid fa-angle-left pe-2"></i>Back
                            </a>
                        </div>
                    </div>
                </div>

                <div class="p-4">
            <form method="POST" action="/api/pipelines/{{ $pipeline->id }}" id="pipelineForm" class="needs-validation js-status-comment-form" novalidate>
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Pipeline Name</label>
                        <input type="text" name="pipeline_name" id="pipeline_name" class="form-control @error('pipeline_name') is-invalid @enderror" placeholder="Pipeline Name" value="{{ old('pipeline_name', $pipeline->pipeline_name) }}">
                        <div class="invalid-feedback" id="pipeline_name-error"></div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Customer</label>
                        <select name="customer_id" id="customer_id" class="form-select @error('customer_id') is-invalid @enderror" data-search-url="{{ route('api.customers.search') }}" data-search-type="customer" data-search-placeholder="-- Search Customer --">
                            <option value="">-- Search Customer --</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}" data-email="{{ $customer->email }}" data-phone="{{ $customer->phone }}" @selected(old('customer_id', $pipeline->customer_id) == $customer->id)>{{ $customer->name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="customer_id-error"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="2" placeholder="Description">{{ old('description', $pipeline->description) }}</textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Stage</label>
                        <div class="d-flex gap-2 pipeline-stage-inline">
                            <select name="stage_id" id="stage_id" class="form-select @error('stage_id') is-invalid @enderror">
                                <option value="">Select Stage</option>
                                @foreach ($stages as $stage)
                                    <option value="{{ $stage->id }}" @selected(old('stage_id', $pipeline->stage_id) == $stage->id)>{{ $stage->name }}</option>
                                @endforeach
                            </select>
                            @can('stages.create')
                            <button type="button" class="btn crm-action-btn addStageBtn stage-plus-btn" title="Add Stage">+</button>
                            @endcan
                        </div>
                        <div class="invalid-feedback" id="stage_id-error"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" id="status" class="form-select @error('status') is-invalid @enderror js-status-comment-trigger">
                            <option value="">Select Status</option>
                            <option value="in_progress" @selected(old('status', $pipeline->status) == 'in_progress')>In Progress</option>
                            <option value="paused" @selected(old('status', $pipeline->status) == 'paused')>Paused</option>
                            <option value="completed" @selected(old('status', $pipeline->status) == 'completed')>Completed</option>
                        </select>
                        <div class="invalid-feedback" id="status-error"></div>
                    </div>

                    <div class="mt-4 pt-4 border-top d-flex flex-sm-row justify-content-end gap-2 form-actions">
                        <a href="{{ route('pipeline.index') }}" class="btn btn-outline-dark-blue">Cancel</a>
                        <button type="submit" class="btn btn-dark-blue" id="submitBtn">
                            <span class="spinner-border spinner-border-sm me-2 d-none" id="btnSpinner" role="status" aria-hidden="true"></span>
                            <span id="btnText">Update</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @include('crm.partials.status-history-table', ['histories' => $pipeline->statusHistories])
</div>
@endsection

<div class="modal fade" id="stageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <div class="modal-header pipeline-stage-modal-header">
                <h5 class="modal-title text-white fw-bold" id="stageModalTitle">Add Stage</h5>
                <button type="button" class="btn-close btn-close-white opacity-100" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="/api/masters/stages" id="stageForm" novalidate>
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Stage Name</label>
                        <input type="text" name="name" id="stageName" class="form-control" required>
                        <div class="invalid-feedback" id="name-error"></div>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-dark-blue" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-dark-blue pipeline-stage-submit-btn" id="stageSubmitBtn">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/pipeline.css' : '/css/pipeline.css') }}">
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/js/pipeline.js' : '/js/pipeline.js') }}"></script>
@endpush
