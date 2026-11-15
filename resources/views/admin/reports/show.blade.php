@extends('layouts.app')
@section('title', 'Admin – '.$report->tracking_code)
@section('content')
<p><a href="{{ route('admin.reports.index') }}">&larr; All reports</a></p>
<article class="report">
  <header>
    <span class="plate">{{ $report->category->name }}</span>
    <span>@include('reports._priority') <span class="status">{{ $report->status }}</span></span>
  </header>
  <h1>{{ $report->title }}</h1>
  <dl class="facts">
    <dt>Tracking code</dt><dd><code>{{ $report->tracking_code }}</code></dd>
    <dt>Reported by</dt><dd>{{ $report->user->name }} ({{ $report->user->email }})</dd>
    <dt>Location</dt><dd>{{ $report->address }}{{ $report->ward ? ' · '.$report->ward : '' }}</dd>
    <dt>Community</dt><dd>
      @foreach ($labels as $type => $label){{ $report->voteCount($type) }} “{{ $label }}”@if (! $loop->last) · @endif @endforeach
    </dd>
  </dl>
  <p class="body-text">{!! nl2br(e($report->description)) !!}</p>
  @if ($report->evidence_path)
    <figure><img src="{{ $report->evidenceUrl() }}" alt="Evidence photo for this report"></figure>
  @endif

  <div class="admin-panels">
    <section>
      <h2>Change status</h2>
      @if ($report->allowedNextStatuses())
        <form method="POST" action="{{ route('admin.reports.status', $report) }}">
          @csrf
          <label>Move to
            <select name="status" required>
              @foreach ($report->allowedNextStatuses() as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach
            </select>
          </label>
          <label>Note <span class="muted">(optional, shown publicly)</span>
            <input name="note" maxlength="500" value="{{ old('note') }}">
          </label>
          @error('note')<p class="field-error">{{ $message }}</p>@enderror
          <button class="btn" type="submit">Update status</button>
        </form>
      @else
        <p class="muted">{{ $report->status }} is a final status.</p>
      @endif
    </section>

    <section>
      <h2>Priority</h2>
      <p>Automatic score: <strong>{{ $report->priority_score }}</strong> / 100
        @if ($report->priority_overridden) · <span class="muted">level set by hand</span>@endif</p>
      <form method="POST" action="{{ route('admin.reports.priority', $report) }}">
        @csrf
        <label>Set level
          <select name="priority_level">
            @foreach ($levels as $l)<option value="{{ $l }}" @selected($report->priority_level === $l)>{{ $l }}</option>@endforeach
          </select>
        </label>
        <label>Reason <input name="reason" maxlength="300" required value="{{ old('reason') }}"></label>
        @error('reason')<p class="field-error">{{ $message }}</p>@enderror
        <button class="btn" type="submit">Override priority</button>
      </form>
      @if ($report->priority_overridden)
        <form method="POST" action="{{ route('admin.reports.priority.reset', $report) }}" class="inline">
          @csrf @method('DELETE')
          <button class="btn ghost small" type="submit">Return to automatic</button>
        </form>
      @endif
    </section>
  </div>

  @include('reports._timeline', ['histories' => $report->histories, 'showActors' => true])
</article>
@endsection
