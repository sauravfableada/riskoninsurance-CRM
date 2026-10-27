@extends('layouts.app')

@section('page_title', 'Masters - Statuses')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h5 mb-0">Statuses</h1>
        <a href="{{ route('masters.statuses.create') }}" class="btn btn-dark-blue btn-sm">Add Status</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Color</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($statuses as $status)
                            <tr>
                                <td>{{ $status->name }}</td>
                                <td>{{ $status->type }}</td>
                                <td>
                                    @if($status->color)
                                        <span class="badge rounded-pill" style="background: {{ $status->color }};">&nbsp;</span>
                                        <span class="small text-muted ms-1">{{ $status->color }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($status->is_active)
                                        <span class="badge crm-status-pill rounded-pill bg-success">Active</span>
                                    @else
                                        <span class="badge crm-status-pill rounded-pill bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('masters.statuses.edit', $status) }}" class="btn btn-sm crm-action-btn">Edit</a>
                                    <form action="{{ route('masters.statuses.destroy', $status) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm crm-action-btn" data-confirm-message="Delete this status?">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted small">No statuses added yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $statuses->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
