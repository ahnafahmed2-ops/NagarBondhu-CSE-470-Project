@extends('layouts.app')
@section('title', 'Admin – reports')
@section('content')
<h1>All reports</h1>
<form method="GET" action="{{ route('admin.reports.index') }}" class="filter filter-row">
  <label>Status
    <select name="status">
      <option value="">Any status</option>
      @foreach ($statuses as $s)<option value="{{ $s }}" @selected($status === $s)>{{ $s }}</option>@endforeach
    </select>
  </label>
  <label>Priority
    <select name="priority">
      <option value="">Any priority</option>
      @foreach ($levels as $l)<option value="{{ $l }}" @selected($priority === $l)>{{ $l }}</option>@endforeach
    </select>
  </label>
  <button class="btn small" type="submit">Show</button>
</form>

@if ($reports->isEmpty())
  <div class="empty"><p>No reports match this filter.</p></div>
@else
  <ul class="report-list">
    @foreach ($reports as $r)
      <li>
        <a href="{{ route('admin.reports.show', $r) }}">
          <span class="plate small">{{ $r->category->name }}</span>
          <strong>{{ $r->title }}</strong>
          <span class="meta">{{ $r->tracking_code }} · {{ $r->address }} · {{ $r->created_at->format('d M Y') }} · @include('reports._priority', ['report' => $r])</span>
          <span class="status">{{ $r->status }}</span>
        </a>
      </li>
    @endforeach
  </ul>
@endif
@endsection
