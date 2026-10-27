@extends('layouts.app')

@section('page_title', 'Configuration - Edit Field')

@section('content')
<div class="container-fluid">
    {{-- Consistent CRM Header Section --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-1">Update Field: <span class="text-primary">{{ $customField->label }}</span></h1>
            <p class="text-muted small mb-0">Modify the settings for this custom field in the <strong>{{ $customField->module }}s</strong> module.</p>
        </div>
        <a href="{{ route('settings.custom-fields.index', ['module' => $customField->module]) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left pe-2"></i>Back
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <form action="{{ route('settings.custom-fields.update', $customField) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">Field Display Name (What users see)</label>
                                <input type="text" name="label" value="{{ old('label', $customField->label) }}" class="form-control form-control-lg @error('label') is-invalid @enderror" required>
                                @error('label') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">Internal ID (Fixed)</label>
                                <input type="text" class="form-control form-control-lg bg-light" value="{{ $customField->name }}" readonly disabled>
                                <div class="form-text extra-small text-info">Internal IDs are locked after creation to protect data.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">Field Type (Fixed)</label>
                                <div class="p-2 border rounded bg-light d-flex align-items-center">
                                    @php
                                        $icon = match($customField->type) {
                                            'text' => 'bi-fonts',
                                            'number' => 'bi-hash',
                                            'date' => 'bi-calendar-date',
                                            'select' => 'bi-caret-down-square',
                                            'textarea' => 'bi-text-paragraph',
                                            default => 'bi-gear'
                                        };
                                    @endphp
                                    <i class="bi {{ $icon }} me-2 text-primary"></i>
                                    <span class="fw-bold small">{{ ucfirst($customField->type) }} Field</span>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">Display Position</label>
                                <input type="number" name="sort_order" value="{{ old('sort_order', $customField->sort_order) }}" class="form-control form-control-lg">
                                <div class="form-text extra-small">Lower numbers appear first.</div>
                            </div>

                            @if($customField->type === 'select')
                            <div class="col-12">
                                <div class="p-3 bg-light rounded border-start border-primary border-4">
                                    <label class="form-label fw-bold small text-dark">Dropdown Options</label>
                                    <textarea name="options" class="form-control" rows="3">{{ old('options', is_array($customField->options) ? implode(', ', $customField->options) : '') }}</textarea>
                                    <div class="form-text extra-small mt-2">Enter options separated by <strong>commas</strong>.</div>
                                </div>
                            </div>
                            @endif

                            <div class="col-12 mt-4">
                                <div class="p-4 bg-light rounded-4 border-dashed">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" name="is_required" value="1" id="isRequired" {{ $customField->is_required ? 'checked' : '' }}>
                                                <label class="form-check-label fw-bold text-dark" for="isRequired">Mark as Mandatory</label>
                                                <p class="extra-small text-muted mb-0">Requires users to fill this field.</p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive" {{ $customField->is_active ? 'checked' : '' }}>
                                                <label class="form-check-label fw-bold text-dark" for="isActive">Is this field active?</label>
                                                <p class="extra-small text-muted mb-0">Uncheck to hide it from all forms.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 pt-3 border-top d-flex gap-3">
                            <button type="submit" class="btn btn-dark-blue px-5 py-2 fw-bold shadow-sm">
                                <i class="bi bi-check2-circle me-2"></i>Update Configuration
                            </button>
                            <a href="{{ route('settings.custom-fields.index', ['module' => $customField->module]) }}" class="btn btn-outline-secondary px-4 py-2">
                                Discard Changes
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .form-control, .form-select { border-radius: 0.5rem; }
    .border-dashed { border-style: dashed !important; border-width: 2px !important; border-color: #cbd5e1 !important; }
    .extra-small { font-size: 0.75rem; }
</style>
@endsection
