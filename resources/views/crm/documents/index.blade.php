@extends('layouts.app')

@section('page_title', 'Customer Documents Repository')

@section('page_actions')
    <button type="button" class="btn btn-dark-blue btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#quickUploadModal">
        <i class="bi bi-cloud-upload me-1"></i> Upload Document
    </button>
@endsection

@section('content')
{{-- Summary KPI Cards --}}
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="avatar bg-primary-subtle text-primary rounded-3 p-3 me-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <i class="bi bi-people-fill fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold">Customers with Docs</div>
                    <div class="fs-4 fw-bold text-dark">{{ number_format($totalCustomersWithDocs ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="avatar bg-success-subtle text-success rounded-3 p-3 me-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <i class="bi bi-files fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold">Total Documents</div>
                    <div class="fs-4 fw-bold text-dark">{{ number_format($totalDocs ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="avatar bg-info-subtle text-info rounded-3 p-3 me-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <i class="bi bi-file-earmark-pdf fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold">Policy PDFs</div>
                    <div class="fs-4 fw-bold text-dark">{{ number_format($totalPolicyDocs ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="avatar bg-warning-subtle text-warning-emphasis rounded-3 p-3 me-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <i class="bi bi-person-badge fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold">KYC & Images</div>
                    <div class="fs-4 fw-bold text-dark">{{ number_format(($totalKycDocs ?? 0) + ($totalImages ?? 0)) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4 overflow-hidden rounded-3">
    <div class="card-header bg-white border-bottom-0 py-3 px-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h5 class="fw-bold mb-1">Customer Documents Repository</h5>
                <p class="text-muted small mb-0">Single entry per customer. Click <strong>View Documents</strong> to view, add, edit, or delete any customer document or image.</p>
            </div>
            <button type="button" class="btn btn-dark-blue btn-sm px-3 rounded-pill" data-bs-toggle="modal" data-bs-target="#quickUploadModal">
                <i class="bi bi-cloud-upload me-1"></i> Upload Document
            </button>
        </div>
    </div>
    
    @if(session('success'))
        <div class="px-4">
            <div class="alert alert-success alert-dismissible fade show pb-2 pt-2 mb-3" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close pb-2 pt-2" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    @endif
    
    @if(session('error'))
        <div class="px-4">
            <div class="alert alert-danger alert-dismissible fade show pb-2 pt-2 mb-3" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close pb-2 pt-2" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    @endif

    {{-- Filters: Customer Dropdown & Search --}}
    <div class="px-4 pt-2 pb-3 border-bottom bg-light bg-opacity-50">
        <form method="GET" action="{{ route('documents.index') }}" class="row g-2 align-items-center">
            <div class="col-md-5">
                <label for="customer_id" class="form-label small fw-semibold text-muted mb-1">Filter by Customer</label>
                <select name="customer_id" id="customer_id" class="form-select form-select-sm select2-customer">
                    <option value="">-- All Customers (Listing Single Entry) --</option>
                    @foreach($allCustomers as $c)
                        <option value="{{ $c->id }}" {{ (string)$selectedCustomerId === (string)$c->id ? 'selected' : '' }}>
                            {{ $c->name }} ({{ $c->phone ?: $c->email ?: 'ID: ' . $c->id }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-5">
                <label for="search" class="form-label small fw-semibold text-muted mb-1">Search Customer or Document Title</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" id="search" class="form-control border-start-0" placeholder="Search by customer name, phone, email, or document..." value="{{ $search }}">
                </div>
            </div>
            <div class="col-md-2 d-flex align-items-end gap-1 mt-auto">
                <button type="submit" class="btn btn-dark-blue btn-sm flex-grow-1">Filter</button>
                @if($selectedCustomerId || $search)
                    <a href="{{ route('documents.index') }}" class="btn btn-outline-secondary btn-sm" title="Clear Filters">
                        <i class="bi bi-x-lg"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Unassigned / General Documents Alert if any exist --}}
    @if(!empty($unassignedDocsCount) && $unassignedDocsCount > 0)
        <div class="mx-4 mt-3 alert alert-secondary d-flex justify-content-between align-items-center py-2 px-3 mb-0 rounded-3">
            <div class="d-flex align-items-center">
                <i class="bi bi-folder-fill fs-5 text-secondary me-2"></i>
                <span class="small fw-semibold text-dark">There are <strong>{{ $unassignedDocsCount }}</strong> unassigned / general documents not linked to any specific customer.</span>
            </div>
            <a href="{{ route('documents.customer', 'unassigned') }}" class="btn btn-sm btn-outline-dark rounded-pill px-3 py-1 small">
                <i class="bi bi-folder2-open me-1"></i> View Unassigned Docs
            </a>
        </div>
    @endif

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
                        <tr>
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
                                        <i class="bi bi-file-earmark-text me-1"></i> {{ $customer->documents_count }} {{ Str::plural('Document', $customer->documents_count) }}
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
</div>

{{-- Quick Upload Document / Image Modal --}}
<div class="modal fade" id="quickUploadModal" tabindex="-1" aria-labelledby="quickUploadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('documents.store') }}" method="POST" enctype="multipart/form-data" id="quickUploadForm">
                @csrf
                <div class="modal-header border-bottom py-3 px-4 bg-light">
                    <h5 class="modal-title fw-bold text-dark" id="quickUploadModalLabel">
                        <i class="bi bi-cloud-arrow-up me-2 text-primary"></i>Upload Customer Document / Image
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Customer / Client <span class="text-danger">*</span></label>
                        <select name="customer_id" id="modal_customer_id" class="form-select select2-modal" required>
                            <option value="">-- Select Customer --</option>
                            @foreach($allCustomers as $c)
                                <option value="{{ $c->id }}">
                                    {{ $c->name }} ({{ $c->phone ?: $c->email ?: 'ID: ' . $c->id }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Document Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="modal_title" class="form-control" placeholder="E.g., PAN Card, Aadhaar, Policy Copy, RC Book" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Choose File or Image <span class="text-danger">*</span></label>
                        <input type="file" name="file" id="modal_file" class="form-control" accept=".pdf,.png,.jpg,.jpeg,.gif,.webp,.doc,.docx" required>
                        <div class="form-text small text-muted">
                            Supported: PDF, Images (JPG, PNG, WEBP), Word Documents. Max 25MB.
                        </div>
                    </div>

                    {{-- Image Live Preview Box --}}
                    <div id="modal_image_preview_wrapper" class="mb-3 p-2 bg-light border rounded text-center" style="display:none;">
                        <small class="text-muted d-block mb-1">Image Preview</small>
                        <img id="modal_image_preview" src="" alt="Preview" style="max-height: 140px; max-width: 100%; object-fit: contain; border-radius: 6px;">
                    </div>
                </div>
                <div class="modal-footer border-top py-2 px-4 bg-light">
                    <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark-blue btn-sm px-4" id="modal_submit_btn">
                        <i class="bi bi-cloud-arrow-up me-1"></i> Upload
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    if ($.fn.select2) {
        $('.select2-customer').select2({
            width: '100%',
            placeholder: '-- All Customers (Listing Single Entry) --',
            allowClear: true
        });
        $('.select2-modal').select2({
            dropdownParent: $('#quickUploadModal'),
            width: '100%',
            placeholder: '-- Select Customer --'
        });
    }

    // Row quick upload button: prefill customer in modal
    $(document).on('click', '.btn-row-quick-upload', function() {
        const custId = $(this).data('customer-id');
        const custName = $(this).data('customer-name');
        $('#modal_customer_id').val(custId).trigger('change');
        $('#quickUploadModalLabel').html('<i class="bi bi-cloud-arrow-up me-2 text-primary"></i>Upload Document for ' + custName);
        $('#quickUploadModal').modal('show');
    });

    // Reset modal title when closed
    $('#quickUploadModal').on('hidden.bs.modal', function() {
        $('#quickUploadModalLabel').html('<i class="bi bi-cloud-arrow-up me-2 text-primary"></i>Upload Customer Document / Image');
        $('#modal_image_preview_wrapper').hide();
        $('#modal_image_preview').attr('src', '');
        $('#quickUploadForm')[0].reset();
        if ($.fn.select2) {
            $('#modal_customer_id').val('').trigger('change');
        }
    });

    // Live preview for image selection in modal
    $('#modal_file').on('change', function() {
        const file = this.files[0];
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#modal_image_preview').attr('src', e.target.result);
                $('#modal_image_preview_wrapper').slideDown();
            };
            reader.readAsDataURL(file);
        } else {
            $('#modal_image_preview_wrapper').slideUp();
        }
    });
});
</script>
@endpush
