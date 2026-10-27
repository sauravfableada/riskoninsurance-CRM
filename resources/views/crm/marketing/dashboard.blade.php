@extends('layouts.app')

@section('page_title', 'Marketing Dashboard')

@push('styles')
<style>
    @media (max-width: 767.98px) {
        .marketing-dashboard {
            padding-top: 1rem !important;
        }

        .marketing-dashboard .marketing-hero {
            gap: 1rem;
        }

        .marketing-dashboard .marketing-hero .text-md-end {
            text-align: left !important;
        }

        .marketing-dashboard .marketing-hero .btn {
            width: 100%;
        }

        .marketing-dashboard .card-header {
            gap: 0.75rem;
        }

        .marketing-dashboard .recent-logs-header {
            flex-direction: column;
            align-items: flex-start !important;
        }

        .marketing-dashboard .recent-logs-header .btn {
            width: 100%;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4 marketing-dashboard">
    <div class="row mb-4 marketing-hero">
        <div class="col-md-6">
            <h1 class="h3 fw-bold text-dark">Marketing Automation</h1>
            <p class="text-muted">Engage your leads and customers with automated Email and SMS campaigns.</p>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="{{ route('marketing.campaigns.create') }}" class="btn btn-dark-blue rounded-pill px-4">
                <i class="bi bi-plus-lg me-2"></i>New Campaign
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 py-2 rounded-4 border-start border-4 border-primary">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1" style="font-size: 0.7rem;">Active Templates</div>
                            <div class="h5 mb-0 fw-bold text-dark">{{ $stats['total_templates'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-file-earmark-richtext fs-2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 py-2 rounded-4 border-start border-4 border-success">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1" style="font-size: 0.7rem;">Total Campaigns</div>
                            <div class="h5 mb-0 fw-bold text-dark">{{ $stats['total_campaigns'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-megaphone fs-2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 py-2 rounded-4 border-start border-4 border-info">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1" style="font-size: 0.7rem;">Messages Sent</div>
                            <div class="h5 mb-0 fw-bold text-dark">{{ $stats['total_sent'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-send fs-2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 py-2 rounded-4 border-start border-4 border-warning">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1" style="font-size: 0.7rem;">Scheduled Today</div>
                            <div class="h5 mb-0 fw-bold text-dark">0</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-clock-history fs-2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Quick Actions -->
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold text-dark m-0">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="{{ route('marketing.templates.create') }}" class="list-group-item list-group-item-action border-0 px-0 d-flex align-items-center">
                            <div class="bg-primary bg-opacity-10 p-2 rounded-3 text-primary me-3">
                                <i class="bi bi-file-earmark-plus"></i>
                            </div>
                            <div>
                                <div class="fw-bold small">Create Template</div>
                                <div class="text-muted" style="font-size: 0.75rem;">Design new message layouts</div>
                            </div>
                        </a>
                        <a href="{{ route('marketing.campaigns.create') }}" class="list-group-item list-group-item-action border-0 px-0 d-flex align-items-center">
                            <div class="bg-success bg-opacity-10 p-2 rounded-3 text-success me-3">
                                <i class="bi bi-rocket-takeoff"></i>
                            </div>
                            <div>
                                <div class="fw-bold small">Launch Campaign</div>
                                <div class="text-muted" style="font-size: 0.75rem;">Start bulk outreach</div>
                            </div>
                        </a>
                        <a href="#" class="list-group-item list-group-item-action border-0 px-0 d-flex align-items-center">
                            <div class="bg-info bg-opacity-10 p-2 rounded-3 text-info me-3">
                                <i class="bi bi-cake2"></i>
                            </div>
                            <div>
                                <div class="fw-bold small">Birthday Automations</div>
                                <div class="text-muted" style="font-size: 0.75rem;">Auto-greet your customers</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="col-lg-8 mb-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center recent-logs-header">
                    <h6 class="fw-bold text-dark m-0">Recent Logs</h6>
                    <a href="#" class="btn btn-sm btn-light rounded-pill">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4 small text-muted">CAMPAIGN</th>
                                    <th class="small text-muted">RECIPIENT</th>
                                    <th class="small text-muted">STATUS</th>
                                    <th class="small text-muted">TIME</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($stats['recent_logs'] as $log)
                                <tr>
                                    <td class="ps-4 fw-bold small">{{ $log->campaign->name ?? 'System Trigger' }}</td>
                                    <td class="small">{{ $log->recipient_email ?: $log->recipient_phone }}</td>
                                    <td>
                                        <span class="badge crm-status-pill bg-{{ $log->status == 'Sent' ? 'success' : 'danger' }} rounded-pill">
                                            {{ $log->status }}
                                        </span>
                                    </td>
                                    <td class="text-muted small">{{ $log->created_at->diffForHumans() }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted small">No recent activity recorded.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
