@extends('layouts.app')

@section('page_title', 'Template Details')

@push('styles')
    <link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/email_marketing.css' : '/css/email_marketing.css') }}?v={{ filemtime(public_path('css/email_marketing.css')) }}">
@endpush

@section('content')
    <div class="container-fluid p-0 marketing-template-show">
        <div class="row justify-content-center">
            <div class="col-lg-12">

                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white border-bottom-0 py-3 px-4">
                        <div class="d-flex justify-content-between align-items-center marketing-template-show-header">
                            <h4 class="fw-bold mb-0">Template Details</h4>

                            <div class="d-flex align-items-center gap-2 marketing-template-show-actions">
                                <a href="{{ route('marketing.templates.edit', $template->id) }}" class="btn btn-dark-blue">
                                    Edit
                                </a>

                                <a href="{{ route('marketing.templates.index') }}" class="btn btn-dark-blue">
                                    <i class="fa-solid fa-angle-left pe-2"></i>Back
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="card-body px-4 pb-4">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-2 text-muted">
                                    <strong>Template Name :</strong> {{ $template->name }}
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-2 text-muted">
                                    <strong>Template Status :</strong>
                                    <span class="badge crm-status-pill rounded-pill {{ $template->status == 'active' ? 'bg-success' : 'bg-secondary' }}">
                                        {{ ucfirst($template->status) }}
                                    </span>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="row d-flex justify-content-center">
                                    @if($template->template_name)
                                        @include('crm.marketing.email.template.' . $template->template_name)
                                    @else
                                        <p class="text-danger">Template file not found.</p>
                                    @endif
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
