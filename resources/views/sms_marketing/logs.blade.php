@extends('layouts.app')

@section('page_title', 'SMS Marketing Logs')

@push('styles')
    <link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/users.css' : '/css/users.css') }}?v={{ filemtime(public_path('css/users.css')) }}">
    <link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/sms_marketing.css' : '/css/sms_marketing.css') }}?v={{ filemtime(public_path('css/sms_marketing.css')) }}">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
@endpush

@section('content')
<div class="container-fluid p-0">
    <div class="card border-0 shadow-sm overflow-hidden text-sm">
        <div class="card-header bg-white border-bottom-0 py-3 px-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 sms-logs-header">
                <div>
                    <h4 class="fw-bold mb-0">SMS Marketing</h4>
                    <p class="text-muted small mb-0">Review delivery history and send SMS to selected customers.</p>
                </div>
                <div class="sms-logs-actions">
                    <a href="{{ route('marketing.sms_marketing.templates.create') }}" class="btn btn-dark-blue">
                        Create Template
                    </a>
                    <button type="button" class="btn btn-dark-blue" data-bs-toggle="modal" data-bs-target="#sendSmsModal">
                        Send SMS <i class="bi bi-send ms-1"></i>
                    </button>
                </div>
            </div>

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <h6 class="fw-bold mb-0">SMS Logs</h6>
                <div class="input-group input-group-sm" style="max-width: 300px; width: 100%;">
                    <span class="input-group-text crm-search-icon border-0"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control crm-search-input border-0" placeholder="Search logs..." id="smsLogsSearch">
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 responsive-table" id="smsLogsTable">
                    <thead>
                        <tr>
                            <th style="width: 80px;">Sr.No</th>
                            <th>Customer</th>
                            <th class="d-none d-md-table-cell">Send Date</th>
                            <th>Template Name</th>
                            <th class="d-none d-md-table-cell">Status</th>
                            <th class="d-none d-md-table-cell">Service</th>
                            <th class="text-end pe-4 d-none d-md-table-cell" style="width: 100px;">Action</th>
                            <th class="text-center d-md-none" style="width: 80px;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="smsLogsTableBody"></tbody>
                </table>
            </div>
            <div id="smsLogsPagination" class="px-4 pb-3 pt-0"></div>
        </div>
    </div>
</div>

<div class="modal fade" id="sendSmsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header py-3 px-4">
                <h5 class="modal-title fw-bold">Send SMS</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="sendSmsForm">
                    @csrf
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted mb-2">Select Customers</label>
                        <select name="customer_ids[]" id="customer_ids" class="form-select" multiple placeholder="--Select-Customers--">
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }} ({{ $customer->phone }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted mb-2">Select Template</label>
                        <select name="template_id" id="template_id" class="form-select">
                            <option value="">--Select Template--</option>
                            @foreach($templates as $template)
                                <option value="{{ $template->id }}">{{ $template->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mt-4 pt-2 d-grid">
                        <button type="submit" class="btn btn-dark-blue" id="btnSendSms">
                            <i class="bi bi-send-fill me-1"></i> Send SMS
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
window.SmsMarketingConfig = {
    page: 'logs',
    csrfToken: @json(csrf_token()),
    logsUrl: @json(route('marketing.sms_marketing.logs')),
    sendSmsUrl: @json(route('marketing.sms_marketing.send_sms')),
    logDeleteBaseUrl: @json(url('marketing/sms-marketing/logs'))
};
</script>
<script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/js/sms_marketing.js' : '/js/sms_marketing.js') }}?v={{ filemtime(public_path('js/sms_marketing.js')) }}"></script>
@endpush
