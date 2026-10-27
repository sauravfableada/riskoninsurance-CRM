@extends('layouts.app')

@section('page_title', 'Quotations')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-1">Quotations</h1>
            <p class="text-muted small mb-0">Track quotation, estimate, and confirmed tour statuses.</p>
        </div>
        <a href="{{ route('quotations.create') }}" class="btn btn-dark-blue btn-sm">
            + New Quotation
        </a>
    </div>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <div class="d-flex gap-2">
                <span class="badge rounded-pill bg-secondary">Quotation</span>
                <span class="badge rounded-pill bg-info text-dark">Estimate</span>
                <span class="badge rounded-pill bg-success">Confirmed</span>
                <span class="badge rounded-pill bg-danger">Cancelled</span>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive" style="min-height: 250px;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Reference</th>
                            <th>Lead</th>
                            <th>Package</th>
                            <th>Status</th>
                            <th>Total Amount</th>
                            <th>Valid Until</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($quotations as $quotation)
                            <tr>
                                <td class="fw-semibold">{{ $quotation->reference }}</td>
                                <td>
                                    @if($quotation->lead)
                                        <a href="{{ route('leads.show', $quotation->lead) }}" class="text-decoration-none fw-medium d-flex align-items-center gap-1">
                                            {{ $quotation->lead->name }}
                                            <i class="bi bi-box-arrow-up-right small opacity-50"></i>
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $quotation->tourPackage?->name ?? '--' }}</td>
                                <td>
                                    @php
                                        $badge = match($quotation->status) {
                                            'estimate' => 'info',
                                            'confirmed' => 'success',
                                            'cancelled' => 'danger',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <span class="badge crm-status-pill rounded-pill bg-{{ $badge }}">{{ ucfirst($quotation->status) }}</span>
                                </td>
                                <td>
                                    {{ number_format((float)$quotation->total_amount, 2) }}
                                </td>
                                <td>
                                    {{ $quotation->valid_until ? \Illuminate\Support\Carbon::parse($quotation->valid_until)->format('d M Y') : '--' }}
                                </td>
                                <td class="text-end">
                                    <div class="dropdown d-inline-block">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            Actions
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="{{ route('quotations.show', $quotation) }}">Preview</a></li>
                                            <li><a class="dropdown-item" href="{{ route('quotations.edit', $quotation) }}">Edit</a></li>
                                            
                                            @if($quotation->lead && !$quotation->lead->converted_customer_id)
                                            <li>
                                                <form action="{{ route('leads.convert', $quotation->lead) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item text-primary"><i class="bi bi-person-plus me-2"></i>Convert Lead to Customer</button>
                                                </form>
                                            </li>
                                            @endif

                                            @if($quotation->status === 'confirmed')
                                            <li>
                                                <form action="{{ route('quotations.convert', $quotation) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item text-success" data-confirm-message="Convert this quotation into a Booking? This will copy the data and attached itinerary.">Convert to Booking</button>
                                                </form>
                                            </li>
                                            @endif
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form action="{{ route('quotations.destroy', $quotation) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger" data-confirm-message="Delete this quotation?">Delete</button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted small">
                                    No quotations added yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $quotations->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
