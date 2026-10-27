@extends('layouts.masters')

@section('page_title', 'Masters - Agents')

@section('masters_content')
@push('styles')
    <link rel="stylesheet"
        href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/main.css' : '/css/main.css') }}?v={{ filemtime(public_path('css/main.css')) }}">
    <link rel="stylesheet"
        href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/users.css' : '/css/users.css') }}?v={{ filemtime(public_path('css/users.css')) }}">
@endpush

@if (session('success'))
    <div class="alert alert-success py-2">{{ session('success') }}</div>
@endif

<div class="card-header border-bottom-0 py-3 px-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-0">Manage Agents</h4>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('masters.agents.create') }}" class="btn btn-dark-blue">
                <i class="fa-solid fa-plus me-1"></i>Add Agent
            </a>
        </div>
    </div>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <h6 class="fw-bold mb-0">Active Agents</h6>
    </div>
</div>

<div class="card-body p-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-4">Name</th>
                    <th>Code</th>
                    <th>Location</th>
                    <th>Type</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($agents as $agent)
                    <tr>
                        <td class="ps-4">{{ $agent->name }}</td>
                        <td>{{ $agent->code }}</td>
                        <td>{{ $agent->city?->name ?? '--' }}, {{ $agent->country?->name ?? '--' }}</td>
                        <td>{{ $agent->type }}</td>
                        <td>{{ $agent->email }}</td>
                        <td>{{ $agent->phone }}</td>
                        <td>
                            @if($agent->is_active)
                                <span class="badge crm-status-pill rounded-pill bg-success">Active</span>
                            @else
                                <span class="badge crm-status-pill rounded-pill bg-secondary">Inactive</span>
                            @endif
                        </td>
                        <td class="text-end pe-4">
                            <a href="{{ route('masters.agents.edit', $agent) }}" class="btn btn-sm crm-action-btn">Edit</a>
                            <form action="{{ route('masters.agents.destroy', $agent) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm crm-action-btn" data-confirm-message="Delete this agent?">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted small py-4">No agents added yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer border-top-0 py-4 px-4">
        {{ $agents->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection
