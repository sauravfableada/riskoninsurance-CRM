@extends('layouts.app')

@section('page_title', 'Launch Campaign')

@push('styles')
<style>
    @media (max-width: 767.98px) {
        .campaign-create-page {
            padding-top: 1rem !important;
        }

        .campaign-create-page .card-header {
            flex-direction: column;
            align-items: flex-start !important;
        }

        .campaign-create-page .card-header .rounded-circle {
            width: 52px;
            height: 52px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .campaign-create-page .btn {
            width: 100%;
        }

        .campaign-create-page .btn + .btn {
            margin-top: 0.75rem;
            margin-left: 0 !important;
        }

        .campaign-create-page .text-end {
            text-align: left !important;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4 campaign-create-page">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white border-0 py-4 px-4 d-flex align-items-center gap-3">
                    <div class="bg-success bg-opacity-10 p-3 rounded-circle text-success">
                        <i class="bi bi-rocket-takeoff-fill fs-4"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold text-dark mb-0">Launch New Campaign</h5>
                        <p class="text-muted small mb-0">Blast your message to a specific segment of your database.</p>
                    </div>
                </div>
                <div class="card-body px-4 pb-4">
                    <form action="{{ route('marketing.campaigns.store') }}" method="POST">
                        @csrf
                        <div class="row g-4">
                            <div class="col-md-12">
                                <label class="form-label fw-bold small text-muted text-uppercase">Campaign Name</label>
                                <input type="text" name="name" class="form-control" placeholder="e.g., Summer 2024 Travel Deals" required>
                                <div class="form-text small text-muted">For internal tracking purposes only.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted text-uppercase">Select Template</label>
                                <select name="marketing_template_id" class="form-select" required>
                                    <option value="" disabled selected>Choose a template...</option>
                                    @foreach($templates as $template)
                                    <option value="{{ $template->id }}">{{ $template->name }} ({{ $template->type }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted text-uppercase">Target Audience</label>
                                <select name="audience_type" class="form-select" required>
                                    <option value="" disabled selected>Select segment...</option>
                                    <option value="Leads">All Active Leads</option>
                                    <option value="Customers">Verified Customers</option>
                                    <option value="Agents">Registered Agents</option>
                                </select>
                            </div>

                            <div class="col-12 mt-5 text-end">
                                <hr class="my-4 opacity-50">
                                <a href="{{ route('marketing.campaigns.index') }}" class="btn btn-light rounded-pill px-4 me-2">Cancel</a>
                                <button type="submit" class="btn btn-dark-blue rounded-pill px-5 fw-bold">Create Campaign</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body">
                    <h6 class="fw-bold text-dark mb-3">Audience Breakdown</h6>
                    <div class="list-group list-group-flush small">
                        <div class="list-group-item d-flex justify-content-between px-0 bg-transparent border-0">
                            <span class="text-muted">Active Leads:</span>
                            <span class="fw-bold text-dark">{{ App\Models\Lead::count() }} recipients</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between px-0 bg-transparent border-0">
                            <span class="text-muted">Customers:</span>
                            <span class="fw-bold text-dark">{{ App\Models\Customer::count() }} recipients</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between px-0 bg-transparent border-0">
                            <span class="text-muted">Agents:</span>
                            <span class="fw-bold text-dark">{{ App\Models\Agent::count() }} recipients</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 mb-4 bg-primary text-white">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-2"></i>Note on Sending</h6>
                    <p class="small mb-0 opacity-75">
                        Campaigns are created as **Drafts**. You can review the details before triggering the final blast. 
                        Messages are sent individually to ensure deliverability.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
