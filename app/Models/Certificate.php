<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'enrollment_id', 'certificate_number', 'student_name',
        'course', 'completed_on', 'photo_path', 'is_public', 'enrollment_id', 'instructor_id', 'status', 'issued_at',
    ];

    protected function casts(): array
    {
        return [
            'completed_on' => 'date',
            'issued_at'    => 'datetime',
            'is_public'    => 'boolean',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? Storage::url($this->photo_path) : null;
    }


    public function instructor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }

    /** PTT-YYYY-NNNNN, unique and never reused. */
    public static function nextNumber(): string
    {
        $year = now()->year;

        $last = static::where('certificate_number', 'like', "PTT-{$year}-%")
            ->orderByDesc('certificate_number')
            ->value('certificate_number');

        $next = $last ? ((int) substr($last, -5)) + 1 : 1;

        return sprintf('PTT-%d-%05d', $year, $next);
    }
}
