@extends('layouts.app')

@section('page_title', 'Masters - Edit Status')

@section('content')
<div class="container-fluid">
    <h1 class="h5 mb-3">Edit Status</h1>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('masters.statuses.update', $status) }}">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $status->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Type</label>
                    <input type="text" name="type" class="form-control @error('type') is-invalid @enderror" value="{{ old('type', $status->type) }}" placeholder="e.g. lead, quotation">
                    @error('type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Color (CSS value)</label>
                    <input type="text" name="color" class="form-control @error('color') is-invalid @enderror" value="{{ old('color', $status->color) }}" placeholder="#22c55e or rgb(...)">
                    @error('color')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" {{ old('is_active', $status->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>

                <button type="submit" class="btn btn-dark-blue">Update</button>
                <a href="{{ route('masters.statuses.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection

