<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Feature F5 – one community vote. A user has at most one vote of each type per report. */
class ReportVote extends Model
{
    public const AFFECTED = 'affected';       // "This affects me too"
    public const STILL_THERE = 'still_there'; // "I saw it – still there"
    public const FIXED = 'fixed';             // "It is already fixed"

    public const LABELS = [
        self::AFFECTED    => 'This affects me too',
        self::STILL_THERE => 'I saw it – still there',
        self::FIXED       => 'It is already fixed',
    ];

    protected $fillable = ['user_id', 'type'];

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
