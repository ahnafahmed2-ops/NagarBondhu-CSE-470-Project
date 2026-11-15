@extends('layouts.app')
@section('title', $report->title)
@section('content')
<p><a href="{{ route('community.index') }}">&larr; Community reports</a></p>
<article class="report">
  <header>
    <span class="plate">{{ $report->category->name }}</span>
    <span>@include('reports._priority') <span class="status">{{ $report->status }}</span></span>
  </header>
  <h1>{{ $report->title }}</h1>
  <dl class="facts">
    <dt>Location</dt><dd>{{ $report->address }}{{ $report->ward ? ' · '.$report->ward : '' }}</dd>
    <dt>Reported</dt><dd>{{ $report->created_at->format('d M Y') }}</dd>
    <dt>Tracking code</dt><dd><code>{{ $report->tracking_code }}</code></dd>
  </dl>
  <p class="body-text">{!! nl2br(e($report->description)) !!}</p>
  @if ($report->evidence_path)
    <figure><img src="{{ $report->evidenceUrl() }}" alt="Evidence photo for this report"></figure>
  @endif

  <h2>Do you know this problem?</h2>
  @if ($report->isClosed())
    <p class="muted">This report is {{ strtolower($report->status) }}, so it no longer takes votes.</p>
  @endif
  <div class="votes">
    @foreach (\App\Models\ReportVote::LABELS as $type => $label)
      @php($mine = in_array($type, $myVotes, true))
      <div class="vote {{ $mine ? 'on' : '' }}">
        <span class="vote-count">{{ $report->voteCount($type) }}</span>
        <span class="vote-label">{{ $label }}</span>
        @unless ($report->isClosed())
          @if ($mine)
            <form method="POST" action="{{ route('reports.votes.destroy', [$report, $type]) }}" class="inline">
              @csrf @method('DELETE')
              <button class="btn small ghost" type="submit">Take back</button>
            </form>
          @else
            <form method="POST" action="{{ route('reports.votes.store', $report) }}" class="inline">
              @csrf <input type="hidden" name="type" value="{{ $type }}">
              <button class="btn small" type="submit">Vote</button>
            </form>
          @endif
        @endunless
      </div>
    @endforeach
  </div>
  <p class="muted">“Still there” and “Already fixed” replace each other.</p>
</article>
@endsection
