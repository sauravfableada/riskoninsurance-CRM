@extends('layouts.masters')

@section('page_title', 'Masters - Edit City')

@section('masters_content')
<h1 class="h5 mb-3">Edit City</h1>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('masters.cities.update', $city) }}">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $city->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Country</label>
                    <select name="country_id" class="form-select @error('country_id') is-invalid @enderror">
                        <option value="">Select country (optional)</option>
                        @foreach($countries as $country)
                            <option value="{{ $country->id }}" @if(old('country_id', $city->country_id) == $country->id) selected @endif>{{ $country->name }}</option>
                        @endforeach
                    </select>
                    @error('country_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" {{ old('is_active', $city->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>

                <button type="submit" class="btn btn-dark-blue">Update</button>
                <a href="{{ route('masters.cities.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection

