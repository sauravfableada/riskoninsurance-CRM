@extends('layouts.app')

@section('page_title', 'View Default Email Template')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">{{ $template->name }}</h5>
                <a href="{{ route('masters.default_email_templates.edit', $template) }}" class="btn btn-sm crm-action-btn">
                    <i class="bi bi-pencil me-1"></i> Edit
                </a>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="mb-2 text-muted small">
                    Created at: {{ $template->created_at?->format('d M Y H:i') }}
                </div>
                <div class="border rounded p-3 bg-light">
                    {!! $template->content !!}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

