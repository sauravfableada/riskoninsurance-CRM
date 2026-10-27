@extends('layouts.masters')

@section('page_title', 'Masters - Add Agent')

@section('masters_content')
<h1 class="h5 mb-3">Add Agent</h1>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div id="agentAlert" class="alert d-none" role="alert"></div>

            <form id="agentForm" method="POST" action="/api/masters/agents">
                <div class="mb-3">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Code</label>
                        <input type="text" name="code" class="form-control" value="{{ old('code') }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Type</label>
                        <input type="text" name="type" class="form-control" value="{{ old('type') }}" placeholder="B2B, B2C, etc.">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Country</label>
                        <select name="country_id" id="country_id" class="form-select">
                            <option value="">Select country</option>
                            @foreach($countries as $country)
                                <option value="{{ $country->id }}" @if(old('country_id') == $country->id) selected @endif>{{ $country->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">City</label>
                        <select name="city_id" id="city_id" class="form-select">
                            <option value="">Select city</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email') }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
                    </div>
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" {{ old('is_active', true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>

                <button type="submit" class="btn btn-dark-blue">Save</button>
                <a href="{{ route('masters.agents.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
</div>
@push('scripts')
    <script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/assets/js/agent.js' : '/assets/js/agent.js') }}?v={{ filemtime(public_path('assets/js/agent.js')) }}"></script>
@endpush
@endsection

