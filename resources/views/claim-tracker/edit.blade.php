@extends('layouts.app')
@section('page_title', 'Edit Claim')
@section('content')
<div class="container-fluid p-0"><div class="card shadow-sm border-0 rounded-4 overflow-hidden form-card">
    <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 w-100">
            <div class="flex-grow-1"><h1 class="h4 mb-1 fw-semibold">Edit Claim</h1><p class="text-muted small mb-0">Update insurance claim details and status.</p></div>
            <div class="d-flex flex-wrap gap-2"><a href="{{ route('claim-tracker.show', $claimTracker) }}" class="btn btn-outline-dark-blue"><i class="bi bi-eye me-1"></i>View</a><a href="{{ route('claim-tracker.index') }}" class="btn btn-dark-blue"><i class="fa-solid fa-angle-left pe-1"></i>Back</a></div>
        </div>
    </div>
    <div class="card-body p-3 p-md-4"><form method="POST" action="{{ route('claim-tracker.update', $claimTracker) }}" enctype="multipart/form-data">@csrf @method('PUT') @include('claim-tracker._form')
        <div class="d-flex justify-content-end gap-2"><a href="{{ route('claim-tracker.index') }}" class="btn btn-secondary">Cancel</a><button class="btn btn-dark-blue" type="submit">Update Claim</button></div>
    </form></div>
</div></div>
@endsection
