@extends('layouts.app')

@section('page_title', 'Edit Email Marketing Template')

@section('content')
@include('marketing.email_marketing_templates._form', [
    'action' => route('marketing.email_marketing_templates.update', $record),
    'method' => 'PUT',
    'record' => $record,
    'templates' => $templates,
    'submitLabel' => 'Update Template',
])
@endsection

