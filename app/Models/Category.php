<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'description', 'severity'];

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }
}
