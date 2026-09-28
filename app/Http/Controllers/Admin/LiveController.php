<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyUpdate;
use App\Models\Mission;
use App\Models\PublishedMetric;
use App\Models\Setting;
use App\Services\SeatAvailability;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * The live business channel shared by the Assistant Command screen, the
 * Owner dashboard and the Staff Progress display. The mockups kept this in
 * one browser's localStorage; here it lives in the database and every screen
 * polls the same state.
 */
class LiveController extends Controller
{
    public function __construct(private SeatAvailability $seats) {}

    public function state(): JsonResponse
    {
        return response()->json($this->payload());
    }

    public function publishMetrics(Request $request): JsonResponse
    {
        $data = $request->validate([
            'cash'        => ['required', 'numeric', 'min:0', 'max:9999999'],
            'enrollments' => ['required', 'integer', 'min:0', 'max:100000'],
            'utilization' => ['required', 'integer', 'min:0', 'max:100'],
            'attendance'  => ['required', 'integer', 'min:0', 'max:100'],
            'completion'  => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        PublishedMetric::create([
            'cash_cents'   => (int) round($data['cash'] * 100),
            'enrollments'  => $data['enrollments'],
            'utilization'  => $data['utilization'],
            'attendance'   => $data['attendance'],
            'completion'   => $data['completion'],
            'published_by' => $request->user()->id,
        ]);

        return $this->changed();
    }

    public function pushUpdate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message'    => ['required', 'string', 'max:500'],
            'type'       => ['required', Rule::in(CompanyUpdate::TYPES)],
            'department' => ['required', Rule::in(CompanyUpdate::DEPARTMENTS)],
        ]);

        CompanyUpdate::create($data + ['published_by' => $request->user()->id]);

        return $this->changed();
    }

    public function assignMission(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'      => ['required', 'string', 'max:200'],
            'owner'      => ['required', 'string'],
            'department' => ['required', Rule::in(Mission::DEPARTMENTS)],
            'deadline'   => ['required', 'date', 'after:now'],
        ], [
            'deadline.after' => 'The deadline must be in the future.',
        ]);

        $owner = collect(Mission::assignableOwners())->firstWhere('id', $data['owner']);
        if (! $owner) {
            return response()->json(['message' => 'Only active registered users can receive assignments.'], 422);
        }
        [$type, $id] = explode(':', $owner['id']);

        Mission::create([
            'title'       => $data['title'],
            'owner_type'  => $type,
            'owner_id'    => (int) $id,
            'owner_name'  => $owner['name'],
            'department'  => $data['department'],
            'deadline'    => Carbon::parse($data['deadline']),
            'status'      => 'open',
            'assigned_by' => $request->user()->id,
        ]);

        return $this->changed();
    }

    public function updateMission(Request $request, Mission $mission): JsonResponse
    {
        $data = $request->validate([
            'action'   => ['required', Rule::in(['complete', 'reschedule'])],
            'deadline' => ['required_if:action,reschedule', 'nullable', 'date', 'after:now'],
        ], [
            'deadline.after' => 'The new deadline must be in the future.',
        ]);

        if ($data['action'] === 'complete') {
            // A mission can only be completed while it is still open; once its
            // deadline passes it is Noise until someone reschedules it.
            Mission::settleExpired();
            $mission->refresh();
            if ($mission->status !== 'open') {
                return response()->json(['message' => 'This mission missed its deadline and is now Noise. Reschedule it to reopen it.'], 422);
            }
            $mission->update(['status' => 'complete', 'completed_at' => now()]);
        } else {
            // Rescheduling returns the task to Open Mission Focus status.
            $mission->update([
                'deadline'     => Carbon::parse($data['deadline']),
                'status'       => 'open',
                'completed_at' => null,
            ]);
        }

        return $this->changed();
    }

    public function deleteMission(Mission $mission): JsonResponse
    {
        $mission->delete();

        return $this->changed();
    }

    /* ── helpers ─────────────────────────────────────────────────────── */

    private function changed(): JsonResponse
    {
        $this->bumpRevision();

        return response()->json($this->payload());
    }

    private function bumpRevision(): void
    {
        Setting::put('live_revision', (string) ((int) Setting::get('live_revision', 1) + 1));
    }

    public function payload(): array
    {
        // A mission turning into Noise is a change every screen must see.
        if (Mission::settleExpired() > 0) {
            $this->bumpRevision();
        }

        $m = PublishedMetric::current();

        return [
            'revision'  => (int) Setting::get('live_revision', 1),
            'updatedAt' => now()->toIso8601String(),
            'metrics'   => [
                'cash'        => $m ? $m->cash_cents / 100 : 0,
                'enrollments' => $m->enrollments ?? 0,
                'utilization' => $m->utilization ?? 0,
                'attendance'  => $m->attendance ?? 0,
                'completion'  => $m->completion ?? 0,
                'publishedAt' => $m?->created_at?->toIso8601String(),
            ],
            'tasks' => Mission::orderBy('deadline')->get()->map(fn ($t) => [
                'id'         => $t->id,
                'title'      => $t->title,
                'ownerId'    => $t->owner_type.':'.$t->owner_id,
                'owner'      => $t->owner_name,
                'department' => $t->department,
                'deadline'   => $t->deadline->toIso8601String(),
                'status'     => $t->status,
            ])->all(),
            'updates' => CompanyUpdate::latest('id')->limit(50)->get()->map(fn ($u) => [
                'id'         => $u->id,
                'message'    => $u->message,
                'type'       => $u->type,
                'department' => $u->department,
                'createdAt'  => $u->created_at->toIso8601String(),
            ])->all(),
            'schedule' => $this->todaySchedule(),
            'owners'   => Mission::assignableOwners(),
        ];
    }

    /**
     * Today's sessions per instructor. A session's students are shared among
     * the instructors covering it — whole students, the remainder going to
     * the first instructors — exactly as the Assistant schedule shows them.
     */
    private function todaySchedule(): array
    {
        $date = today();
        $per  = (int) config('ptt.seats_per_instructor');
        $byInstructor = [];

        foreach (config('ptt.session_slots') as $slot) {
            $covering = $this->seats->isOpenOn($date) ? $this->seats->instructorsForSlot($date, $slot) : collect();
            $n = $covering->count();
            if (! $n) {
                continue;
            }
            $booked = $this->seats->bookedForSlot($date, $slot);
            $tiers  = implode(' / ', $this->seats->tiersForSlot($date, $slot));
            $base = intdiv($booked, $n);
            $rem  = $booked % $n;

            foreach ($covering->values() as $i => $inst) {
                $b = min($per, $base + ($i < $rem ? 1 : 0));
                $byInstructor[$inst->id] ??= ['id' => $inst->id, 'name' => $inst->name, 'sessions' => []];
                $byInstructor[$inst->id]['sessions'][] = [
                    'time'      => $slot,
                    'program'   => $tiers ?: 'No students booked',
                    'booked'    => $b,
                    'capacity'  => $per,
                    'available' => max(0, $per - $b),
                ];
            }
        }

        return [
            'date'        => $date->toDateString(),
            'instructors' => array_values($byInstructor),
        ];
    }
}
