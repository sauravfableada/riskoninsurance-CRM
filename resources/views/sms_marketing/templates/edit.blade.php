@extends('layouts.app')

@section('page_title', 'Edit SMS Template')

@push('styles')
<link rel="stylesheet"
    href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/sms_marketing.css' : '/css/sms_marketing.css') }}?v={{ filemtime(public_path('css/sms_marketing.css')) }}">
@endpush

@section('content')
<div class="container-fluid p-0">
    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="p-4">
            <div class="d-flex justify-content-between align-items-center border-bottom pb-3 sms-template-header">
                <h4 class="fw-bold mb-0">Edit SMS Template</h4>
                <a href="{{ route('marketing.sms_marketing.index') }}" class="btn btn-dark-blue">
                    <i class="fa-solid fa-arrow-left pe-2"></i> Back
                </a>
            </div>
        </div>
        <div class="card-body px-4 pb-4">
            <form action="{{ route('marketing.sms_marketing.templates.update', $record) }}" method="POST" id="templateForm">
                @csrf
                @method('PUT')
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-bold">Template Name</label>
                        <input type="text" name="name" value="{{ $record->name }}" class="form-control" placeholder="Enter template name">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-bold">Template Status</label>
                        <select name="status" class="form-select">
                            <option value="active" {{ $record->status == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ $record->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label text-muted small fw-bold">Content</label>
                    <textarea name="content" class="form-control" rows="5" placeholder="Type your message here...">{{ $record->content }}</textarea>
                    
                    <div class="crm-note-box p-3 mt-4 rounded border border-secondary border-opacity-25">
                        <p class="text-muted small mb-2"><i class="bi bi-info-circle me-1 text-primary"></i> <strong>Note:</strong> You can use the following shortcodes in your template:</p>
                        <ul class="list-unstyled mb-0 small ps-3">
                            <li class="mb-1"><code class="crm-inline-code-accent">[user_name]</code>: This will be replaced with the name of the customer.</li>
                            <li><code class="crm-inline-code-accent">[company_name]</code>: This will be replaced with the company name of the user.</li>
                        </ul>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                    <a href="{{ route('marketing.sms_marketing.index') }}" class="btn btn-outline-dark-blue">Cancel</a>
                    <button type="submit" class="btn btn-dark-blue" id="btnSubmit">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
window.SmsMarketingConfig = {
    page: 'template-form',
    redirectUrl: @json(route('marketing.sms_marketing.index')),
    submitLoadingText: 'Updating...'
};
</script>
<script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/js/sms_marketing.js' : '/js/sms_marketing.js') }}?v={{ filemtime(public_path('js/sms_marketing.js')) }}"></script>
@endpush
