@extends('layouts.app')
@section('title', $report->title)
@section('content')
<p><a href="{{ route('reports.index') }}">&larr; My reports</a></p>
<article class="report">
  <header>
    <span class="plate">{{ $report->category->name }}</span>
    <span>@include('reports._priority') <span class="status">{{ $report->status }}</span></span>
  </header>
  <h1>{{ $report->title }}</h1>
  <dl class="facts">
    <dt>Tracking code</dt><dd><a href="{{ route('track', ['code' => $report->tracking_code]) }}"><code>{{ $report->tracking_code }}</code></a> <span class="muted">– share it so anyone can follow progress</span></dd>
    <dt>Community</dt><dd>{{ $report->voteCount('affected') }} affected · {{ $report->voteCount('still_there') }} saw it still there · {{ $report->voteCount('fixed') }} say it is fixed</dd>
    <dt>Location</dt><dd>{{ $report->address }}{{ $report->ward ? ' · '.$report->ward : '' }}</dd>
    <dt>Reported</dt><dd>{{ $report->created_at->format('d M Y, H:i') }}</dd>
    @if ($report->updated_at->gt($report->created_at))
      <dt>Last edited</dt><dd>{{ $report->updated_at->format('d M Y, H:i') }}</dd>
    @endif
  </dl>
  <p class="body-text">{!! nl2br(e($report->description)) !!}</p>
  @if ($report->evidence_path)
    <figure><img src="{{ $report->evidenceUrl() }}" alt="Evidence photo for this report"></figure>
  @endif

  @include('reports._timeline', ['histories' => $report->histories])

  @if ($report->isEditable())
    <div class="actions">
      <a class="btn" href="{{ route('reports.edit', $report) }}">Edit report</a>
      <form method="POST" action="{{ route('reports.destroy', $report) }}" class="inline"
            data-confirm="Delete this report? This cannot be undone.">
        @csrf @method('DELETE')
        <button class="btn danger" type="submit">Delete report</button>
      </form>
    </div>
  @else
    <p class="muted">This report is being handled, so it can no longer be edited or deleted.</p>
  @endif
</article>
@endsection
