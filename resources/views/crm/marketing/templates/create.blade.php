@extends('layouts.app')

@section('page_title', 'Create Template')

@push('styles')
    <link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/email_marketing.css' : '/css/email_marketing.css') }}?v={{ filemtime(public_path('css/email_marketing.css')) }}">
@endpush

@section('content')
    <div class="container-fluid p-0 marketing-template-page">
        <div class="row justify-content-center">
            <div class="col-lg-12">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="p-4">

                        <div class="d-flex justify-content-between align-items-center border-bottom pb-3 marketing-template-header">
                            <div>
                                <h1 class="h4 mb-1">Create Message Template</h1>
                                <p class="text-muted small mb-0">Design a reusable layout for automated and manual campaigns.</p>
                            </div>
                            <a href="{{ route('marketing.templates.index') }}" class="btn btn-dark-blue"><i class="fa-solid fa-angle-left pe-2"></i>Back</a>
                        </div>
                    </div>

                    <div class="card-body px-4 pb-4">
                        <form id="marketingTemplateForm">
                            @csrf
                            <div class="row g-4">
                                <div class="col-md-12">
                                    <label class="form-label">Select Template</label>
            
                                    <div id="carouselExampleControlsNoTouching" class="carousel slide marketing-template-carousel" data-bs-touch="false">
                                        <div class="carousel-inner">
                                            <div class="carousel-item active" data-template-name="template_1">
                                                <h4 class="text-center">Template 1</h4>
                                                <div class="card template-card crm-template-card">
                                                    @include('crm.marketing.email.template.template_1')
                                                </div>
                                            </div>
                                            <div class="carousel-item" data-template-name="template_2">
                                                <h4 class="text-center">Template 2</h4>
                                                <div class="card template-card crm-template-card">
                                                    @include('crm.marketing.email.template.template_2')
                                                </div>
                                            </div>
                                        </div>
                                        <button class="carousel-control-prev" type="button"
                                            data-bs-target="#carouselExampleControlsNoTouching" data-bs-slide="prev">
                                            <span class="carousel-control-prev-icon bg-dark" aria-hidden="true"></span>
                                            <span class="visually-hidden">Previous</span>
                                        </button>
                                        <button class="carousel-control-next" type="button"
                                            data-bs-target="#carouselExampleControlsNoTouching" data-bs-slide="next">
                                            <span class="carousel-control-next-icon bg-dark" aria-hidden="true"></span>
                                            <span class="visually-hidden">Next</span>
                                        </button>
                                    </div>
                                </div>

                                <div class="col-md-8">
                                    <label class="form-label"><i class="bi bi-file-text me-1"></i> Template Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label"><i class="bi bi-info-circle me-1"></i> Template Status <span class="text-danger">*</span></label>
                                    <select name="status" id="status" class="form-select">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label"><i class="bi bi-image me-1"></i> Image 1</label>
                                    <input class="form-control" type="file" name="image_1" id="image_1">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label"><i class="bi bi-image me-1"></i> Image 2</label>
                                    <input class="form-control" type="file" name="image_2" id="image_2">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label"><i class="bi bi-image me-1"></i> Image 3</label>
                                    <input class="form-control" type="file" name="image_3" id="image_3">
                                </div>

                                <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                                    <a href="{{ route('marketing.templates.index') }}" class="btn btn-outline-dark-blue">Cancel</a>
                                    <button type="submit" class="btn btn-dark-blue">Save Template</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
@push('scripts')
    <script>
        window.EmailMarketingConfig = {
            page: 'form',
            submitUrl: @json(route('marketing.templates.store')),
            redirectUrl: @json(route('marketing.templates.index'))
        };
    </script>
    <script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/js/email_marketing.js' : '/js/email_marketing.js') }}?v={{ filemtime(public_path('js/email_marketing.js')) }}"></script>
@endpush
