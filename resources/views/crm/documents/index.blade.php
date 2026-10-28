@extends('layouts.app')

@section('page_title', 'Document Center')

@section('page_actions')
    <a href="{{ route('documents.create') }}" class="btn btn-dark-blue btn-sm rounded-pill px-3">
        <i class="bi bi-cloud-upload me-1"></i> Upload Document
    </a>
@endsection

@section('content')
<div class="card border-0 shadow-sm mb-4 overflow-hidden">
    <div class="card-header bg-white border-bottom-0 py-3 px-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h5 class="fw-bold mb-1">Customer Documents Repository</h5>
                <p class="text-muted small mb-0">Browse and manage uploaded documents customer-wise.</p>
            </div>
            <a href="{{ route('documents.create') }}" class="btn btn-dark-blue btn-sm px-3">
                <i class="bi bi-cloud-upload me-1"></i> Upload Document
            </a>
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
    
    @if(session('error'))
        <div class="px-4">
            <div class="alert alert-danger alert-dismissible fade show pb-2 pt-2 mb-3" role="alert">
                {{ session('error') }}
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
                    <option value="">-- All Customers (Listing All) --</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}" {{ (string)$selectedCustomerId === (string)$c->id ? 'selected' : '' }}>
                            {{ $c->name }} ({{ $c->phone ?: $c->email ?: 'ID: ' . $c->id }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-5">
                <label for="search" class="form-label small fw-semibold text-muted mb-1">Search Keyword</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" id="search" class="form-control border-start-0" placeholder="Search by title, customer name or email..." value="{{ $search }}">
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

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="documentsTable">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Customer Name</th>
                        <th>Document Title</th>
                        <th>File Type</th>
                        <th>Uploaded By</th>
                        <th>Date</th>
                        <th class="pe-4 text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($documents as $document)
                        <tr>
                            <td class="ps-4">
                                @if($document->customer)
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-sm bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; font-weight: 600;">
                                            {{ strtoupper(substr($document->customer->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="fw-semibold text-dark">{{ $document->customer->name }}</div>
                                            <div class="small text-muted">{{ $document->customer->phone ?: $document->customer->email }}</div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted fst-italic">General / Unassigned</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-light rounded p-2 me-2 d-flex align-items-center justify-content-center text-primary" style="width: 36px; height: 36px;">
                                        @if(in_array(strtolower($document->file_type), ['pdf']))
                                            <i class="bi bi-file-earmark-pdf fs-5 text-danger"></i>
                                        @elseif(in_array(strtolower($document->file_type), ['png', 'jpg', 'jpeg', 'gif', 'svg']))
                                            <i class="bi bi-file-earmark-image fs-5 text-success"></i>
                                        @elseif(in_array(strtolower($document->file_type), ['doc', 'docx']))
                                            <i class="bi bi-file-earmark-word fs-5 text-primary"></i>
                                        @else
                                            <i class="bi bi-file-earmark-text fs-5"></i>
                                        @endif
                                    </div>
                                    <div class="fw-semibold text-dark">{{ $document->title }}</div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-secondary rounded-pill px-3">{{ strtoupper($document->file_type ?: 'FILE') }}</span>
                            </td>
                            <td>
                                <span class="text-muted">{{ $document->user->name ?? 'System' }}</span>
                            </td>
                            <td>
                                <span class="text-muted">{{ $document->created_at ? $document->created_at->format('M d, Y') : '--' }}</span>
                            </td>
                            <td class="pe-4 text-end">
                                <a href="{{ route('documents.download', $document->id) }}" class="btn btn-sm btn-light rounded-circle me-1" title="Download">
                                    <i class="bi bi-download text-success"></i>
                                </a>
                                <a href="{{ route('documents.edit', $document->id) }}" class="btn btn-sm btn-light rounded-circle me-1" title="Edit">
                                    <i class="bi bi-pencil-square text-primary"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-light rounded-circle ajax-document-delete" data-id="{{ $document->id }}" title="Delete">
                                    <i class="bi bi-trash text-danger"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-folder2-open fs-1 d-block mb-3 opacity-50"></i>
                                No documents found for the selected criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="px-4 py-3 border-top">
            {{ $documents->links() }}
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
            placeholder: '-- All Customers (Listing All) --',
            allowClear: true
        });
    }

    $(document).on('click', '.ajax-document-delete', function(e) {
        e.preventDefault();
        const docId = $(this).data('id');
        const $row = $(this).closest('tr');

        Swal.fire({
            title: 'Delete this document?',
            text: 'This action cannot be undone!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/api/documents/' + docId,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json'
                    },
                    success: function(res) {
                        Swal.fire('Deleted!', res.message || 'Document deleted.', 'success');
                        $row.fadeOut(300, function() { $(this).remove(); });
                    },
                    error: function() {
                        Swal.fire('Error', 'Unable to delete document. Please try again.', 'error');
                    }
                });
            }
        });
    });
});
</script>
@endpush
