@extends('layouts.app')

@section('page_title', 'Document Center')

@section('page_actions')
    <a href="{{ route('documents.create') }}" class="btn btn-dark-blue btn-sm rounded-pill px-3">
        <i class="bi bi-cloud-upload me-1"></i> Upload Document
    </a>
@endsection

@section('content')
<div class="card border-0 shadow-sm mb-4 overflow-hidden">
    <div class="card-header bg-white border-bottom-0 py-4 px-4">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">Documents Repository</h5>
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

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Title</th>
                        <th>Type</th>
                        <th>Uploaded By</th>
                        <th>Date</th>
                        <th class="pe-4 text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($documents as $document)
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="bg-light rounded p-2 me-3 d-flex align-items-center justify-content-center text-primary" style="width: 40px; height: 40px;">
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
                                <span class="badge bg-secondary rounded-pill px-3">{{ strtoupper($document->file_type) }}</span>
                            </td>
                            <td>
                                <span class="text-muted">{{ $document->user->name ?? 'Unknown' }}</span>
                            </td>
                            <td>
                                <span class="text-muted">{{ $document->created_at->format('M d, Y') }}</span>
                            </td>
                            <td class="pe-4 text-end">
                                <a href="{{ route('documents.download', $document->id) }}" class="btn btn-sm btn-light rounded-circle me-1" title="Download">
                                    <i class="bi bi-download text-success"></i>
                                </a>
                                <a href="{{ route('documents.edit', $document->id) }}" class="btn btn-sm btn-light rounded-circle me-1" title="Edit">
                                    <i class="bi bi-pencil-square text-primary"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-light rounded-circle ajax-document-delete" data-id="{{ $document->id }}" title="Delete">
                                    <i class="bi bi-trash text-danger text-danger"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-folder2-open fs-1 d-block mb-3 opacity-50"></i>
                                No documents found.
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
<script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/js/documents.js' : '/js/documents.js') }}"></script>
@endpush
