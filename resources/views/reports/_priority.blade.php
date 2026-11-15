{{-- F7 – priority badge --}}
<span class="priority p-{{ strtolower($report->priority_level) }}" title="Priority score {{ $report->priority_score }} / 100">
  {{ $report->priority_level }} · {{ $report->priority_score }}
</span>
