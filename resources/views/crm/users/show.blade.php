@extends('layouts.app')

@section('page_title', 'Sub Agent Details')

@push('styles')
    <link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/users-show.css' : '/css/users-show.css') }}?v={{ filemtime(public_path('css/users-show.css')) }}">
@endpush

@section('content')
    <div class="container-fluid p-0">
        <div class="row g-4">
            <div class="col-lg-12">
                <div class="card shadow-sm border-0 rounded-4 overflow-hidden detail-view-card">
                    <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                            <div class="flex-grow-1 w-100">
                                <h1 class="h4 mb-1 fw-semibold">Sub Agent Details</h1>
                                <p class="text-muted small mb-0">Complete information about this sub agent</p>
                            </div>
                            <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-lg-end justify-content-md-end">
                                @if(auth()->user()?->isMainAdmin())
                                    <a href="{{ route('users.edit', $user) }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                                        <i class="bi bi-pencil me-1"></i>Edit
                                    </a>
                                @endif
                                <a href="{{ route('users.index') }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                                    <i class="fa-solid fa-angle-left pe-1"></i>
                                    <span>Back</span>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-3 p-md-4">
                        <div class="detail-view-block">
                            <div class="row g-4 align-items-start">

                                <!-- Responsive Profile Image Section -->
                                <div class="col-md-4 col-lg-3 text-center text-md-start">
                                    {{-- <div class="staff-profile-wrap mx-auto mx-md-0"> --}}
                                        <div class="staff-profile-media">
                                            @php
                                                $hasValidAvatar = $user->avatar_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->avatar_path);
                                            @endphp

                                            @if($hasValidAvatar)
                                                <img src="{{ route('users.image', $user) }}?v={{ optional($user->updated_at)->timestamp }}"
                                                     alt="{{ $user->name }}"
                                                     class="staff-profile-img img-fluid shadow-sm"
                                                     loading="lazy">
                                            @else
                                                <span class="staff-profile-placeholder d-flex align-items-center justify-content-center">
                                                    <i class="bi bi-person-circle"></i>
                                                </span>
                                            @endif
                                        </div>
                                    {{-- </div> --}}
                                </div>

                                <!-- Sub Agent Information -->
                                <div class="col-md-8 col-lg-9">
                                    <div class="detail-view-title mb-3">{{ $user->name ?? '--' }}</div>

                                    <div class="row g-0 detail-view-grid">

                                        <!-- Created Info -->
                                        <div class="col-md-6 detail-view-row">
                                            <span class="detail-view-label pe-2"><i class="fas fa-user-tie text-muted me-2"></i>Created By:</span>
                                            <span class="detail-view-value">Admin</span>
                                        </div>
                                        <div class="col-md-6 detail-view-row">
                                            <span class="detail-view-label pe-2"><i class="fas fa-calendar-plus text-muted me-2"></i>Created At:</span>
                                            <span class="detail-view-value">
                                                {{ $user->created_at?->format('d M, Y h:i A') ?? '--' }}
                                            </span>
                                        </div>

                                        <!-- Email -->
                                        <div class="col-md-6 detail-view-row">
                                            <span class="detail-view-label pe-2"><i class="fas fa-envelope text-muted me-2"></i>Email:</span>
                                            <span class="detail-view-value">
                                                @if($user->email)
                                                    <a href="mailto:{{ $user->email }}" class="text-decoration-none link-hover">
                                                        {{ $user->email }}
                                                    </a>
                                                @else
                                                    --
                                                @endif
                                            </span>
                                        </div>

                                        <!-- Phone -->
                                        <div class="col-md-6 detail-view-row">
                                            <span class="detail-view-label pe-2"><i class="fas fa-phone-alt text-muted me-2"></i>Phone Number:</span>
                                            <span class="detail-view-value">
                                                @if($user->phone)
                                                    <a href="tel:{{ $user->phone }}" class="text-decoration-none link-hover">
                                                        {{ $user->phone }}
                                                    </a>
                                                @else
                                                    --
                                                @endif
                                            </span>
                                        </div>

                                        <!-- WhatsApp -->
                                        <div class="col-md-6 detail-view-row">
                                            <span class="detail-view-label pe-2"><i class="fab fa-whatsapp text-muted me-2"></i>WhatsApp Number:</span>
                                            <span class="detail-view-value">
                                                @if($user->whatsapp)
                                                    <a href="https://wa.me/{{ $user->whatsapp }}" 
                                                       target="_blank" 
                                                       class="text-decoration-none link-hover">
                                                        {{ $user->whatsapp }}
                                                    </a>
                                                @else
                                                    --
                                                @endif
                                            </span>
                                        </div>

                                        <!-- Role -->
                                        <div class="col-md-6 detail-view-row">
                                            <span class="detail-view-label pe-2"><i class="fas fa-user-tag text-muted me-2"></i>Role:</span>
                                            <span class="detail-view-value">
                                                {{ $user->roles->pluck('name')->map(fn($role) => ucfirst($role))->implode(', ') ?: 'Sub Agent' }}
                                            </span>
                                        </div>

                                        <!-- Address -->
                                        <div class="col-md-6 detail-view-row">
                                            <span class="detail-view-label pe-2"><i class="fas fa-map-marker-alt text-muted me-2"></i>Address:</span>
                                            <span class="detail-view-value">{{ $user->address ?: '--' }}</span>
                                        </div>

                                        <!-- Status -->
                                        <div class="col-md-6 detail-view-row">
                                            <span class="detail-view-label pe-2"><i class="fas fa-toggle-on text-muted me-2"></i>Status:</span>
                                            <span class="detail-view-value">
                                                <span class="badge {{ ($user->is_active ?? true) ? 'bg-success' : 'bg-secondary' }} rounded-pill px-3">
                                                    {{ ($user->is_active ?? true) ? 'Active' : 'Inactive' }}
                                                </span>
                                            </span>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Commission Financial Metrics -->
                        <div class="mt-5 pt-3 border-top">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h5 class="fw-bold mb-1"><i class="fa-solid fa-wallet text-primary me-2"></i>Commission Overview</h5>
                                    <p class="text-muted small mb-0">Earnings and withdrawal summary for this sub agent</p>
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-sm-6 col-lg-3">
                                    <div class="card border rounded-3 p-3 bg-light h-100 shadow-none">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                                <i class="fa-solid fa-indian-rupee-sign fs-5"></i>
                                            </div>
                                            <div>
                                                <div class="text-muted small">Total Commission Earned</div>
                                                <div class="h5 fw-bold mb-0">₹{{ number_format($totalEarned ?? 0, 2) }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-3">
                                    <div class="card border rounded-3 p-3 bg-light h-100 shadow-none">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="rounded-circle bg-success bg-opacity-10 text-success p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                                <i class="fa-solid fa-circle-check fs-5"></i>
                                            </div>
                                            <div>
                                                <div class="text-muted small">Available Balance</div>
                                                <div class="h5 fw-bold mb-0 text-success">₹{{ number_format($availableBalance ?? 0, 2) }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-3">
                                    <div class="card border rounded-3 p-3 bg-light h-100 shadow-none">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="rounded-circle bg-warning bg-opacity-10 text-warning p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                                <i class="fa-solid fa-clock-rotate-left fs-5"></i>
                                            </div>
                                            <div>
                                                <div class="text-muted small">Pending Payouts</div>
                                                <div class="h5 fw-bold mb-0 text-warning">₹{{ number_format($pendingWithdrawalAmount ?? 0, 2) }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-3">
                                    <div class="card border rounded-3 p-3 bg-light h-100 shadow-none">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="rounded-circle bg-info bg-opacity-10 text-info p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                                <i class="fa-solid fa-money-bill-transfer fs-5"></i>
                                            </div>
                                            <div>
                                                <div class="text-muted small">Total Withdrawn / Paid</div>
                                                <div class="h5 fw-bold mb-0">₹{{ number_format($totalWithdrawnApproved ?? 0, 2) }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Commission Entries List -->
                            <div class="card border rounded-3 overflow-hidden shadow-none">
                                <div class="card-header bg-white py-3 px-3 d-flex justify-content-between align-items-center">
                                    <h6 class="fw-bold mb-0"><i class="fa-solid fa-list-check me-2 text-muted"></i>Commission Entries</h6>
                                    <span class="badge bg-secondary rounded-pill">{{ count($commissionEntries ?? []) }} entries</span>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="ps-3" style="width: 60px;">#</th>
                                                <th>Policy Number</th>
                                                <th>Premium (₹)</th>
                                                <th>Commission (₹)</th>
                                                <th>Commission %</th>
                                                <th>Payment Status</th>
                                                <th>Entry Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($commissionEntries ?? [] as $idx => $entry)
                                                <tr>
                                                    <td class="ps-3 text-muted">{{ $idx + 1 }}</td>
                                                    <td class="fw-semibold">
                                                        {{ $entry->policy?->policy_number_no ?? $entry->policy?->policy_number ?? ('Policy #' . $entry->policy_id) }}
                                                    </td>
                                                    <td>₹{{ number_format($entry->premium_amount ?? 0, 2) }}</td>
                                                    <td class="fw-bold text-dark">₹{{ number_format($entry->commission_amount ?? 0, 2) }}</td>
                                                    <td>
                                                        {{ $entry->commission_percentage !== null ? number_format($entry->commission_percentage, 2) . '%' : 'Fixed' }}
                                                    </td>
                                                    <td>
                                                        @if($entry->payment_status === 'paid')
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1">Paid</span>
                                                        @elseif($entry->payment_status === 'pending')
                                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-2.5 py-1">Pending</span>
                                                        @else
                                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-2.5 py-1">{{ ucfirst($entry->payment_status ?? 'Pending') }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-muted small">
                                                        {{ $entry->entry_date ? \Carbon\Carbon::parse($entry->entry_date)->format('d M, Y') : '--' }}
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="7" class="text-center py-4 text-muted">
                                                        <i class="fa-solid fa-receipt fs-3 d-block mb-2 text-secondary opacity-50"></i>
                                                        No commission entries found for this sub agent.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
