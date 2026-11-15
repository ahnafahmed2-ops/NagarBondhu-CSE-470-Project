@php($selected = (int) old('category_id', $report->category_id ?? 0))
<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="report-form" novalidate>
  @csrf
  @isset($report) @method('PUT') @endisset

  <fieldset>
    <legend>What kind of problem is it?</legend>
    <div class="cat-grid">
      @foreach ($categories as $c)
        <label class="cat-option">
          <input type="radio" name="category_id" value="{{ $c->id }}" @checked($selected === (int) $c->id) required>
          <span><strong>{{ $c->name }}</strong><small>{{ $c->description }}</small></span>
        </label>
      @endforeach
    </div>
    @error('category_id')<p class="field-error">{{ $message }}</p>@enderror
  </fieldset>

  <label>Short title
    <input name="title" value="{{ old('title', $report->title ?? '') }}" maxlength="120" required placeholder="e.g. Deep pothole in front of Rahman Market">
  </label>
  @error('title')<p class="field-error">{{ $message }}</p>@enderror

  <label>Describe the problem
    <textarea name="description" rows="5" maxlength="2000" required data-counter="desc-count"
      placeholder="What is wrong, how long has it been like this, who does it affect?">{{ old('description', $report->description ?? '') }}</textarea>
    <span class="counter"><span id="desc-count">0</span> / 2000</span>
  </label>
  @error('description')<p class="field-error">{{ $message }}</p>@enderror

  <div class="two-col">
    <label>Where is it? <input name="address" value="{{ old('address', $report->address ?? '') }}" maxlength="200" required placeholder="Road, landmark, area"></label>
    <label>Ward / area <span class="muted">(optional)</span> <input name="ward" value="{{ old('ward', $report->ward ?? '') }}" maxlength="60" placeholder="e.g. Ward 17"></label>
  </div>
  @error('address')<p class="field-error">{{ $message }}</p>@enderror
  @error('ward')<p class="field-error">{{ $message }}</p>@enderror

  <label>Photo evidence <span class="muted">(optional · JPG, PNG or WebP · up to 5 MB)</span>
    <input type="file" name="evidence" id="evidence" accept="image/jpeg,image/png,image/webp">
  </label>
  @error('evidence')<p class="field-error">{{ $message }}</p>@enderror

  @if (isset($report) && $report->evidence_path)
    <div class="current-evidence">
      <img src="{{ $report->evidenceUrl() }}" alt="Current evidence photo">
      <label class="check"><input type="checkbox" name="remove_evidence" value="1"> Remove this photo (or choose a new one above to replace it)</label>
    </div>
  @endif
  <img id="evidence-preview" class="preview" alt="Preview of the selected photo" hidden>

  <div class="actions">
    <button class="btn" type="submit">{{ $submitLabel }}</button>
    <a class="btn ghost" href="{{ $cancelUrl }}">Cancel</a>
  </div>
</form>
