<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OwnerObjective extends Model
{
    public const CATEGORIES = ['Operations', 'Enrollment', 'Finance', 'Training', 'Growth', 'Personal Leadership'];

    protected $fillable = ['title', 'category', 'deadline', 'status', 'completed_at', 'created_by'];

    protected function casts(): array
    {
        return ['deadline' => 'datetime', 'completed_at' => 'datetime'];
    }

    /** Open objectives past their deadline are Noise ("missed"). */
    public static function settleExpired(): int
    {
        return static::where('status', 'open')
            ->where('deadline', '<=', now())
            ->update(['status' => 'missed', 'updated_at' => now()]);
    }
}
