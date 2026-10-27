@extends('layouts.masters')

@section('page_title', 'Masters - Hotels')

@section('masters_content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h5 mb-0 text-muted fw-semibold">Manage Hotels</h2>
    <a href="{{ route('masters.hotels.create') }}" class="btn btn-dark-blue btn-sm">Add Hotel</a>
</div>

@if (session('success'))
    <div class="alert alert-success py-2">{{ session('success') }}</div>
@endif

<div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Location</th>
                            <th>Stars</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($hotels as $hotel)
                            <tr>
                                <td>{{ $hotel->name }}</td>
                                <td>{{ $hotel->city?->name ?? '--' }}</td>
                                <td>{{ $hotel->star_rating }}</td>
                                <td>{{ $hotel->email }}</td>
                                <td>{{ $hotel->phone }}</td>
                                <td>
                                    @if($hotel->is_active)
                                        <span class="badge crm-status-pill rounded-pill bg-success">Active</span>
                                    @else
                                        <span class="badge crm-status-pill rounded-pill bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('masters.hotels.edit', $hotel) }}" class="btn btn-sm crm-action-btn">Edit</a>
                                    <form action="{{ route('masters.hotels.destroy', $hotel) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm crm-action-btn" data-confirm-message="Delete this hotel?">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted small">No hotels added yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
</div>
<div class="mt-3">
    {{ $hotels->links() }}
</div>
@endsection
