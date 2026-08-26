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
        'course', 'completed_on', 'photo_path', 'is_public',
    ];

    protected function casts(): array
    {
        return [
            'completed_on' => 'date',
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
}
