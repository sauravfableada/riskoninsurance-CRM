@extends('layouts.app')

@section('page_title', 'Email Templates')

@push('styles')
    <link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/users.css' : '/css/users.css') }}?v={{ filemtime(public_path('css/users.css')) }}">
    <link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/email_marketing.css' : '/css/email_marketing.css') }}?v={{ filemtime(public_path('css/email_marketing.css')) }}">
@endpush

@section('content')
    <div class="container-fluid p-0">

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-bottom-0 py-3 px-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 marketing-templates-header">
                    <div>
                        <h4 class="fw-bold mb-0">Email Templates</h4>
                        <p class="text-muted small mb-0">Manage reusable email layouts for campaigns.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2 marketing-templates-actions">
                        <a href="{{ route('marketing.templates.create') }}" class="btn btn-dark-blue">
                            <i class="bi bi-plus-lg me-1"></i>Create Template
                        </a>

                        <a href="{{ route('marketing.campaigns.index') }}" class="btn btn-dark-blue">
                            <i class="bi bi-send-fill me-1"></i>Send Email
                        </a>
                    </div>
                </div>
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <h6 class="fw-bold mb-0">Template Directory</h6>
                    <div class="input-group input-group-sm" style="max-width: 300px; width: 100%;">
                        <span class="input-group-text crm-search-icon border-0"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control crm-search-input border-0" placeholder="Search templates..."
                            id="templatesSearch">
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="templatesTable" class="table table-hover align-middle mb-0 responsive-table">
                        <thead>
                            <tr>
                                <th class="ps-4" style="width: 80px;">Sr.No</th>
                                <th>Template Name</th>
                                <th class="d-none d-md-table-cell">Status</th>
                                <th class="text-end pe-4 d-none d-md-table-cell" style="width: 140px;">Actions</th>
                                <th class="text-center d-md-none" style="width: 80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="templatesTableBody"></tbody>
                    </table>
                </div>
                <div id="templatesPagination" class="px-4 pb-3 pt-0"></div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.EmailMarketingConfig = {
            page: 'index',
            csrfToken: @json(csrf_token()),
            indexUrl: @json(route('marketing.templates.index')),
            templatesBaseUrl: @json(url('marketing/templates'))
        };
    </script>
    <script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/js/email_marketing.js' : '/js/email_marketing.js') }}?v={{ filemtime(public_path('js/email_marketing.js')) }}"></script>
@endpush
