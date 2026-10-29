<div class="card-body p-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="documentsTable">
            <thead class="bg-light">
                <tr>
                    <th class="ps-4" style="width: 60px;">#</th>
                    <th>Customer Details</th>
                    <th>Total Documents</th>
                    <th>Latest Document Uploaded</th>
                    <th class="pe-4 text-end" style="width: 220px;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($customers as $index => $customer)
                    @php
                        $latestDoc = $customer->documents->first();
                        $policyCount = $customer->policy_docs_count ?? 0;
                        $kycCount = $customer->kyc_docs_count ?? 0;
                        $imageCount = $customer->image_docs_count ?? 0;
                    @endphp
                    <tr class="customer-row" 
                        data-customer-id="{{ $customer->id }}" 
                        data-search-text="{{ strtolower($customer->name . ' ' . $customer->phone . ' ' . $customer->email . ' ' . ($latestDoc ? $latestDoc->title : '')) }}">
                        <td class="ps-4 text-muted fw-semibold">
                            {{ ($customers->currentPage() - 1) * $customers->perPage() + $index + 1 }}
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-md bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width: 42px; height: 42px; font-weight: 700; font-size: 16px;">
                                    {{ strtoupper(substr($customer->name, 0, 1)) }}
                                </div>
                                <div>
                                    <a href="{{ route('documents.customer', $customer->id) }}" class="fw-bold text-dark text-decoration-none hover-primary fs-6">
                                        {{ $customer->name }}
                                    </a>
                                    <div class="small text-muted d-flex align-items-center gap-2 mt-1">
                                        @if($customer->phone)
                                            <span><i class="bi bi-telephone me-1"></i>{{ $customer->phone }}</span>
                                        @endif
                                        @if($customer->email)
                                            <span><i class="bi bi-envelope me-1"></i>{{ $customer->email }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex flex-column align-items-start gap-1">
                                <span class="badge bg-primary px-3 py-2 rounded-pill fs-7 fw-semibold">
                                    <i class="bi bi-file-earmark-text me-1"></i> {{ (int)($customer->documents_count ?? 0) }} {{ Str::plural('Document', (int)($customer->documents_count ?? 0)) }}
                                </span>
                                <div class="d-flex flex-wrap gap-1 mt-1">
                                    @if($policyCount > 0)
                                        <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle" style="font-size: 11px;">
                                            <i class="bi bi-shield-check me-1"></i>{{ $policyCount }} Policy
                                        </span>
                                    @endif
                                    @if($kycCount > 0)
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" style="font-size: 11px;">
                                            <i class="bi bi-person-vcard me-1"></i>{{ $kycCount }} KYC
                                        </span>
                                    @endif
                                    @if($imageCount > 0)
                                        <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle" style="font-size: 11px;">
                                            <i class="bi bi-image me-1"></i>{{ $imageCount }} Images
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($latestDoc)
                                @php
                                    $isImg = $latestDoc->isImage();
                                    $isPdf = $latestDoc->isPdf();
                                    $iconClass = $isPdf ? 'bi-file-earmark-pdf text-danger' : ($isImg ? 'bi-file-earmark-image text-success' : 'bi-file-earmark-text text-primary');
                                @endphp
                                <div class="d-flex align-items-center">
                                    <i class="bi {{ $iconClass }} fs-4 me-2"></i>
                                    <div style="max-width: 320px;">
                                        <div class="fw-semibold text-truncate text-dark" title="{{ $latestDoc->title }}">
                                            {{ $latestDoc->title }}
                                        </div>
                                        <div class="small text-muted">
                                            <span>{{ $latestDoc->created_at ? $latestDoc->created_at->format('M d, Y') : '--' }}</span>
                                            @if($latestDoc->user)
                                                <span> • by {{ $latestDoc->user->name }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @else
                                <span class="text-muted small">No recent upload</span>
                            @endif
                        </td>
                        <td class="pe-4 text-end text-nowrap">
                            <a href="{{ route('documents.customer', $customer->id) }}" class="btn btn-sm btn-dark-blue rounded-pill px-3 me-1" title="View all documents for {{ $customer->name }}">
                                <i class="bi bi-eye me-1"></i> View Documents
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle btn-row-quick-upload" 
                                data-customer-id="{{ $customer->id }}" 
                                data-customer-name="{{ $customer->name }}" 
                                title="Quick Upload Document for {{ $customer->name }}">
                                <i class="bi bi-plus-lg"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="bi bi-folder2-open fs-1 d-block mb-3 opacity-50"></i>
                            <h6 class="fw-semibold text-dark">No customer documents found</h6>
                            <p class="small text-muted mb-3">No documents match the selected filters. Upload new documents or clear filters.</p>
                            <button type="button" class="btn btn-dark-blue btn-sm rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#quickUploadModal">
                                <i class="bi bi-cloud-upload me-1"></i> Upload First Document
                            </button>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($customers->hasPages())
    <div class="card-footer bg-white border-top-0 py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="text-muted small">
            Showing <strong>{{ $customers->firstItem() ?? 0 }}</strong> to <strong>{{ $customers->lastItem() ?? 0 }}</strong> of <strong>{{ $customers->total() }}</strong> customers
        </div>
        <div>
            {{ $customers->links() }}
        </div>
    </div>
@endif
