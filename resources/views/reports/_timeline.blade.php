{{-- F4 – status timeline. $showActors = true only on the admin page. --}}
<h2>Status history</h2>
<ol class="timeline">
  @foreach ($histories as $h)
    <li>
      <span class="when">{{ $h->created_at->format('d M Y, H:i') }}</span>
      <strong>
        @if ($h->from_status && $h->from_status !== $h->to_status)
          {{ $h->from_status }} &rarr; {{ $h->to_status }}
        @elseif ($h->from_status)
          {{ $h->to_status }} <span class="muted">(priority)</span>
        @else
          {{ $h->to_status }}
        @endif
      </strong>
      @if ($h->note)<span class="note">{{ $h->note }}</span>@endif
      @if (! empty($showActors) && $h->user)<span class="muted">by {{ $h->user->name }}</span>@endif
    </li>
  @endforeach
</ol>
