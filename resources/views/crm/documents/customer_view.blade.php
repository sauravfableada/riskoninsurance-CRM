@extends('layouts.app')

@php
    $custName = $customer ? $customer->name : 'Unassigned / General Documents';
@endphp

@section('page_title', $custName . ' - Documents')

@section('page_actions')
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('documents.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Back to Listing
        </a>
        <button type="button" class="btn btn-dark-blue btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#uploadDocModal">
            <i class="bi bi-cloud-upload me-1"></i> Upload Document / Image
        </button>
    </div>
@endsection

@section('content')
{{-- Customer Profile Banner --}}
<div class="card border-0 shadow-sm mb-4 rounded-3 overflow-hidden">
    <div class="card-body p-4 bg-white">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div class="d-flex align-items-center">
                <div class="avatar avatar-lg bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width: 56px; height: 56px; font-weight: 700; font-size: 22px;">
                    {{ strtoupper(substr($custName, 0, 1)) }}
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h4 class="fw-bold mb-0 text-dark">{{ $custName }}</h4>
                        @if($customer && $customer->type)
                            <span class="badge bg-secondary-subtle text-secondary border rounded-pill px-2 py-1 small">{{ ucfirst($customer->type) }}</span>
                        @endif
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-3 text-muted small mt-2">
                        @if($customer?->phone)
                            <span><i class="bi bi-telephone text-primary me-1"></i><a href="tel:{{ $customer->phone }}" class="text-muted text-decoration-none">{{ $customer->phone }}</a></span>
                        @endif
                        @if($customer?->email)
                            <span><i class="bi bi-envelope text-primary me-1"></i><a href="mailto:{{ $customer->email }}" class="text-muted text-decoration-none">{{ $customer->email }}</a></span>
                        @endif
                        @if($customer?->address)
                            <span class="text-truncate" style="max-width: 320px;" title="{{ $customer->address }}"><i class="bi bi-geo-alt text-primary me-1"></i>{{ $customer->address }}</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                @if($customer)
                    <a href="{{ url('masters/customers/' . $customer->id) }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3" title="View Customer in CRM">
                        <i class="bi bi-person me-1"></i> View Profile
                    </a>
                @endif
                <button type="button" class="btn btn-dark-blue btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#uploadDocModal">
                    <i class="bi bi-plus-lg me-1"></i> Upload Document / Image
                </button>
            </div>
        </div>
    </div>
</div>

{{-- KPI Counters for this Customer --}}
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 kpi-filter-card cursor-pointer" data-filter="all">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="avatar bg-primary-subtle text-primary rounded-3 p-3 me-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <i class="bi bi-files fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold">Total Documents</div>
                    <div class="fs-4 fw-bold text-dark" id="kpi-total">{{ $totalDocs }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 kpi-filter-card cursor-pointer" data-filter="policy">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="avatar bg-info-subtle text-info rounded-3 p-3 me-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <i class="bi bi-shield-check fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold">Policy PDFs</div>
                    <div class="fs-4 fw-bold text-dark" id="kpi-policy">{{ $policyDocs }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 kpi-filter-card cursor-pointer" data-filter="kyc">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="avatar bg-warning-subtle text-warning-emphasis rounded-3 p-3 me-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <i class="bi bi-person-vcard fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold">KYC Documents</div>
                    <div class="fs-4 fw-bold text-dark" id="kpi-kyc">{{ $kycDocs }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 kpi-filter-card cursor-pointer" data-filter="image">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="avatar bg-success-subtle text-success rounded-3 p-3 me-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <i class="bi bi-images fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold">Images & Photos</div>
                    <div class="fs-4 fw-bold text-dark" id="kpi-image">{{ $imageDocs }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Main Documents Repository Panel --}}
<div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-4">
    <div class="card-header bg-white border-bottom py-3 px-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            {{-- Tabs --}}
            <ul class="nav nav-pills gap-1" id="docCategoryTabs">
                <li class="nav-item">
                    <button class="nav-link active rounded-pill px-3 py-1 small fw-semibold tab-btn" data-filter="all">
                        All <span class="badge bg-light text-dark ms-1">{{ $totalDocs }}</span>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link rounded-pill px-3 py-1 small fw-semibold tab-btn" data-filter="policy">
                        Policy PDFs <span class="badge bg-light text-dark ms-1">{{ $policyDocs }}</span>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link rounded-pill px-3 py-1 small fw-semibold tab-btn" data-filter="kyc">
                        KYC Proofs <span class="badge bg-light text-dark ms-1">{{ $kycDocs }}</span>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link rounded-pill px-3 py-1 small fw-semibold tab-btn" data-filter="image">
                        Images & Photos <span class="badge bg-light text-dark ms-1">{{ $imageDocs }}</span>
                    </button>
                </li>
            </ul>

            {{-- Instant Search inside customer documents --}}
            <div class="input-group input-group-sm" style="max-width: 280px;">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                <input type="text" id="docSearchInput" class="form-control border-start-0 bg-light" placeholder="Search in this customer's docs...">
            </div>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="customerDocsTable">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4" style="width: 50px;">#</th>
                        <th style="width: 90px;">Preview</th>
                        <th>Document Title & Details</th>
                        <th>Category</th>
                        <th>File Format</th>
                        <th>Uploaded Info</th>
                        <th class="pe-4 text-end" style="width: 220px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="docTableBody">
                    @forelse ($documents as $index => $doc)
                        @php
                            $isImg = $doc->isImage();
                            $isPdf = $doc->isPdf();
                            $isKyc = (bool)$doc->is_kyc;
                            $isPolicy = !$isKyc && (
                                str_contains($doc->documentable_type ?? '', 'Policy')
                                || str_contains($doc->meta ?? '', 'policy')
                                || str_starts_with($doc->file_path, 'policies/')
                            );
                            
                            $categoryFilter = 'other';
                            if ($isKyc) {
                                $categoryFilter = 'kyc';
                            } elseif ($isPolicy) {
                                $categoryFilter = 'policy';
                            } elseif ($isImg) {
                                $categoryFilter = 'image';
                            }

                            $badge = $doc->category_badge;
                            $fileUrl = route('documents.view', $doc->id);
                        @endphp
                        <tr class="doc-row" 
                            data-category="{{ $categoryFilter }}" 
                            data-is-kyc="{{ $isKyc ? '1' : '0' }}" 
                            data-is-policy="{{ $isPolicy ? '1' : '0' }}" 
                            data-is-image="{{ $isImg ? '1' : '0' }}" 
                            data-title="{{ strtolower($doc->title . ' ' . basename($doc->file_path)) }}" 
                            id="doc-row-{{ $doc->id }}">
                            <td class="ps-4 text-muted fw-semibold">{{ $index + 1 }}</td>
                            
                            {{-- Visual Preview Thumbnail --}}
                            <td>
                                @if($isImg)
                                    <div class="position-relative d-inline-block rounded overflow-hidden border shadow-sm cursor-pointer img-thumbnail-trigger" 
                                         data-img-url="{{ $fileUrl }}" 
                                         data-img-title="{{ $doc->title }}"
                                         title="Click to view image preview"
                                         style="width: 54px; height: 54px; background: #f8fafc;">
                                        <img src="{{ $fileUrl }}" alt="Thumb" class="w-100 h-100" style="object-fit: cover;" onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'w-100 h-100 d-flex align-items-center justify-content-center bg-light text-success\'><i class=\'bi bi-file-earmark-image fs-4\'></i></div>';">
                                        <div class="position-absolute bottom-0 end-0 bg-dark bg-opacity-75 text-white p-1" style="font-size: 9px; line-height: 1;">
                                            <i class="bi bi-arrows-fullscreen"></i>
                                        </div>
                                    </div>
                                @elseif($isPdf)
                                    <a href="{{ $fileUrl }}" target="_blank" class="rounded p-2 bg-danger-subtle text-danger d-inline-flex align-items-center justify-content-center text-decoration-none border border-danger-subtle shadow-sm" style="width: 54px; height: 54px;" title="View PDF">
                                        <i class="bi bi-file-earmark-pdf fs-3"></i>
                                    </a>
                                @else
                                    <div class="rounded p-2 bg-secondary-subtle text-secondary d-inline-flex align-items-center justify-content-center border shadow-sm" style="width: 54px; height: 54px;">
                                        <i class="bi bi-file-earmark-text fs-3"></i>
                                    </div>
                                @endif
                            </td>

                            {{-- Title & Metadata --}}
                            <td>
                                <div>
                                    <div class="fw-bold text-dark mb-1 doc-title-text" style="font-size: 14.5px;">
                                        {{ $doc->title }}
                                    </div>
                                    <div class="small text-muted d-flex align-items-center gap-2 flex-wrap">
                                        <span><i class="bi bi-file-text me-1"></i>{{ basename($doc->file_path) }}</span>
                                        @if(!empty($doc->meta_array['policy_number']))
                                            <span class="badge bg-light text-dark border">
                                                <i class="bi bi-shield-check me-1 text-primary"></i>Policy: {{ $doc->meta_array['policy_number'] }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            {{-- Category Badge --}}
                            <td>
                                <span class="badge {{ $badge['class'] }} px-2 py-1 border small fw-semibold">
                                    {{ $badge['label'] }}
                                </span>
                            </td>

                            {{-- File Format --}}
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary border px-2 py-1 font-monospace" style="font-size: 11px;">
                                    {{ strtoupper($doc->file_type ?: 'FILE') }}
                                </span>
                            </td>

                            {{-- Uploaded Info --}}
                            <td>
                                <div class="small text-dark fw-semibold">
                                    {{ $doc->created_at ? $doc->created_at->format('M d, Y') : '--' }}
                                </div>
                                <div class="small text-muted">
                                    {{ $doc->user ? $doc->user->name : 'System' }}
                                    @if($doc->created_at)
                                        <span style="font-size: 11px;">({{ $doc->created_at->format('h:i A') }})</span>
                                    @endif
                                </div>
                            </td>

                            {{-- Actions (View, Download, Edit, Delete) --}}
                            <td class="pe-4 text-end text-nowrap">
                                <div class="d-inline-flex align-items-center gap-1 justify-content-end">
                                    @if($isImg)
                                        <button type="button" class="btn btn-sm btn-light border rounded-circle img-thumbnail-trigger" 
                                            data-img-url="{{ $fileUrl }}" 
                                            data-img-title="{{ $doc->title }}" 
                                            title="View Image Fullscreen">
                                            <i class="bi bi-eye text-primary"></i>
                                        </button>
                                    @else
                                        <a href="{{ $fileUrl }}" target="_blank" class="btn btn-sm btn-light border rounded-circle" title="View Document">
                                            <i class="bi bi-eye text-primary"></i>
                                        </a>
                                    @endif

                                    <a href="{{ route('documents.download', $doc->id) }}" class="btn btn-sm btn-light border rounded-circle" title="Download">
                                        <i class="bi bi-download text-success"></i>
                                    </a>

                                    <button type="button" class="btn btn-sm btn-light border rounded-circle btn-edit-doc" 
                                        data-id="{{ $doc->id }}" 
                                        data-title="{{ $doc->title }}" 
                                        data-category="{{ $doc->meta_array['category'] ?? '' }}"
                                        data-file-type="{{ strtoupper($doc->file_type) }}"
                                        data-file-name="{{ basename($doc->file_path) }}"
                                        data-is-img="{{ $isImg ? '1' : '0' }}"
                                        data-img-url="{{ $fileUrl }}"
                                        title="Edit Document / Replace File">
                                        <i class="bi bi-pencil-square text-secondary"></i>
                                    </button>

                                    <button type="button" class="btn btn-sm btn-light border rounded-circle btn-delete-doc" 
                                        data-id="{{ $doc->id }}" 
                                        data-title="{{ $doc->title }}" 
                                        title="Delete Document">
                                        <i class="bi bi-trash text-danger"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr id="empty-placeholder">
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-folder2-open fs-1 d-block mb-3 opacity-50"></i>
                                <h6 class="fw-semibold text-dark">No documents found for this customer</h6>
                                <p class="small text-muted mb-3">Upload KYC proofs, Policy schedules, images, or signature for this customer.</p>
                                <button type="button" class="btn btn-dark-blue btn-sm rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#uploadDocModal">
                                    <i class="bi bi-cloud-upload me-1"></i> Upload First Document / Image
                                </button>
                            </td>
                        </tr>
                    @endforelse
                    <tr id="empty-filter-placeholder" style="display: none;">
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-funnel fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            <h6 class="fw-semibold text-dark">No documents found in this tab</h6>
                            <p class="small text-muted mb-0">No documents match the active filter or search keywords.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Upload Document / Image Modal --}}
<div class="modal fade" id="uploadDocModal" tabindex="-1" aria-labelledby="uploadDocModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('documents.customer.upload', $customer ? $customer->id : 'unassigned') }}" method="POST" enctype="multipart/form-data" id="customerUploadForm">
                @csrf
                <div class="modal-header border-bottom py-3 px-4 bg-light">
                    <h5 class="modal-title fw-bold text-dark" id="uploadDocModalLabel">
                        <i class="bi bi-cloud-arrow-up me-2 text-primary"></i>Upload Document or Image
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Document Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="upload_title" class="form-control" placeholder="E.g., Aadhaar Card Front, Pan Card, Renewed Policy" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Category / Document Type</label>
                        <select name="category" id="upload_category" class="form-select">
                            <option value="policy_pdf">Policy PDF / Schedule</option>
                            <option value="kyc_aadhaar">KYC - Aadhaar Card</option>
                            <option value="kyc_pan">KYC - PAN Card</option>
                            <option value="kyc_driving_licence">KYC - Driving Licence</option>
                            <option value="kyc_address">KYC - Address Proof</option>
                            <option value="kyc_photo">Customer Photo</option>
                            <option value="kyc_signature">Signature</option>
                            <option value="vehicle_rc">Vehicle RC / Fitness</option>
                            <option value="medical_doc">Medical / Health Report</option>
                            <option value="general" selected>General Document / Image</option>
                        </select>
                    </div>

                    @if(!empty($policies) && $policies->count() > 0)
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Link to Policy (Optional)</label>
                            <select name="policy_id" id="upload_policy_id" class="form-select">
                                <option value="">-- None (Customer Level Document) --</option>
                                @foreach($policies as $pol)
                                    <option value="{{ $pol->id }}">
                                        {{ $pol->policy_number ?: ('#' . $pol->id) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Choose File or Image <span class="text-danger">*</span></label>
                        <input type="file" name="file" id="upload_file" class="form-control" accept=".pdf,.png,.jpg,.jpeg,.gif,.webp,.doc,.docx" required>
                        <div class="form-text small text-muted">
                            Supported: PDF, Images (JPG, PNG, WEBP), Word Docs. Max 25MB.
                        </div>
                    </div>

                    {{-- Live Image Preview --}}
                    <div id="upload_image_preview_box" class="mb-3 p-2 bg-light border rounded text-center" style="display:none;">
                        <small class="text-muted d-block mb-1">Image Preview</small>
                        <img id="upload_image_preview" src="" alt="Preview" style="max-height: 140px; max-width: 100%; object-fit: contain; border-radius: 6px;">
                    </div>
                </div>
                <div class="modal-footer border-top py-2 px-4 bg-light">
                    <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark-blue btn-sm px-4" id="upload_btn">
                        <i class="bi bi-cloud-arrow-up me-1"></i> Upload Now
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Document / Image Modal --}}
<div class="modal fade" id="editDocModal" tabindex="-1" aria-labelledby="editDocModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="editDocForm">
                @csrf
                <input type="hidden" id="edit_doc_id" name="doc_id">
                <div class="modal-header border-bottom py-3 px-4 bg-light">
                    <h5 class="modal-title fw-bold text-dark" id="editDocModalLabel">
                        <i class="bi bi-pencil-square me-2 text-primary"></i>Edit Document / Replace Image
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Document Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="edit_title" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Category / Type</label>
                        <select name="category" id="edit_category" class="form-select">
                            <option value="policy_pdf">Policy PDF / Schedule</option>
                            <option value="kyc_aadhaar">KYC - Aadhaar Card</option>
                            <option value="kyc_pan">KYC - PAN Card</option>
                            <option value="kyc_driving_licence">KYC - Driving Licence</option>
                            <option value="kyc_address">KYC - Address Proof</option>
                            <option value="kyc_photo">Customer Photo</option>
                            <option value="kyc_signature">Signature</option>
                            <option value="vehicle_rc">Vehicle RC / Fitness</option>
                            <option value="medical_doc">Medical / Health Report</option>
                            <option value="general">General Document / Image</option>
                        </select>
                    </div>

                    <div class="mb-3 p-3 bg-light border rounded">
                        <div class="small fw-semibold text-dark mb-1">Current File:</div>
                        <div class="small text-muted font-monospace" id="edit_current_filename">--</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Replace File or Image (Optional)</label>
                        <input type="file" name="file" id="edit_file" class="form-control" accept=".pdf,.png,.jpg,.jpeg,.gif,.webp,.doc,.docx">
                        <div class="form-text small text-muted">
                            Leave empty if you only want to update the title or category.
                        </div>
                    </div>

                    {{-- Live Image Preview if replacing --}}
                    <div id="edit_image_preview_box" class="mb-3 p-2 bg-light border rounded text-center" style="display:none;">
                        <small class="text-muted d-block mb-1">New Image Preview</small>
                        <img id="edit_image_preview" src="" alt="Preview" style="max-height: 140px; max-width: 100%; object-fit: contain; border-radius: 6px;">
                    </div>
                </div>
                <div class="modal-footer border-top py-2 px-4 bg-light">
                    <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark-blue btn-sm px-4" id="edit_submit_btn">
                        <i class="bi bi-check2-circle me-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Fullscreen Image Lightbox Modal --}}
<div class="modal fade" id="imageLightboxModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 bg-dark text-white shadow-lg overflow-hidden">
            <div class="modal-header border-bottom border-secondary py-2 px-3">
                <h6 class="modal-title fw-bold text-white text-truncate" id="lightboxTitle">Image Preview</h6>
                <div class="d-flex align-items-center gap-2">
                    <a href="#" id="lightboxDownloadBtn" class="btn btn-sm btn-outline-light rounded-pill px-3" download>
                        <i class="bi bi-download me-1"></i> Download
                    </a>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body p-0 text-center bg-black d-flex align-items-center justify-content-center" style="min-height: 380px; max-height: 80vh;">
                <img id="lightboxImage" src="" alt="Full view" style="max-width: 100%; max-height: 78vh; object-fit: contain;">
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Live preview for Upload Modal
    $('#upload_file').on('change', function() {
        const file = this.files[0];
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#upload_image_preview').attr('src', e.target.result);
                $('#upload_image_preview_box').slideDown();
            };
            reader.readAsDataURL(file);
        } else {
            $('#upload_image_preview_box').slideUp();
        }
    });

    // Live preview for Edit Modal
    $('#edit_file').on('change', function() {
        const file = this.files[0];
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#edit_image_preview').attr('src', e.target.result);
                $('#edit_image_preview_box').slideDown();
            };
            reader.readAsDataURL(file);
        } else {
            $('#edit_image_preview_box').slideUp();
        }
    });

    // Image Lightbox Trigger
    $(document).on('click', '.img-thumbnail-trigger', function(e) {
        e.preventDefault();
        const imgUrl = $(this).data('img-url');
        const imgTitle = $(this).data('img-title') || 'Image Preview';
        $('#lightboxImage').attr('src', imgUrl);
        $('#lightboxTitle').text(imgTitle);
        $('#lightboxDownloadBtn').attr('href', imgUrl);
        $('#imageLightboxModal').modal('show');
    });

    // Open Edit Modal
    $(document).on('click', '.btn-edit-doc', function() {
        const id = $(this).data('id');
        const title = $(this).data('title');
        const category = $(this).data('category');
        const filename = $(this).data('file-name');
        const filetype = $(this).data('file-type');

        $('#edit_doc_id').val(id);
        $('#edit_title').val(title);
        if (category) {
            $('#edit_category').val(category);
        }
        $('#edit_current_filename').text(filename + ' (' + filetype + ')');
        $('#edit_file').val('');
        $('#edit_image_preview_box').hide();
        $('#editDocModal').modal('show');
    });

    // Submit Edit Form via AJAX
    $('#editDocForm').on('submit', function(e) {
        e.preventDefault();
        const docId = $('#edit_doc_id').val();
        const formData = new FormData(this);
        const $submitBtn = $('#edit_submit_btn');

        $submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

        $.ajax({
            url: '/documents/' + docId + '/update-ajax',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            },
            success: function(res) {
                $submitBtn.prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i> Save Changes');
                $('#editDocModal').modal('hide');
                Swal.fire({
                    icon: 'success',
                    title: 'Updated!',
                    text: res.message || 'Document updated successfully.',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            },
            error: function(xhr) {
                $submitBtn.prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i> Save Changes');
                const err = xhr.responseJSON?.message || 'Failed to update document.';
                Swal.fire('Error', err, 'error');
            }
        });
    });

    // Delete Document via AJAX
    $(document).on('click', '.btn-delete-doc', function() {
        const docId = $(this).data('id');
        const title = $(this).data('title') || 'this document';
        const $row = $('#doc-row-' + docId);

        Swal.fire({
            title: 'Delete Document?',
            text: 'Are you sure you want to delete "' + title + '"? This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/documents/' + docId + '/delete-ajax',
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json'
                    },
                    success: function(res) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: res.message || 'Document deleted successfully.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        $row.fadeOut(300, function() {
                            $(this).remove();
                            // Recalculate counter
                            const remaining = $('.doc-row:visible').length;
                            $('#kpi-total').text(remaining);
                            if ($('.doc-row').length === 0) {
                                location.reload();
                            }
                        });
                    },
                    error: function() {
                        Swal.fire('Error', 'Unable to delete document. Please try again.', 'error');
                    }
                });
            }
        });
    });

    // Category Tabs Filter
    $('.tab-btn, .kpi-filter-card').on('click', function() {
        const filter = $(this).data('filter');
        $('.tab-btn').removeClass('active');
        $('.tab-btn[data-filter="' + filter + '"]').addClass('active');

        applyFilters();
    });

    // Instant Search Filter
    $('#docSearchInput').on('keyup input', function() {
        applyFilters();
    });

    function applyFilters() {
        const activeFilter = $('.tab-btn.active').data('filter') || 'all';
        const searchKeyword = ($('#docSearchInput').val() || '').toLowerCase().trim();

        let visibleRows = 0;

        $('.doc-row').each(function() {
            const $row = $(this);
            const rowCat = $row.data('category');
            const rowTitle = ($row.data('title') || '').toString().toLowerCase();
            const isKyc = $row.data('is-kyc') == '1' || rowCat === 'kyc';
            const isPolicy = $row.data('is-policy') == '1' || rowCat === 'policy';
            const isImg = $row.data('is-image') == '1' || rowCat === 'image';

            let matchesCat = false;
            if (activeFilter === 'all') {
                matchesCat = true;
            } else if (activeFilter === 'kyc') {
                matchesCat = isKyc;
            } else if (activeFilter === 'policy') {
                matchesCat = isPolicy;
            } else if (activeFilter === 'image') {
                matchesCat = isImg;
            } else {
                matchesCat = (rowCat === activeFilter);
            }

            let matchesSearch = true;
            if (searchKeyword) {
                matchesSearch = rowTitle.includes(searchKeyword);
            }

            if (matchesCat && matchesSearch) {
                $row.show();
                visibleRows++;
            } else {
                $row.hide();
            }
        });

        if (visibleRows === 0 && $('.doc-row').length > 0) {
            $('#empty-filter-placeholder').show();
        } else {
            $('#empty-filter-placeholder').hide();
        }
    }
});
</script>
@endpush

