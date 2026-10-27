@extends('layouts.app')

@section('page_title', 'Edit Default Email Template')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3 px-4">
                <h5 class="fw-bold mb-0">Edit Email Template</h5>
            </div>
            <form method="POST" action="{{ route('masters.default_email_templates.update', $template) }}">
                @csrf
                @method('PUT')
                <div class="card-body px-4 pb-4">
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $template->name) }}" class="form-control @error('name') is-invalid @enderror">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Content <span class="text-danger">*</span></label>
                        <textarea name="content" rows="10" class="form-control @error('content') is-invalid @enderror">{{ old('content', $template->content) }}</textarea>
                        @error('content')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="card-footer bg-body-tertiary border-top px-4 py-3">
                    <a href="{{ route('masters.default_email_templates.index') }}" class="btn btn-light">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-dark-blue px-4 ms-2">
                        <i class="bi bi-save me-1"></i> Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

