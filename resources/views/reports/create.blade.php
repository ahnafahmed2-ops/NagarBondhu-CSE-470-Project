@extends('layouts.app')
@section('title', 'Report a problem')
@section('content')
<h1>Report a problem</h1>
<p class="lead">Pick a category and describe what you see. Your report starts with the status “Submitted”.</p>
@include('reports._form', [
    'action' => route('reports.store'),
    'submitLabel' => 'Submit report',
    'cancelUrl' => route('reports.index'),
])
@endsection
