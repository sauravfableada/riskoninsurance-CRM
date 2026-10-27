@extends('layouts.app')

@section('page_title', 'Add Tour Package')

@section('page_actions')
    <a href="{{ route('packages.index') }}" class="btn btn-light btn-sm rounded-pill px-3">
        <i class="fa-solid fa-angle-left pe-2"></i>Back
    </a>
@endsection

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom-0 py-4 px-4">
        <h5 class="fw-bold mb-0">Package Information</h5>
    </div>
    <div class="card-body px-4 pb-4">
        <form action="{{ route('packages.store') }}" method="POST">
            @csrf

            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Package Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="e.g. Exotic Kerala Tour" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Package Code</label>
                    <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}" placeholder="e.g. KER-001">
                    @error('code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                    <select name="is_active" class="form-select @error('is_active') is-invalid @enderror" required>
                        <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('is_active')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Destination <span class="text-danger">*</span></label>
                    <input type="text" name="destination" class="form-control @error('destination') is-invalid @enderror" value="{{ old('destination') }}" placeholder="e.g. Kochi, Munnar, Alleppey" required>
                    @error('destination')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Duration (Nights) <span class="text-danger">*</span></label>
                    <input type="number" name="duration_nights" class="form-control @error('duration_nights') is-invalid @enderror" value="{{ old('duration_nights', 0) }}" min="0" required>
                    @error('duration_nights')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Travel Type</label>
                    <select name="travel_type_id" class="form-select @error('travel_type_id') is-invalid @enderror">
                        <option value="">Select Type</option>
                        @foreach($travelTypes as $type)
                            <option value="{{ $type->id }}" {{ old('travel_type_id') == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                        @endforeach
                    </select>
                    @error('travel_type_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Base Price <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" step="0.01" name="base_price" class="form-control @error('base_price') is-invalid @enderror" value="{{ old('base_price', 0) }}" required>
                        <select name="currency_id" class="form-select flex-grow-0" style="width: 100px;">
                            @foreach($currencies as $currency)
                                <option value="{{ $currency->id }}" {{ old('currency_id') == $currency->id || $currency->is_default ? 'selected' : '' }}>{{ $currency->code }}</option>
                            @endforeach
                        </select>
                    </div>
                    @error('base_price')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Available Seats</label>
                    <input type="number" name="available_seats" class="form-control @error('available_seats') is-invalid @enderror" value="{{ old('available_seats') }}" placeholder="Leave blank for unlimited">
                    @error('available_seats')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Package Highlights</label>
                <textarea name="highlights" class="form-control @error('highlights') is-invalid @enderror" rows="5" placeholder="List the main attractions and inclusions...">{{ old('highlights') }}</textarea>
                @error('highlights')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex justify-content-end gap-2 mt-5 pt-3 border-top">
                <a href="{{ route('packages.index') }}" class="btn btn-light rounded-pill px-4">Cancel</a>
                <button type="submit" class="btn btn-dark-blue rounded-pill px-4">Create Package</button>
            </div>
        </form>
    </div>
</div>
@endsection
