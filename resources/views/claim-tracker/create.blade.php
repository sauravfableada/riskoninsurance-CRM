@extends('layouts.app')
@section('page_title', 'Add Claim')
@section('content')
<div class="container-fluid p-0"><div class="card shadow-sm border-0 rounded-4 overflow-hidden form-card">
    <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div><h1 class="h4 mb-1 fw-semibold">Add Claim</h1><p class="text-muted small mb-0">Create and track a new insurance claim.</p></div>
            <a href="{{ route('claim-tracker.index') }}" class="btn btn-dark-blue"><i class="fa-solid fa-angle-left pe-1"></i><span>Back</span></a>
        </div>
    </div>
    <div class="card-body p-3 p-md-4"><form method="POST" action="{{ route('claim-tracker.store') }}" enctype="multipart/form-data">@csrf @include('claim-tracker._form')
        <div class="d-flex justify-content-end gap-2"><a href="{{ route('claim-tracker.index') }}" class="btn btn-secondary">Cancel</a><button class="btn btn-dark-blue" type="submit">Save Claim</button></div>
    </form></div>
</div></div>
@endsection
