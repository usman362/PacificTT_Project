<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstructorAvailability extends Model
{
    protected $table = 'instructor_availability';
    public $timestamps = false;

    protected $fillable = ['instructor_id', 'weekday', 'is_available', 'starts_at', 'ends_at'];

    protected function casts(): array
    {
        return ['is_available' => 'boolean'];
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }
}
