@extends('layouts.app')

@section('page_title', 'SMS Marketing')

@push('styles')
    <link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/users.css' : '/css/users.css') }}?v={{ filemtime(public_path('css/users.css')) }}">
    <link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/sms_marketing.css' : '/css/sms_marketing.css') }}?v={{ filemtime(public_path('css/sms_marketing.css')) }}">
@endpush

@section('content')
<div class="container-fluid p-0">
    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="card-header bg-white border-bottom-0 py-3 px-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 sms-marketing-header">
                <div>
                    <h4 class="fw-bold mb-0">SMS Marketing</h4>
                    <p class="text-muted small mb-0">Manage SMS templates and gateway settings in one place.</p>
                </div>
                <div class="d-flex flex-wrap gap-2 sms-marketing-actions">
                    <a href="{{ route('marketing.sms_marketing.templates.create') }}" class="btn btn-dark-blue">
                        <i class="bi bi-plus-lg me-1"></i>Create Template
                    </a>
                    <a href="{{ route('marketing.sms_marketing.logs') }}" class="btn btn-dark-blue">
                        <i class="bi bi-send-fill me-1"></i>Send SMS
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <ul class="nav nav-tabs px-4 border-bottom-0" id="smsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link @if(!request('tab') || request('tab') == 'templates') active @endif fw-bold py-3" id="templates-tab" data-bs-toggle="tab" data-bs-target="#templates" type="button" role="tab">
                        Templates
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link @if(request('tab') == 'credentials') active @endif fw-bold py-3" id="credentials-tab" data-bs-toggle="tab" data-bs-target="#credentials" type="button" role="tab">
                        SMS Credentials
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="smsTabsContent">
                <div class="tab-pane fade @if(!request('tab') || request('tab') == 'templates') show active @endif" id="templates" role="tabpanel">
                    <div class="p-4 border-top">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                            <h6 class="fw-bold mb-0">Template Directory</h6>
                            <div class="input-group input-group-sm" style="max-width: 300px; width: 100%;">
                                <span class="input-group-text crm-search-icon border-0"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control crm-search-input border-0" placeholder="Search templates..." id="smsTemplatesSearch">
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 responsive-table" id="smsTemplatesTable">
                                <thead>
                                    <tr>
                                        <th class="ps-4" style="width: 80px;">Sr.No</th>
                                        <th>Template Name</th>
                                        <th class="d-none d-md-table-cell">Status</th>
                                        <th class="text-end pe-4 d-none d-md-table-cell" style="width: 140px;">Action</th>
                                        <th class="text-center d-md-none" style="width: 80px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="smsTemplatesTableBody"></tbody>
                            </table>
                        </div>

                        <div id="smsTemplatesPagination" class="pt-3"></div>
                    </div>
                </div>

                <div class="tab-pane fade @if(request('tab') == 'credentials') show active @endif" id="credentials" role="tabpanel">
                    <div class="p-4 border-top bg-light">
                        <form action="{{ route('marketing.sms_marketing.save_credentials') }}" method="POST" id="saveCredentialsForm">
                            @csrf
                            <div class="mb-4 pb-3 border-bottom">
                                <h6 class="fw-bold mb-3">Default SMS Service</h6>
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label text-muted small fw-bold">Default:</label>
                                        <select name="sms_default_service" class="form-select border shadow-none bg-light">
                                            <option value="twilio" {{ $credentials['sms_default_service'] == 'twilio' ? 'selected' : '' }}>Twilio</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <h6 class="fw-bold mb-3">Twilio API Credentials</h6>
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label text-muted small fw-bold">Twilio SID:</label>
                                        <input type="text" name="twilio_sid" value="{{ $credentials['twilio_sid'] }}" class="form-control border shadow-none bg-light" placeholder="Enter Twilio SID">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted small fw-bold">Twilio Auth Token:</label>
                                        <input type="password" name="twilio_auth_token" value="{{ $credentials['twilio_auth_token'] }}" class="form-control border shadow-none bg-light" placeholder="Enter Auth Token">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted small fw-bold">Twilio Phone Number:</label>
                                        <input type="text" name="twilio_phone_number" value="{{ $credentials['twilio_phone_number'] }}" class="form-control border shadow-none bg-light" placeholder="+1234567890">
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 pt-2 sms-credentials-actions">
                                <button type="submit" class="btn btn-dark-blue border-0 px-4" id="btnSaveCredentials">
                                    Save Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
window.SmsMarketingConfig = {
    page: 'index',
    csrfToken: @json(csrf_token()),
    indexUrl: @json(route('marketing.sms_marketing.index')),
    templateEditBaseUrl: @json(url('marketing/sms-marketing/templates')),
    templateDeleteBaseUrl: @json(url('marketing/sms-marketing/templates'))
};
</script>
<script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/js/sms_marketing.js' : '/js/sms_marketing.js') }}?v={{ filemtime(public_path('js/sms_marketing.js')) }}"></script>
@endpush
