@extends('layouts.app')

@section('page_title', 'Sales Performance')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Sales Performance</h1>
            <p class="text-muted small">Analysis of agent productivity and sales volume.</p>
        </div>
        <div>
            <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-2"></i>Back to Analytics
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Agent Performance leaderboard</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-3">Agent</th>
                                    <th class="text-center">Total Bookings</th>
                                    <th>Total Sales Value</th>
                                    <th>Avg. Value / Booking</th>
                                    <th class="text-end pe-3">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($agentPerformance as $agent)
                                <tr>
                                    <td class="ps-3">
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle bg-soft-primary p-2 me-3" style="background-color: #e8eaf6;">
                                                <i class="bi bi-person text-primary"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark">{{ $agent->name }}</div>
                                                <div class="text-muted small">{{ $agent->code }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border">{{ $agent->bookings_count }}</span>
                                    </td>
                                    <td class="fw-bold text-primary">₹{{ number_format($agent->total_sales, 2) }}</td>
                                    <td class="text-muted">
                                        ₹{{ $agent->bookings_count > 0 ? number_format($agent->total_sales / $agent->bookings_count, 2) : '0.00' }}
                                    </td>
                                    <td class="text-end pe-3">
                                        <span class="badge {{ $agent->is_active ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $agent->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No agent performance data found.</td>
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
