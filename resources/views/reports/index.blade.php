@extends('layouts.app')
@section('title', 'My reports')
@section('content')
<div class="page-head">
  <h1>My reports</h1>
  <a class="btn" href="{{ route('reports.create') }}">Report a problem</a>
</div>

<form method="GET" action="{{ route('reports.index') }}" class="filter">
  <label>Show
    <select name="category" onchange="this.form.submit()">
      <option value="">All categories</option>
      @foreach ($categories as $c)
        <option value="{{ $c->id }}" @selected($filter === (int) $c->id)>{{ $c->name }}</option>
      @endforeach
    </select>
  </label>
  <noscript><button class="btn small" type="submit">Apply</button></noscript>
</form>

@if ($reports->isEmpty())
  <div class="empty">
    <p>{{ $filter ? 'You have no reports in this category.' : 'You haven’t reported anything yet.' }}</p>
    <a class="btn" href="{{ route('reports.create') }}">Report your first problem</a>
  </div>
@else
  <ul class="report-list">
    @foreach ($reports as $r)
      <li>
        <a href="{{ route('reports.show', $r) }}">
          <span class="plate small">{{ $r->category->name }}</span>
          <strong>{{ $r->title }}</strong>
          <span class="meta">{{ $r->address }} · {{ $r->created_at->format('d M Y') }}</span>
          <span class="status">{{ $r->status }}</span>
          <span class="meta">{{ $r->tracking_code }} · Priority {{ $r->priority_level }}</span>
        </a>
      </li>
    @endforeach
  </ul>
@endif
@endsection
