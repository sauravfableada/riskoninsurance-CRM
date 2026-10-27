@extends('layouts.masters')

@section('page_title', 'Masters - Add Country')

@section('masters_content')
<h1 class="h5 mb-3">Add Country</h1>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('masters.countries.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">ISO Code</label>
                    <input type="text" name="iso_code" class="form-control @error('iso_code') is-invalid @enderror" value="{{ old('iso_code') }}" maxlength="3" required>
                    @error('iso_code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" {{ old('is_active', true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>

                <button type="submit" class="btn btn-dark-blue">Save</button>
                <a href="{{ route('masters.countries.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection

