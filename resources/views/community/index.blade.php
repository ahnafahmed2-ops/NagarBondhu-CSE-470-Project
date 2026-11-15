@extends('layouts.app')
@section('title', 'Community reports')
@section('content')
<h1>Problems in your area</h1>
<p class="lead">Open reports from other citizens. Tell us if a problem affects you too, if you saw it is still there, or if it has already been fixed. Your votes raise or lower its priority.</p>

<form method="GET" action="{{ route('community.index') }}" class="filter filter-row">
  <label>Ward / area
    <input name="ward" value="{{ $ward }}" maxlength="60" placeholder="e.g. Ward 17 or Mirpur">
  </label>
  <label>Category
    <select name="category">
      <option value="">All categories</option>
      @foreach ($categories as $c)
        <option value="{{ $c->id }}" @selected($filter === (int) $c->id)>{{ $c->name }}</option>
      @endforeach
    </select>
  </label>
  <button class="btn small" type="submit">Show</button>
</form>

@if ($reports->isEmpty())
  <div class="empty"><p>No open reports from other citizens match this filter.</p></div>
@else
  <ul class="report-list">
    @foreach ($reports as $r)
      <li>
        <a href="{{ route('community.show', $r) }}">
          <span class="plate small">{{ $r->category->name }}</span>
          <strong>{{ $r->title }}</strong>
          <span class="meta">{{ $r->address }}{{ $r->ward ? ' · '.$r->ward : '' }} · {{ $r->affected_count }} affected · {{ $r->still_there_count }} saw it</span>
          <span class="status">@include('reports._priority', ['report' => $r])</span>
        </a>
      </li>
    @endforeach
  </ul>
@endif
@endsection
