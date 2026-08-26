<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassSession extends Model
{
    use HasFactory;

    protected $fillable = ['program_id', 'label', 'start_date', 'capacity', 'is_active'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'is_active'  => 'boolean',
            'capacity'   => 'integer',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Seats taken = enrolments that are paid, waivered, or still inside a live
     * seat hold. Abandoned and expired holds release their seat automatically.
     */
    public function seatsTaken(): int
    {
        return $this->enrollments()->holdingSeat()->count();
    }

    public function seatsRemaining(): int
    {
        return max(0, $this->capacity - $this->seatsTaken());
    }

    public function isFull(): bool
    {
        return $this->seatsRemaining() < 1;
    }
}
