@extends('layouts.app')
@section('title', 'Track a report')
@section('content')
<h1>Track a report</h1>
<p class="lead">Enter the tracking code you got when the report was submitted (for example NB-261115-K7Q2M).</p>

<form method="GET" action="{{ route('track') }}" class="filter filter-row">
  <label>Tracking code <input name="code" value="{{ $code }}" maxlength="20" required placeholder="NB-YYMMDD-XXXXX"></label>
  <button class="btn small" type="submit">Track</button>
</form>

@if ($code !== '' && ! $report)
  <div class="notice bad" role="alert">No report has the code {{ $code }}. Check it and try again.</div>
@elseif ($report)
  <article class="report">
    <header>
      <span class="plate">{{ $report->category->name }}</span>
      <span class="status">{{ $report->status }}</span>
    </header>
    <h2>{{ $report->title }}</h2>
    <dl class="facts">
      <dt>Tracking code</dt><dd><code>{{ $report->tracking_code }}</code></dd>
      <dt>Location</dt><dd>{{ $report->address }}{{ $report->ward ? ' · '.$report->ward : '' }}</dd>
      <dt>Priority</dt><dd>{{ $report->priority_level }}</dd>
    </dl>
    @include('reports._timeline', ['histories' => $report->histories])
  </article>
@endif
@endsection
