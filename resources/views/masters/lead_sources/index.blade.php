@extends('layouts.masters')

@section('page_title', 'Masters - Lead Sources')

@section('masters_content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h5 mb-0 text-muted fw-semibold">Manage Lead Sources</h2>
    <a href="{{ route('masters.lead_sources.create') }}" class="btn btn-dark-blue btn-sm">Add Lead Source</a>
</div>

@if (session('success'))
    <div class="alert alert-success py-2">{{ session('success') }}</div>
@endif

<div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($leadSources as $source)
                            <tr>
                                <td>{{ $source->name }}</td>
                                <td>{{ $source->description }}</td>
                                <td>
                                    @if($source->is_active)
                                        <span class="badge crm-status-pill rounded-pill bg-success">Active</span>
                                    @else
                                        <span class="badge crm-status-pill rounded-pill bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('masters.lead_sources.edit', $source) }}" class="btn btn-sm crm-action-btn">Edit</a>
                                    <form action="{{ route('masters.lead_sources.destroy', $source) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm crm-action-btn" data-confirm-message="Delete this lead source?">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted small">No lead sources added yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
</div>
<div class="mt-3">
    {{ $leadSources->links() }}
</div>
@endsection
