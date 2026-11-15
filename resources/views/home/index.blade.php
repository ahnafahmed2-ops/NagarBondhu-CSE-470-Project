@extends('layouts.app')
@section('title', 'Report city problems')
@section('content')
<section class="hero">
  <h1>See a problem on your street? Put it on record.</h1>
  <p class="lead">NagarBondhu lets you report waterlogging, broken roads and other city problems so the right authority can see them and act.</p>
  <p><a class="btn" href="{{ route('register') }}">Create a free account</a>
     <a class="btn ghost" href="{{ route('login') }}">I already have an account</a></p>
</section>
<section>
  <h2>What you can report</h2>
  <ul class="plates">
    @foreach ($categories as $c)
      <li class="plate" title="{{ $c->description }}">{{ $c->name }}</li>
    @endforeach
  </ul>
</section>
@endsection
