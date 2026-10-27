@extends('layouts.masters')

@section('page_title', 'Masters - Suppliers')

@section('masters_content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h5 mb-0 text-muted fw-semibold">Manage Suppliers</h2>
    <a href="{{ route('masters.suppliers.create') }}" class="btn btn-dark-blue btn-sm">Add Supplier</a>
</div>

@if (session('success'))
    <div class="alert alert-success py-2">{{ session('success') }}</div>
@endif

<div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Location</th>
                            <th>Contact</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($suppliers as $supplier)
                            <tr>
                                <td>{{ $supplier->name }}</td>
                                <td>{{ $supplier->type }}</td>
                                <td>{{ $supplier->city?->name ?? '--' }}, {{ $supplier->country?->name ?? '--' }}</td>
                                <td>{{ $supplier->contact_person }}</td>
                                <td>{{ $supplier->email }}</td>
                                <td>{{ $supplier->phone }}</td>
                                <td>
                                    @if($supplier->is_active)
                                        <span class="badge crm-status-pill rounded-pill bg-success">Active</span>
                                    @else
                                        <span class="badge crm-status-pill rounded-pill bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('suppliers.payables', $supplier) }}" class="btn btn-sm btn-outline-dark-blue">Payables</a>
                                    <a href="{{ route('masters.suppliers.edit', $supplier) }}" class="btn btn-sm crm-action-btn">Edit</a>
                                    <form action="{{ route('masters.suppliers.destroy', $supplier) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm crm-action-btn" data-confirm-message="Delete this supplier?">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted small">No suppliers added yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
</div>
<div class="mt-3">
    {{ $suppliers->links() }}
</div>
@endsection
