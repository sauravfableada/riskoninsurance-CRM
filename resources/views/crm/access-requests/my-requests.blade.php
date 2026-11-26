@extends('layouts.app')

@section('page_title', 'My Access Requests')

@push('styles')
<link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/main.css' : '/css/main.css') }}?v={{ filemtime(public_path('css/main.css')) }}">
@endpush

@section('content')
<div class="container-fluid p-0">
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 px-4">
            <h4 class="fw-bold mb-1">My Access Requests</h4>
            <p class="text-muted small mb-0">Track the status of your permission requests and administrator feedback.</p>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Module</th>
                            <th>Requested Action</th>
                            <th>Reason</th>
                            <th>Requested At</th>
                            <th>Status</th>
                            <th class="pe-4">Admin Comment</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $req)
                            @php
                                $moduleLabel = $modules[$req->module]['label'] ?? ucfirst(str_replace('_', ' ', $req->module));
                            @endphp
                            <tr>
                                <td class="ps-4 fw-semibold">{{ $moduleLabel }}</td>
                                <td>
                                    <span class="fw-semibold text-capitalize">{{ $req->action }}</span>
                                    <code class="small ms-1">{{ $req->permission }}</code>
                                </td>
                                <td style="max-width: 260px;"><span class="small">{{ $req->reason }}</span></td>
                                <td class="small text-muted">{{ optional($req->requested_at ?? $req->created_at)->format('d M Y, h:i A') }}</td>
                                <td>
                                    @if($req->status === 'approved')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3">Approved – Action Enabled</span>
                                    @elseif($req->status === 'rejected')
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3">Rejected</span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-3">Pending – Awaiting Approval</span>
                                    @endif
                                </td>
                                <td class="pe-4 small text-muted">{{ $req->admin_comment ?: '--' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">You have not submitted any access requests yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($requests->hasPages())
            <div class="card-footer bg-white py-3 px-4">
                {{ $requests->links() }}
            </div>
        @endif
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 px-4">
            <h5 class="fw-bold mb-0">Currently Active Approved Permissions</h5>
        </div>
        <div class="card-body p-4">
            @if($activeGrants->isEmpty())
                <p class="text-muted small mb-0">No individual permissions have been granted via access requests yet.</p>
            @else
                <div class="d-flex flex-wrap gap-2">
                    @foreach($activeGrants as $grant)
                        @php
                            $mLabel = $modules[$grant->module]['label'] ?? ucfirst(str_replace('_', ' ', $grant->module));
                        @endphp
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2">
                            <i class="bi bi-check-circle me-1"></i>
                            {{ ucfirst($grant->action) }} ({{ $mLabel }})
                        </span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
