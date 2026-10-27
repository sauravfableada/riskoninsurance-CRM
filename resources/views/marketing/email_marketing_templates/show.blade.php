@extends('layouts.app')

@section('page_title', 'View Email Marketing Template')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">{{ $record->name }}</h5>
                <span class="badge crm-status-pill rounded-pill bg-secondary text-uppercase">{{ $record->status }}</span>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="mb-2 text-muted small">
                    Base template: {{ $record->defaultTemplate->name ?? '--' }} |
                    Created by: {{ $record->creator->name ?? '--' }} |
                    {{ $record->created_at?->format('d M Y H:i') }}
                </div>
                <div class="border rounded p-3 bg-light">
                    {!! $record->content !!}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
