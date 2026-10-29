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
        <form method="GET" action="{{ route('documents.index') }}" id="docFilterForm" class="row g-2 align-items-center">
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
                    <input type="text" name="search" id="search" class="form-control border-start-0" placeholder="Search by customer name, phone, email, or document..." value="{{ $search }}" autocomplete="off">
                </div>
            </div>
            <div class="col-md-2 d-flex align-items-end gap-1 mt-auto">
                <button type="submit" class="btn btn-dark-blue btn-sm flex-grow-1" id="btnSubmitFilter">Filter</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnClearFilters" title="Clear Filters" style="{{ ($selectedCustomerId || $search) ? '' : 'display:none;' }}">
                    <i class="bi bi-x-lg"></i>
                </button>
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

    <div id="documentsTableContainer">
        @include('crm.documents.table_partial')
    </div>
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

    // ── Instant Filter without page reload (AJAX + Client-side Instant Filter) ──
    function filterDocumentsAjax(pageUrl) {
        const custId = $('#customer_id').val() || '';
        const searchVal = $('#search').val() || '';
        const url = pageUrl || "{{ route('documents.index') }}";

        $('#documentsTableContainer').css('opacity', '0.5');

        // Instant client-side row filtering first for 0ms feedback
        if (!pageUrl) {
            applyClientSideRowFilters(custId, searchVal);
        }

        $.ajax({
            url: url,
            type: 'GET',
            data: {
                customer_id: custId,
                search: searchVal,
                ajax: 1
            },
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(html) {
                $('#documentsTableContainer').html(html).css('opacity', '1');

                // Update browser URL query string without reloading page
                const newUrl = new URL(window.location.href);
                if (custId) {
                    newUrl.searchParams.set('customer_id', custId);
                } else {
                    newUrl.searchParams.delete('customer_id');
                }
                if (searchVal) {
                    newUrl.searchParams.set('search', searchVal);
                } else {
                    newUrl.searchParams.delete('search');
                }
                newUrl.searchParams.delete('ajax');
                window.history.replaceState({}, '', newUrl.toString());

                // Update clear button visibility
                if (custId || searchVal) {
                    $('#btnClearFilters').show();
                } else {
                    $('#btnClearFilters').hide();
                }
            },
            error: function() {
                $('#documentsTableContainer').css('opacity', '1');
            }
        });
    }

    function applyClientSideRowFilters(custId, searchVal) {
        const sTerm = (searchVal || '').toLowerCase().trim();
        $('.customer-row').each(function() {
            const $row = $(this);
            const rowCustId = $row.data('customer-id') ? $row.data('customer-id').toString() : '';
            const rowSearch = ($row.data('search-text') || '').toString().toLowerCase();

            const matchCust = !custId || (rowCustId === custId.toString());
            const matchSearch = !sTerm || rowSearch.includes(sTerm);

            if (matchCust && matchSearch) {
                $row.show();
            } else {
                $row.hide();
            }
        });
    }

    // Trigger filter on customer dropdown onchange
    $('#customer_id').on('change select2:select select2:clear', function() {
        filterDocumentsAjax();
    });

    // Trigger filter on search input typing (debounced)
    let searchDebounceTimer = null;
    $('#search').on('keyup input', function() {
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(function() {
            filterDocumentsAjax();
        }, 300);
    });

    // Form submit blocker -> triggers AJAX filter
    $('#docFilterForm').on('submit', function(e) {
        e.preventDefault();
        filterDocumentsAjax();
        return false;
    });

    // Clear filters button without page reload
    $('#btnClearFilters').on('click', function(e) {
        e.preventDefault();
        $('#customer_id').val('').trigger('change.select2');
        $('#search').val('');
        filterDocumentsAjax();
    });

    // AJAX pagination links
    $(document).on('click', '#documentsTableContainer .pagination a', function(e) {
        e.preventDefault();
        const targetUrl = $(this).attr('href');
        if (targetUrl) {
            filterDocumentsAjax(targetUrl);
        }
    });
});
</script>
@endpush
