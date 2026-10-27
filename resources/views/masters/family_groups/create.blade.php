@extends('layouts.masters')

@section('page_title', 'Masters - Family Group Master')

@section('masters_content')

@endsection

@push('scripts')
    <script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/assets/js/family_groups.js' : '/assets/js/family_groups.js') }}?v={{ filemtime(public_path('assets/js/family_groups.js')) }}"></script>
@endpush
