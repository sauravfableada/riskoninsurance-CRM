@extends('layouts.app')

@section('page_title', 'Create Email Marketing Template')

@section('content')
@include('marketing.email_marketing_templates._form', [
    'action' => route('marketing.email_marketing_templates.store'),
    'method' => 'POST',
    'record' => null,
    'templates' => $templates,
    'submitLabel' => 'Create Template',
])
@endsection

