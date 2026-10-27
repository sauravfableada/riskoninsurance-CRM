@extends('layouts.masters')

@section('page_title', 'Masters - Edit Room Category')

@section('masters_content')
<h1 class="h5 mb-3">Edit Room Category</h1>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('masters.room_categories.update', $roomCategory) }}">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $roomCategory->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <input type="text" name="description" class="form-control @error('description') is-invalid @enderror" value="{{ old('description', $roomCategory->description) }}">
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" {{ old('is_active', $roomCategory->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>

                <button type="submit" class="btn btn-dark-blue">Update</button>
                <a href="{{ route('masters.room_categories.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection

