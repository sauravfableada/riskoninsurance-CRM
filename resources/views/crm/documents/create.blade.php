@extends('layouts.app')

@section('page_title', 'Upload Document')

@section('page_actions')
    <a href="{{ route('documents.index') }}" class="btn btn-light btn-sm rounded-pill px-3">
        <i class="bi bi-arrow-left me-1"></i> Back to Documents
    </a>
@endsection

@section('content')
<div class="card border-0 shadow-sm mb-4 overflow-hidden">
    <div class="card-header bg-white border-bottom-0 py-4 px-4">
        <h5 class="fw-bold mb-0">Upload New File</h5>
    </div>
    <div class="card-body px-4 pb-4">
        <form action="/api/documents" method="POST" enctype="multipart/form-data" class="ajax-document-form" id="documentForm">
            @csrf

            <div class="row g-4">
                <div class="col-md-12">
                    <label class="form-label fw-semibold">Document Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" required placeholder="E.g., Corporate Brochure Q3">
                    <div class="invalid-feedback d-block" id="title-error">@error('title'){{ $message }}@enderror</div>
                </div>
                
                <div class="col-md-12">
                    <label class="form-label fw-semibold">File Upload <span class="text-danger">*</span></label>
                    <input type="file" name="file" id="file" class="form-control @error('file') is-invalid @enderror" required>
                    <div class="form-text mt-2 text-muted">
                        <i class="bi bi-info-circle me-1"></i> Max file size: 10MB. Allowed types: PDF, DOC, DOCX, JPG, PNG.
                    </div>
                    <div class="invalid-feedback d-block" id="file-error">@error('file'){{ $message }}@enderror</div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                <a href="{{ route('documents.index') }}" class="btn btn-light">Cancel</a>
                <button type="submit" class="btn btn-dark-blue px-4" id="submitBtn">Upload Document</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/js/documents.js' : '/js/documents.js') }}"></script>
@endpush
