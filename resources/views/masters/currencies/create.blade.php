@extends('layouts.masters')

@section('page_title', 'Masters - Add Currency')

@section('masters_content')
<h1 class="h5 mb-3">Add Currency</h1>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('masters.currencies.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Code</label>
                    <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}" required>
                    @error('code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Symbol</label>
                    <input type="text" name="symbol" class="form-control @error('symbol') is-invalid @enderror" value="{{ old('symbol') }}">
                    @error('symbol')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Exchange Rate</label>
                    <input type="number" step="0.0001" name="exchange_rate" class="form-control @error('exchange_rate') is-invalid @enderror" value="{{ old('exchange_rate', 1) }}" required>
                    @error('exchange_rate')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" id="is_default" name="is_default" {{ old('is_default') ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_default">Default currency</label>
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" {{ old('is_active', true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>

                <button type="submit" class="btn btn-dark-blue">Save</button>
                <a href="{{ route('masters.currencies.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection

