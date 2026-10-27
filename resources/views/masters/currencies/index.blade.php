@extends('layouts.masters')

@section('page_title', 'Masters - Currencies')

@section('masters_content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h5 mb-0 text-muted fw-semibold">Manage Currencies</h2>
    <a href="{{ route('masters.currencies.create') }}" class="btn btn-dark-blue btn-sm">Add Currency</a>
</div>

@if (session('success'))
    <div class="alert alert-success py-2">{{ session('success') }}</div>
@endif

<div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Symbol</th>
                            <th>Exchange Rate</th>
                            <th>Default</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($currencies as $currency)
                            <tr>
                                <td>{{ $currency->code }}</td>
                                <td>{{ $currency->name }}</td>
                                <td>{{ $currency->symbol }}</td>
                                <td>{{ $currency->exchange_rate }}</td>
                                <td>
                                    @if($currency->is_default)
                                        <span class="badge bg-primary">Default</span>
                                    @endif
                                </td>
                                <td>
                                    @if($currency->is_active)
                                        <span class="badge crm-status-pill rounded-pill bg-success">Active</span>
                                    @else
                                        <span class="badge crm-status-pill rounded-pill bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('masters.currencies.edit', $currency) }}" class="btn btn-sm crm-action-btn">Edit</a>
                                    <form action="{{ route('masters.currencies.destroy', $currency) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm crm-action-btn" data-confirm-message="Delete this currency?">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted small">No currencies added yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
</div>
<div class="mt-3">
    {{ $currencies->links() }}
</div>
@endsection
