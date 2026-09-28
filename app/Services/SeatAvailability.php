<?php

namespace App\Services;

use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\Program;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use App\Models\Instructor;

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
    public function check(Program $program, string $slot, Carbon $date, ?Enrollment $ignore = null): array
    {
        if (! $this->isOpenOn($date)) {
            return $this->closed('Sunday classes are not available. Please select Monday–Saturday.', 0);
        }

        if ($this->isPast($date)) {
            return $this->closed('That date has already passed. Please choose an upcoming date.', 0);
        }

        // Capacity comes from who is actually rostered for that session.
        $capacity = $this->capacityForSlot($date, $slot);

        if ($capacity === 0) {
            return $this->closed('No instructor is available for that session. Please choose another date or time.', 0);
        }

        $session = $this->findSession($program, $slot, $date);

        if ($session && ! $session->is_active) {
            return $this->closed('That class has been closed. Please choose another date.', $capacity);
        }

        $booked = $this->bookedForSlot($date, $slot);

        // When an existing booking is being moved, it must not block itself.
        if ($ignore
            && $ignore->preferred_date?->toDateString() === $date->toDateString()
            && $ignore->classSession?->label === $slot) {
            $booked = max(0, $booked - 1);
        }

        $remaining = max(0, $capacity - $booked);

        return [
            'open'            => $remaining > 0,
            'reason'          => $remaining > 0 ? null : 'This class is full. Please choose another date or session.',
            'seats_remaining' => $remaining,
            'capacity'        => $capacity,
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

    /* ── Instructor-driven capacity ─────────────────────────────────────
       The console's rule: every instructor available for a session adds
       `seats_per_instructor` seats to it. No instructor, no seats.        */

    public function instructorsForSlot(Carbon $date, string $slot): Collection
    {
        [$start, $end] = $this->slotWindow($slot);
        $weekday = $date->dayOfWeekIso;          // 1 = Mon … 7 = Sun

        return Instructor::with('availability')->get()
            ->filter(fn (Instructor $i) => $i->coversSlot($weekday, $start, $end))
            ->values();
    }

    public function capacityForSlot(Carbon $date, string $slot): int
    {
        if (! $this->isOpenOn($date)) {
            return 0;
        }

        return $this->instructorsForSlot($date, $slot)->count()
             * (int) config('ptt.seats_per_instructor');
    }

    public function bookedForSlot(Carbon $date, string $slot): int
    {
        return Enrollment::whereDate('preferred_date', $date)
            ->whereHas('classSession', fn ($q) => $q->where('label', $slot))
            ->whereIn('status', $this->holdingStatuses())
            ->count();
    }

    /** Programme tiers ("Core", "Advanced") with seats held in this session. */
    public function tiersForSlot(Carbon $date, string $slot): array
    {
        $tiers = config('ptt.program_tiers', []);

        return Enrollment::whereDate('preferred_date', $date)
            ->whereHas('classSession', fn ($q) => $q->where('label', $slot))
            ->whereIn('status', $this->holdingStatuses())
            ->with('program:id,slug,name')
            ->get()
            ->map(fn ($e) => $tiers[$e->program?->slug] ?? $e->program?->name)
            ->filter()->unique()->sort()->values()->all();
    }

    public function capacityForDate(Carbon $date): int
    {
        return collect(config('ptt.session_slots'))
            ->sum(fn ($slot) => $this->capacityForSlot($date, $slot));
    }

    public function bookedForDate(Carbon $date): int
    {
        return Enrollment::whereDate('preferred_date', $date)
            ->whereIn('status', $this->holdingStatuses())
            ->count();
    }

    public function bookedForProgramOnDate(Carbon $date, string $programSlug): int
    {
        return Enrollment::whereDate('preferred_date', $date)
            ->whereIn('status', $this->holdingStatuses())
            ->whereHas('program', fn ($q) => $q->where('slug', $programSlug))
            ->count();
    }

    public function capacityForMonth(Carbon $month): int
    {
        $total = 0;
        $day   = $month->copy()->startOfMonth();

        while ($day->month === $month->month) {
            $total += $this->capacityForDate($day);
            $day->addDay();
        }

        return $total;
    }

    public function bookedForMonth(Carbon $month): int
    {
        return Enrollment::whereMonth('preferred_date', $month->month)
            ->whereYear('preferred_date', $month->year)
            ->whereIn('status', $this->holdingStatuses())
            ->count();
    }

    /** "8:00 AM – 12:00 PM" → ["08:00", "12:00"] */
    private function slotWindow(string $slot): array
    {
        if (! preg_match('/(\d{1,2}:\d{2}\s*[AP]M).*?(\d{1,2}:\d{2}\s*[AP]M)/i', $slot, $m)) {
            return ['00:00', '23:59'];
        }

        return [
            Carbon::parse($m[1])->format('H:i'),
            Carbon::parse($m[2])->format('H:i'),
        ];
    }

    /** Statuses that occupy a seat. */
    public function holdingStatuses(): array
    {
        return ['started', 'waiver_signed', 'deposit_paid', 'paid', 'completed'];
    }
}
