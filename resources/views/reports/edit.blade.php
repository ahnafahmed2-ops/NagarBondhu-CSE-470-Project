@extends('layouts.app')
@section('title', 'Edit report')
@section('content')
<h1>Edit report</h1>
<p class="lead">You can change a report until staff start reviewing it.</p>
@include('reports._form', [
    'action' => route('reports.update', $report),
    'submitLabel' => 'Save changes',
    'cancelUrl' => route('reports.show', $report),
])
@endsection
