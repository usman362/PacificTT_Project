<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublishedMetric extends Model
{
    protected $fillable = ['cash_cents', 'enrollments', 'utilization', 'attendance', 'completion', 'published_by'];

    public static function current(): ?self
    {
        return static::latest('id')->first();
    }
}
