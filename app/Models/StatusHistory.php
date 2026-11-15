<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Feature F4 – one entry in a report's timeline. */
class StatusHistory extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'from_status', 'to_status', 'note'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
