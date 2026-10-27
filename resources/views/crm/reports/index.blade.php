@extends('layouts.app')

@section('page_title', 'Analytics Dashboard')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Analytics Dashboard</h1>
            <p class="text-muted small">Real-time overview of your business performance.</p>
        </div>
        <div>
            <button class="btn crm-action-btn btn-sm" onclick="window.print()">
                <i class="bi bi-printer me-2"></i>Print Report
            </button>
        </div>
    </div>

    <!-- KPI Row -->
    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 py-2 border-start border-primary border-4">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Revenue</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">₹{{ number_format($stats['total_revenue'], 2) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-currency-dollar fs-2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 py-2 border-start border-success border-4">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Customers</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['total_customers']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-people fs-2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 py-2 border-start border-warning border-4">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Conversion Rate</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['conversion_rate'] }}%</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-person-check fs-2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 py-2 border-start border-danger border-4">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Pending Tasks</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['pending_tasks']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-list-task fs-2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Revenue Chart -->
        <div class="col-xl-8 col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="m-0 font-weight-bold text-primary text-uppercase small">Revenue Distribution</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area" style="height: 320px;">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Task Status Chart -->
        <div class="col-xl-4 col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="m-0 font-weight-bold text-primary text-uppercase small">Task Status</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2" style="height: 250px;">
                        <canvas id="taskStatusChart"></canvas>
                    </div>
                    <div class="mt-4 text-center small">
                        @foreach($tasksByStatus as $status => $count)
                            <span class="mr-2">
                                <i class="bi bi-circle-fill text-{{ $status === 'completed' ? 'success' : ($status === 'in_progress' ? 'info' : 'warning') }}"></i> {{ ucfirst($status) }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Customer Growth Chart -->
        <div class="col-xl-8 col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="m-0 font-weight-bold text-primary text-uppercase small">Customer Growth</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area" style="height: 320px;">
                        <canvas id="customerGrowthChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lead Status Distribution -->
        <div class="col-xl-4 col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="m-0 font-weight-bold text-primary text-uppercase small">Lead Pipeline</h6>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @foreach($leadsByStatus as $status => $count)
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <span>{{ ucfirst(str_replace('_', ' ', $status)) }}</span>
                                <span class="badge bg-primary rounded-pill">{{ $count }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="row g-4">
        <div class="col-lg-4">
            <a href="{{ route('reports.profit_loss') }}" class="card border-0 shadow-sm text-decoration-none hover-shadow">
                <div class="card-body text-center py-4">
                    <i class="bi bi-file-earmark-bar-graph fs-1 text-primary mb-3"></i>
                    <h5 class="fw-bold text-dark">Profit & Loss</h5>
                    <p class="text-muted small mb-0">Booking-wise revenue vs costs.</p>
                </div>
            </a>
        </div>
        <div class="col-lg-4">
            <a href="{{ route('reports.sales') }}" class="card border-0 shadow-sm text-decoration-none hover-shadow">
                <div class="card-body text-center py-4">
                    <i class="bi bi-person-lines-fill fs-1 text-success mb-3"></i>
                    <h5 class="fw-bold text-dark">Sales Performance</h5>
                    <p class="text-muted small mb-0">Agent & package sales analysis.</p>
                </div>
            </a>
        </div>
        <div class="col-lg-4">
            <a href="{{ route('reports.pending') }}" class="card border-0 shadow-sm text-decoration-none hover-shadow">
                <div class="card-body text-center py-4">
                    <i class="bi bi-wallet2 fs-1 text-warning mb-3"></i>
                    <h5 class="fw-bold text-dark">Pending Accounts</h5>
                    <p class="text-muted small mb-0">Unpaid invoices & payables.</p>
                </div>
            </a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Revenue Chart
    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: {!! json_encode($monthlyRevenue->pluck('month')) !!},
            datasets: [{
                label: "Revenue",
                tension: 0.3,
                backgroundColor: "rgba(78, 115, 223, 0.05)",
                borderColor: "rgba(78, 115, 223, 1)",
                pointRadius: 3,
                pointBackgroundColor: "rgba(78, 115, 223, 1)",
                pointBorderColor: "rgba(78, 115, 223, 1)",
                pointHoverRadius: 3,
                pointHoverBackgroundColor: "rgba(78, 115, 223, 1)",
                pointHoverBorderColor: "rgba(78, 115, 223, 1)",
                pointHitRadius: 10,
                pointBorderWidth: 2,
                data: {!! json_encode($monthlyRevenue->pluck('revenue')) !!},
            }],
        },
        options: {
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true } }
        }
    });

    // Task Status Chart
    new Chart(document.getElementById('taskStatusChart'), {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($tasksByStatus->keys()) !!},
            datasets: [{
                data: {!! json_encode($tasksByStatus->values()) !!},
                backgroundColor: ['#f6c23e', '#36b9cc', '#1cc88a'],
                hoverBackgroundColor: ['#f4b619', '#2c9faf', '#17a673'],
                hoverBorderColor: "rgba(234, 236, 244, 1)",
            }],
        },
        options: {
            maintainAspectRatio: false,
            cutout: '80%',
        },
    });

    // Customer Growth Chart
    new Chart(document.getElementById('customerGrowthChart'), {
        type: 'bar',
        data: {
            labels: {!! json_encode($customerGrowth->pluck('month')) !!},
            datasets: [{
                label: "New Customers",
                backgroundColor: "#4e73df",
                hoverBackgroundColor: "#2e59d9",
                borderColor: "#4e73df",
                data: {!! json_encode($customerGrowth->pluck('total')) !!},
            }],
        },
        options: {
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true } }
        }
    });
</script>
<style>
    .hover-shadow:hover {
        transform: translateY(-5px);
        transition: all 0.3s ease;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }
</style>
@endpush
