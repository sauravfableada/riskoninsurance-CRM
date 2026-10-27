@extends('layouts.masters')

@section('page_title', 'Masters - Edit Agent')

@section('masters_content')
<h1 class="h5 mb-3">Edit Agent</h1>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('masters.agents.update', $agent) }}">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $agent->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Code</label>
                        <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $agent->code) }}">
                        @error('code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Type</label>
                        <input type="text" name="type" class="form-control @error('type') is-invalid @enderror" value="{{ old('type', $agent->type) }}" placeholder="B2B, B2C, etc.">
                        @error('type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Country</label>
                        <select name="country_id" id="country_id" class="form-select @error('country_id') is-invalid @enderror">
                            <option value="">Select country</option>
                            @foreach($countries as $country)
                                <option value="{{ $country->id }}" @if(old('country_id', $agent->country_id) == $country->id) selected @endif>{{ $country->name }}</option>
                            @endforeach
                        </select>
                        @error('country_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">City</label>
                        <select name="city_id" id="city_id" class="form-select @error('city_id') is-invalid @enderror">
                            <option value="">Select city</option>
                            @foreach($cities as $city)
                                <option value="{{ $city->id }}" @if(old('city_id', $agent->city_id) == $city->id) selected @endif>{{ $city->name }}</option>
                            @endforeach
                        </select>
                        @error('city_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $agent->email) }}">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $agent->phone) }}">
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" {{ old('is_active', $agent->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>

                <button type="submit" class="btn btn-dark-blue">Update</button>
                <a href="{{ route('masters.agents.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
</div>
@push('scripts')
<script>
    document.getElementById('country_id').addEventListener('change', function() {
        const countryId = this.value;
        const citySelect = document.getElementById('city_id');
        
        citySelect.innerHTML = '<option value="">Loading...</option>';
        
        if (!countryId) {
            citySelect.innerHTML = '<option value="">Select city</option>';
            return;
        }
        
        fetch(`/masters/cities-by-country/${countryId}`)
            .then(response => response.json())
            .then(data => {
                citySelect.innerHTML = '<option value="">Select city</option>';
                data.forEach(city => {
                    citySelect.innerHTML += `<option value="${city.id}">${city.name}</option>`;
                });
            })
            .catch(error => {
                console.error('Error fetching cities:', error);
                citySelect.innerHTML = '<option value="">Error loading cities</option>';
            });
    });
</script>
@endpush
@endsection

