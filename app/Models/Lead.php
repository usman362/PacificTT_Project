<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    protected $fillable = [
        'name', 'phone', 'email', 'electrical_experience', 'plc_experience',
        'program_id', 'preferred_date', 'preferred_session', 'source',
        'status', 'follow_up_at', 'notes', 'enrollment_id',
    ];

    protected function casts(): array
    {
        return [
            'preferred_date' => 'date',
            'follow_up_at'   => 'datetime',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function reference(): string
    {
        return 'LD-' . str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function statusLabel(): string
    {
        return ucwords(str_replace('_', ' ', $this->status));
    }
}
