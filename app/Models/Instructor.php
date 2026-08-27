<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Instructor extends Model
{
    protected $fillable = [
        'name', 'email', 'phone', 'daily_rate_cents', 'status', 'courses',
    ];

    public function availability(): HasMany
    {
        return $this->hasMany(InstructorAvailability::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /** Does this instructor cover the given weekday and session window? */
    public function coversSlot(int $weekday, string $startsAt, string $endsAt): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        $day = $this->availability->firstWhere('weekday', $weekday);

        if (! $day || ! $day->is_available) {
            return false;
        }

        // MySQL hands back "08:00:00" while the slot window is "08:00", and a
        // plain string compare makes the longer value look *later*. Compare
        // minutes-since-midnight instead.
        $toMinutes = static function (string $t): int {
            [$h, $m] = array_pad(explode(':', $t), 2, '0');

            return ((int) $h) * 60 + (int) $m;
        };

        return $toMinutes((string) $day->starts_at) <= $toMinutes($startsAt)
            && $toMinutes((string) $day->ends_at)   >= $toMinutes($endsAt);
    }
}
