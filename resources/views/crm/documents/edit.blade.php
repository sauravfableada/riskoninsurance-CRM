@extends('layouts.app')

@section('page_title', 'Edit Document')

@section('page_actions')
    <div class="d-flex gap-2">
        <a href="{{ route('documents.index') }}" class="btn btn-light btn-sm rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
@endsection

@section('content')
<div class="card border-0 shadow-sm mb-4 overflow-hidden">
    <div class="card-header bg-white border-bottom-0 py-4 px-4">
        <h5 class="fw-bold mb-0">Update Document: {{ $document->title }}</h5>
    </div>
    <div class="card-body px-4 pb-4">
        <form action="/api/documents/{{ $document->id }}" method="POST" enctype="multipart/form-data" class="ajax-document-form" id="documentForm">
            @csrf
            @method('PUT')

            <div class="row g-4">
                <div class="col-md-12">
                    <label class="form-label fw-semibold">Document Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $document->title) }}" required>
                    <div class="invalid-feedback d-block" id="title-error">@error('title'){{ $message }}@enderror</div>
                </div>
                
                <div class="col-md-12">
                    <label class="form-label fw-semibold">Replace File (Optional)</label>
                    <input type="file" name="file" id="file" class="form-control @error('file') is-invalid @enderror">
                    <div class="form-text mt-2 text-muted">
                        <i class="bi bi-info-circle me-1"></i> Leave this blank if you don't want to replace the current file (Current: <strong>{{ strtoupper($document->file_type) }}</strong>).
                    </div>
                    <div class="invalid-feedback d-block" id="file-error">@error('file'){{ $message }}@enderror</div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                <a href="{{ route('documents.index') }}" class="btn btn-light">Cancel</a>
                <button type="submit" class="btn btn-dark-blue px-4" id="submitBtn">Update</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/js/documents.js' : '/js/documents.js') }}"></script>
@endpush
