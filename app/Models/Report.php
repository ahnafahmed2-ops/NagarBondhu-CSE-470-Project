<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use InvalidArgumentException;

class Report extends Model
{
    public const STATUS_SUBMITTED = 'Submitted';

    /**
     * Feature F4 – the status workflow. Each status lists the statuses it may move to.
     * Rejected and Merged are end states.
     */
    public const TRANSITIONS = [
        'Submitted'             => ['Under Review', 'Rejected'],
        'Under Review'          => ['Verified', 'Rejected', 'Merged'],
        'Verified'              => ['Assigned'],
        'Assigned'              => ['In Progress'],
        'In Progress'           => ['Awaiting Verification'],
        'Awaiting Verification' => ['Resolved', 'In Progress'],
        'Resolved'              => ['Reopened'],
        'Reopened'              => ['Assigned'],
        'Rejected'              => [],
        'Merged'                => [],
    ];

    /** Reports in these statuses are finished: no more votes, not shown on the community page. */
    public const CLOSED_STATUSES = ['Resolved', 'Rejected', 'Merged'];

    /** Feature F7 – priority levels, highest first, with the minimum score for each. */
    public const PRIORITY_LEVELS = ['Critical' => 70, 'High' => 50, 'Medium' => 30, 'Low' => 0];

    /** Feature F7 – words that make a problem more dangerous (English and Bangla). */
    public const HAZARD_WORDS = ['child', 'children', 'school', 'hospital', 'accident', 'injured', 'electric',
        'shock', 'fell', 'danger', 'deep', 'night', 'শিশু', 'স্কুল', 'হাসপাতাল', 'দুর্ঘটনা', 'বিপদ'];

    protected $fillable = [
        'category_id', 'title', 'description', 'address', 'ward', 'evidence_path',
    ];

    protected $casts = [
        'priority_overridden' => 'boolean',
    ];

    protected static function booted(): void
    {
        // F4 – every report gets a public tracking code such as NB-261115-K7Q2M.
        static::creating(function (Report $report) {
            $report->status ??= self::STATUS_SUBMITTED;
            do {
                $code = 'NB-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
            } while (self::where('tracking_code', $code)->exists());
            $report->tracking_code = $code;
        });

        // F4 – the first entry of the timeline.
        static::created(function (Report $report) {
            $report->histories()->create([
                'user_id' => $report->user_id, 'to_status' => $report->status, 'note' => 'Report submitted',
            ]);
        });

        // F7 – keep the priority up to date whenever the report is saved.
        static::saving(function (Report $report) {
            $report->priority_score = $report->calculatePriorityScore();
            if (! $report->priority_overridden) {
                $report->priority_level = self::levelForScore($report->priority_score);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(ReportVote::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(StatusHistory::class)->orderBy('created_at')->orderBy('id');
    }

    /** Open reports, i.e. not resolved, rejected or merged. */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', self::CLOSED_STATUSES);
    }

    /** A citizen may change or delete a report only until staff start handling it. */
    public function isEditable(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    public function isClosed(): bool
    {
        return in_array($this->status, self::CLOSED_STATUSES, true);
    }

    public function evidenceUrl(): ?string
    {
        return $this->evidence_path ? asset('storage/'.$this->evidence_path) : null;
    }

    // ---------------------------------------------------------------- F4 – status workflow

    /** @return list<string> */
    public function allowedNextStatuses(): array
    {
        return self::TRANSITIONS[$this->status] ?? [];
    }

    public function canMoveTo(string $status): bool
    {
        return in_array($status, $this->allowedNextStatuses(), true);
    }

    /** The only way the status changes: refuses illegal moves and records every change. */
    public function changeStatus(string $to, User $actor, ?string $note = null): void
    {
        if (! $this->canMoveTo($to)) {
            throw new InvalidArgumentException("A report cannot move from {$this->status} to {$to}.");
        }

        $from = $this->status;
        $this->status = $to;
        $this->save();

        $this->histories()->create([
            'user_id' => $actor->id, 'from_status' => $from, 'to_status' => $to, 'note' => $note,
        ]);
    }

    // ---------------------------------------------------------------- F5 – community votes

    public function voteCount(string $type): int
    {
        return $this->relationLoaded('votes')
            ? $this->votes->where('type', $type)->count()
            : $this->votes()->where('type', $type)->count();
    }

    // ---------------------------------------------------------------- F7 – priority

    /**
     * score (0–100) = category severity (1–5) × 12                         max 60
     *               + supporters: min(20, 6 × log2(1 + "affects me" votes)) max 20
     *               + "still there" confirmations × 2                       max 10
     *               + 10 if the title or description mentions a hazard
     */
    public function calculatePriorityScore(): int
    {
        $severity = (int) (Category::whereKey($this->category_id)->value('severity') ?? 3);
        $affected = $this->exists ? $this->votes()->where('type', ReportVote::AFFECTED)->count() : 0;
        $stillThere = $this->exists ? $this->votes()->where('type', ReportVote::STILL_THERE)->count() : 0;

        $score = $severity * 12
            + min(20, (int) round(6 * log(1 + $affected, 2)))
            + min(10, $stillThere * 2)
            + ($this->mentionsHazard() ? 10 : 0);

        return min(100, $score);
    }

    public static function levelForScore(int $score): string
    {
        foreach (self::PRIORITY_LEVELS as $level => $minimum) {
            if ($score >= $minimum) {
                return $level;
            }
        }

        return 'Low';
    }

    public function mentionsHazard(): bool
    {
        $text = mb_strtolower($this->title.' '.$this->description);
        foreach (self::HAZARD_WORDS as $word) {
            if (str_contains($text, $word)) {
                return true;
            }
        }

        return false;
    }

    /** Admin sets the level by hand; it then stays fixed when votes change. */
    public function overridePriority(string $level, User $actor, string $reason): void
    {
        $old = $this->priority_level;
        $this->priority_level = $level;
        $this->priority_overridden = true;
        $this->save();

        $this->histories()->create([
            'user_id' => $actor->id, 'from_status' => $this->status, 'to_status' => $this->status,
            'note' => "Priority changed from {$old} to {$level}: {$reason}",
        ]);
    }

    /** Hand the level back to the automatic calculation. */
    public function clearPriorityOverride(User $actor): void
    {
        $this->priority_overridden = false;
        $this->save();

        $this->histories()->create([
            'user_id' => $actor->id, 'from_status' => $this->status, 'to_status' => $this->status,
            'note' => "Priority returned to automatic ({$this->priority_level})",
        ]);
    }
}
