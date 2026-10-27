@extends('layouts.masters')

@section('page_title', 'Masters - Add Hotel')

@section('masters_content')
<h1 class="h5 mb-3">Add Hotel</h1>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('masters.hotels.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Country</label>
                        <select name="country_id" id="country_id" class="form-select @error('country_id') is-invalid @enderror">
                            <option value="">Select country</option>
                            @foreach($countries as $country)
                                <option value="{{ $country->id }}" @if(old('country_id') == $country->id) selected @endif>{{ $country->name }}</option>
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
                        </select>
                        @error('city_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Star Rating</label>
                        <input type="number" name="star_rating" class="form-control @error('star_rating') is-invalid @enderror" value="{{ old('star_rating') }}" min="1" max="5">
                        @error('star_rating')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}">
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" {{ old('is_active', true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>

                <button type="submit" class="btn btn-dark-blue">Save</button>
                <a href="{{ route('masters.hotels.index') }}" class="btn btn-link">Cancel</a>
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

