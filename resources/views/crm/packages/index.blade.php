@extends('layouts.app')

@section('page_title', 'Tour Packages')

@section('page_actions')
    <a href="{{ route('packages.create') }}" class="btn btn-dark-blue btn-sm rounded-pill px-3">
        <i class="bi bi-plus-lg me-1"></i> Add Package
    </a>
@endsection

@section('content')
<div class="card border-0 shadow-sm mb-4 overflow-hidden">
    <div class="card-header bg-white border-bottom-0 py-4 px-4">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">Tour Packages Inventory</h5>
        </div>
    </div>
    
    @if(session('success'))
        <div class="px-4">
            <div class="alert alert-success alert-dismissible fade show pb-2 pt-2 mb-3" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close pb-2 pt-2" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    @endif

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Code</th>
                        <th>Package Name</th>
                        <th>Destination</th>
                        <th>Duration</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th class="pe-4 text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($packages as $package)
                        <tr>
                            <td class="ps-4">
                                <span class="badge bg-light text-dark border fw-medium">{{ $package->code ?? '--' }}</span>
                            </td>
                            <td class="fw-semibold text-dark">{{ $package->name }}</td>
                            <td><span class="text-muted">{{ $package->destination }}</span></td>
                            <td>{{ $package->duration_nights }} Nights</td>
                            <td>
                                <div class="fw-bold">
                                    {{ $package->currency?->symbol ?? '₹' }}{{ number_format($package->base_price) }}
                                </div>
                            </td>
                            <td>
                                @if($package->is_active)
                                    <span class="badge crm-status-pill bg-success text-white rounded-pill">Active</span>
                                @else
                                    <span class="badge crm-status-pill bg-secondary text-white rounded-pill">Inactive</span>
                                @endif
                            </td>
                            <td class="pe-4 text-end">
                                <div class="btn-group">
                                    <a href="{{ route('packages.itinerary', $package) }}" class="btn btn-sm btn-outline-secondary" title="Build Itinerary">
                                        <i class="bi bi-calendar-event"></i>
                                    </a>
                                    <a href="{{ route('packages.edit', $package) }}" class="btn btn-sm crm-action-btn" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('packages.destroy', $package) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm crm-action-btn" data-confirm-message="Are you sure you want to delete this package?" title="Delete">
                                            <i class="bi bi-trash text-danger"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-box-seam fs-1 d-block mb-3 opacity-50"></i>
                                No tour packages found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="px-4 py-3 border-top">
            {{ $packages->links() }}
        </div>
    </div>
</div>
@endsection
