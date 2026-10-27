@extends('layouts.app')

@section('page_title', 'Default Email Templates')

@section('page_actions')
    <a href="{{ route('masters.default_email_templates.create') }}" class="btn btn-dark-blue btn-sm rounded-pill px-3">
        <i class="bi bi-plus-lg me-1"></i> Add Default Email Template
    </a>
@endsection

@section('content')
<div class="card border-0 shadow-sm mb-4 overflow-hidden">
    <div class="card-header bg-white border-bottom-0 py-3 px-4">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">Default Email Templates</h5>
            <form class="d-flex" method="GET" action="{{ route('masters.default_email_templates.index') }}">
                <input type="text" name="search" value="{{ $search ?? '' }}" class="form-control form-control-sm"
                       placeholder="Search by name...">
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="px-4">
            <div class="alert alert-success alert-dismissible fade show pb-2 pt-2 mb-3" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close pb-2 pt-2" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    @endif

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Name</th>
                        <th>Created By</th>
                        <th>Created At</th>
                        <th class="pe-4 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody id="email-templates-table-body">
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3">

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/assets/js/email-templates.js' : '/assets/js/email-templates.js') }}?v={{ filemtime(public_path('assets/js/email-templates.js')) }}"></script>
@endpush

