@extends('layouts.app')

@section('page_title', 'View Client Document')

@push('styles')
    <link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/users-show.css' : '/css/users-show.css') }}?v={{ filemtime(public_path('css/users-show.css')) }}">
    <style>
        .file-thumb { height: 140px; width: 100%; object-fit: cover; border-radius: 8px; }
        .file-card  { transition: box-shadow .2s; }
        .file-card:hover { box-shadow: 0 4px 14px rgba(0,0,0,.1); }
    </style>
@endpush

@section('content')
<div class="container-fluid p-0">
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden detail-view-card">

        {{-- Header --}}
        <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div class="flex-grow-1">
                    <h1 class="h4 mb-1 fw-semibold">Document Details</h1>
                    <p class="text-muted small mb-0">Complete information about this uploaded document.</p>
                </div>
                <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                    <a href="{{ route('upload-document.edit', $document->id) }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                        <i class="bi bi-pencil me-1"></i> Edit
                    </a>
                    <a href="{{ route('upload-document.list') }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                        <i class="fa-solid fa-angle-left pe-1"></i> Back
                    </a>
                </div>
            </div>
        </div>

        {{-- Body --}}
        <div class="card-body p-3 p-md-4">

            @if($document)

            <div class="detail-view-block">

                {{-- Document Info Grid --}}
                <div class="row g-0 detail-view-grid mb-0">

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label pe-2">
                            <i class="fas fa-users text-muted me-2"></i>Family Group:
                        </span>
                        <span class="detail-view-value">{{ $document->familyGroup->group_name ?? '--' }}</span>
                    </div>

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label pe-2">
                            <i class="fas fa-user text-muted me-2"></i>Client Name:
                        </span>
                        <span class="detail-view-value">{{ $document->client->name ?? '--' }}</span>
                    </div>

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label pe-2">
                            <i class="fas fa-folder-open text-muted me-2"></i>Document Type:
                        </span>
                        <span class="detail-view-value">{{ $document->documentType->document_type ?? '--' }}</span>
                    </div>

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label pe-2">
                            <i class="fas fa-file-alt text-muted me-2"></i>Document Name:
                        </span>
                        <span class="detail-view-value">{{ $document->documentName->document_name ?? '--' }}</span>
                    </div>

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label pe-2">
                            <i class="fas fa-hashtag text-muted me-2"></i>Document Number:
                        </span>
                        <span class="detail-view-value">{{ $document->document_number ?: '--' }}</span>
                    </div>

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label pe-2">
                            <i class="fas fa-calendar-alt text-muted me-2"></i>Due Date:
                        </span>
                        <span class="detail-view-value">
                            {{ $document->due_date ? \Carbon\Carbon::parse($document->due_date)->format('d M Y') : '--' }}
                        </span>
                    </div>

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label pe-2">
                            <i class="fas fa-toggle-on text-muted me-2"></i>Status:
                        </span>
                        <span class="detail-view-value">
                            <span class="badge rounded-pill px-3 {{ $document->status ? 'bg-success' : 'bg-secondary' }}">
                                {{ $document->status ? 'Active' : 'Inactive' }}
                            </span>
                        </span>
                    </div>

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label pe-2">
                            <i class="fas fa-fingerprint text-muted me-2"></i>Document ID:
                        </span>
                        <span class="detail-view-value">#{{ $document->id }}</span>
                    </div>

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label pe-2">
                            <i class="fas fa-user-tie text-muted me-2"></i>Created By:
                        </span>
                        <span class="detail-view-value">{{ $document->creator->name ?? '--' }}</span>
                    </div>

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label pe-2">
                            <i class="fas fa-calendar-plus text-muted me-2"></i>Created At:
                        </span>
                        <span class="detail-view-value">
                            {{ $document->created_at ? $document->created_at->format('d M Y, h:i A') : '--' }}
                        </span>
                    </div>

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label pe-2">
                            <i class="fas fa-user-edit text-muted me-2"></i>Modified By:
                        </span>
                        <span class="detail-view-value">{{ $document->modifier->name ?? '--' }}</span>
                    </div>

                    <div class="col-md-6 detail-view-row">
                        <span class="detail-view-label pe-2">
                            <i class="fas fa-clock text-muted me-2"></i>Updated At:
                        </span>
                        <span class="detail-view-value">
                            {{ $document->updated_at ? $document->updated_at->format('d M Y, h:i A') : '--' }}
                        </span>
                    </div>

                    @if($document->remarks)
                    <div class="col-12 detail-view-row">
                        <span class="detail-view-label pe-2">
                            <i class="fas fa-comment-alt text-muted me-2"></i>Remarks:
                        </span>
                        <span class="detail-view-value">{{ $document->remarks }}</span>
                    </div>
                    @endif

                </div>

                {{-- Uploaded Files --}}
                <div class="mt-4 pt-3 border-top">
                    <h6 class="fw-semibold mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-paperclip"></i> Uploaded Files
                        <span class="badge bg-secondary rounded-pill">{{ $files->count() }}</span>
                    </h6>

                    @if($files->isNotEmpty())
                        <div class="row g-3">
                            @foreach($files as $file)
                                @php $ext = strtolower(pathinfo($file->file_name, PATHINFO_EXTENSION)); @endphp
                                <div class="col-6 col-md-3 col-lg-2">
                                    <div class="border rounded-3 p-2 text-center file-card h-100 bg-light">

                                        @if(in_array($ext, ['jpg','jpeg','png']))
                                            <img src="{{ asset($file->file_path) }}"
                                                 alt="{{ $file->file_name }}"
                                                 class="file-thumb mb-2"
                                                 onerror="this.style.display='none'">
                                        @elseif($ext === 'pdf')
                                            <div class="d-flex align-items-center justify-content-center mb-2" style="height:140px;">
                                                <i class="bi bi-file-earmark-pdf-fill text-danger" style="font-size:3rem;"></i>
                                            </div>
                                        @else
                                            <div class="d-flex align-items-center justify-content-center mb-2" style="height:140px;">
                                                <i class="bi bi-file-earmark-fill text-secondary" style="font-size:3rem;"></i>
                                            </div>
                                        @endif

                                        <p class="mb-2 small text-muted text-truncate" title="{{ $file->file_name }}">
                                            {{ $file->file_name }}
                                        </p>

                                        <a href="{{ asset($file->file_path) }}" target="_blank"
                                           class="btn btn-sm btn-outline-dark-blue w-100">
                                            <i class="bi bi-eye me-1"></i> View
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-file-earmark-x" style="font-size:2.5rem; opacity:.35;"></i>
                            <p class="mt-2 mb-0 small">No files uploaded.</p>
                        </div>
                    @endif
                </div>

            </div>

            @else

            {{-- No record --}}
            <div class="text-center py-5 text-muted">
                <i class="bi bi-file-earmark-x display-1 opacity-25"></i>
                <p class="mt-3 mb-0">No record found.</p>
                <a href="{{ route('upload-document.list') }}" class="btn btn-dark-blue btn-sm mt-3 rounded-pill px-4">
                    Back to List
                </a>
            </div>

            @endif

        </div>
    </div>
</div>
@endsection
