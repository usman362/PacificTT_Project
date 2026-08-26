<?php

namespace App\Services;

use App\Models\ClassSession;
use App\Models\Program;
use Illuminate\Support\Carbon;

/**
 * Real seat availability.
 *
 * A dated class only gets a class_sessions row once someone actually books it,
 * so an unbooked date reports full capacity rather than requiring an admin to
 * pre-create every day. Seat counts are always derived from live enrolments —
 * never stored — so an expired hold frees its seat with no cleanup job.
 */
class SeatAvailability
{
    public function isOpenOn(Carbon $date): bool
    {
        return ! in_array($date->dayOfWeek, config('ptt.closed_weekdays'), true);
    }

    public function isPast(Carbon $date): bool
    {
        return $date->startOfDay()->isBefore(now()->startOfDay());
    }

    /**
     * @return array{open:bool, reason:?string, seats_remaining:int, capacity:int}
     */
    public function check(Program $program, string $slot, Carbon $date): array
    {
        $capacity = config('ptt.default_capacity');

        if (! $this->isOpenOn($date)) {
            return $this->closed('Sunday classes are not available. Please select Monday–Saturday.', $capacity);
        }

        if ($this->isPast($date)) {
            return $this->closed('That date has already passed. Please choose an upcoming date.', $capacity);
        }

        $session = $this->findSession($program, $slot, $date);

        if ($session === null) {
            // Nobody has booked this day yet.
            return ['open' => true, 'reason' => null, 'seats_remaining' => $capacity, 'capacity' => $capacity];
        }

        if (! $session->is_active) {
            return $this->closed('That class has been closed. Please choose another date.', $session->capacity);
        }

        $remaining = $session->seatsRemaining();

        return [
            'open'            => $remaining > 0,
            'reason'          => $remaining > 0 ? null : 'This class is full. Please choose another date or session.',
            'seats_remaining' => $remaining,
            'capacity'        => $session->capacity,
        ];
    }

    public function findSession(Program $program, string $slot, Carbon $date): ?ClassSession
    {
        return ClassSession::where('program_id', $program->id)
            ->where('label', $slot)
            ->whereDate('start_date', $date->toDateString())
            ->first();
    }

    /**
     * Get the dated class, creating it the first time somebody books it.
     */
    public function findOrCreateSession(Program $program, string $slot, Carbon $date): ClassSession
    {
        return ClassSession::firstOrCreate(
            [
                'program_id' => $program->id,
                'label'      => $slot,
                'start_date' => $date->toDateString(),
            ],
            [
                'capacity'  => config('ptt.default_capacity'),
                'is_active' => true,
            ]
        );
    }

    private function closed(string $reason, int $capacity): array
    {
        return ['open' => false, 'reason' => $reason, 'seats_remaining' => 0, 'capacity' => $capacity];
    }
}
