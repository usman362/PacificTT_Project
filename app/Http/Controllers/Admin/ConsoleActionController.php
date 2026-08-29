<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Instructor;
use App\Models\InstructorAvailability;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\Waiver;
use App\Services\SeatAvailability;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ConsoleActionController extends Controller
{
    private const WEEKDAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

    public function saveInstructor(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id'       => ['nullable', 'integer', 'exists:instructors,id'],
            'name'     => ['required', 'string', 'max:120'],
            'email'    => ['nullable', 'email', 'max:180'],
            'phone'    => ['nullable', 'string', 'max:40'],
            'rate'     => ['nullable', 'numeric', 'min:0'],
            'status'   => ['required', 'in:Active,Inactive'],
            'course'   => ['required', 'string'],
            'avail'    => ['array'],
        ]);

        $instructor = Instructor::updateOrCreate(
            ['id' => $data['id'] ?? null],
            [
                'name'             => $data['name'],
                'email'            => $data['email'] ?? null,
                'phone'            => $data['phone'] ?? null,
                'daily_rate_cents' => (int) round(($data['rate'] ?? 1200) * 100),
                'status'           => strtolower($data['status']),
                'courses'          => match ($data['course']) {
                    'Core Only'     => 'core',
                    'Advanced Only' => 'advanced',
                    default         => 'both',
                },
            ]
        );

        foreach (self::WEEKDAYS as $index => $label) {
            $row = $data['avail'][$label] ?? null;

            InstructorAvailability::updateOrCreate(
                ['instructor_id' => $instructor->id, 'weekday' => $index + 1],
                [
                    'is_available' => (bool) ($row['on'] ?? false),
                    'starts_at'    => $row['start'] ?? '08:00',
                    'ends_at'      => $row['end']   ?? '20:00',
                ]
            );
        }

        return response()->json(['ok' => true, 'id' => $instructor->id]);
    }

    public function deleteInstructor(Request $request): JsonResponse
    {
        $request->validate(['id' => ['required', 'integer', 'exists:instructors,id']]);
        Instructor::whereKey($request->integer('id'))->delete();

        return response()->json(['ok' => true]);
    }

    public function saveLead(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id'         => ['nullable', 'integer', 'exists:leads,id'],
            'name'       => ['required', 'string', 'max:120'],
            'phone'      => ['required', 'string', 'max:40'],
            'email'      => ['nullable', 'email', 'max:180'],
            'program'    => ['nullable', 'string'],
            'date'       => ['nullable', 'date'],
            'session'    => ['nullable', 'string'],
            'source'     => ['nullable', 'string', 'max:60'],
            'status'     => ['required', 'string'],
            'follow'     => ['nullable', 'string'],
            'electrical' => ['nullable', 'string', 'max:60'],
            'plc'        => ['nullable', 'string', 'max:60'],
            'notes'      => ['nullable', 'string'],
        ]);

        $lead = Lead::updateOrCreate(
            ['id' => $data['id'] ?? null],
            [
                'name'                  => $data['name'],
                'phone'                 => $data['phone'],
                'email'                 => $data['email'] ?? null,
                'electrical_experience' => $data['electrical'] ?? null,
                'plc_experience'        => $data['plc'] ?? null,
                'program_id'            => \App\Models\Program::where('name', $data['program'] ?? '')->value('id'),
                'preferred_date'        => ($data['date'] ?? null) ?: null,
                'preferred_session'     => $data['session'] ?? null,
                'source'                => $data['source'] ?? 'Other',
                'status'                => str_replace(' ', '_', strtolower($data['status'])),
                'follow_up_at'          => $this->parseFollowUp($data['follow'] ?? null),
                'notes'                 => $data['notes'] ?? null,
            ]
        );

        return response()->json(['ok' => true, 'id' => $lead->id]);
    }

    public function acceptWaiver(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id'        => ['required', 'integer', 'exists:waivers,id'],
            'staff'     => ['required', 'string', 'max:120'],
            'signature' => ['required', 'string', 'max:120'],
            'date'      => ['required', 'date'],
        ]);

        $waiver = Waiver::findOrFail($data['id']);

        if ($waiver->staff_accepted_on) {
            return response()->json(['ok' => false, 'message' => 'This waiver has already been accepted.'], 422);
        }

        $waiver->update([
            'staff_name'        => $data['staff'],
            'staff_signature'   => $data['signature'],
            'staff_accepted_on' => $data['date'],
            'staff_user_id'     => $request->user()->id,
            'needs_review'      => false,
        ]);

        return response()->json(['ok' => true]);
    }

    /** Zelle is only money once a person confirms it arrived. */
    public function verifyZelle(Request $request): JsonResponse
    {
        $data = $request->validate(['id' => ['required', 'integer', 'exists:payments,id']]);

        $payment = Payment::findOrFail($data['id']);

        if ($payment->status === 'succeeded') {
            return response()->json(['ok' => false, 'message' => 'Already verified.'], 422);
        }

        $payment->update([
            'status'      => 'succeeded',
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
            'paid_at'     => now(),
        ]);

        $payment->enrollment?->refreshPaymentStatus();

        return response()->json(['ok' => true]);
    }

    /** Live capacity for a date, straight from instructor availability. */
    public function schedule(Request $request, SeatAvailability $seats): JsonResponse
    {
        $date = Carbon::parse($request->query('date', today()->toDateString()));

        $sessions = collect(config('ptt.session_slots'))->map(function ($slot) use ($seats, $date) {
            $cap    = $seats->capacityForSlot($date, $slot);
            $booked = $seats->bookedForSlot($date, $slot);

            return [
                'slot'        => $slot,
                'instructors' => $seats->instructorsForSlot($date, $slot)
                                    ->map(fn ($i) => ['id' => $i->id, 'name' => $i->name])->values(),
                'capacity'    => $cap,
                'booked'      => $booked,
                'available'   => max(0, $cap - $booked),
            ];
        })->values();

        return response()->json([
            'date'      => $date->toDateString(),
            'closed'    => in_array($date->dayOfWeek, config('ptt.closed_weekdays'), true),
            'sessions'  => $sessions,
            'capacity'  => $sessions->sum('capacity'),
            'booked'    => $sessions->sum('booked'),
        ]);
    }


    /** Issue a single-use onboarding link and email the instructor. */
    public function inviteInstructor(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:180'],
            'name'  => ['nullable', 'string', 'max:120'],
        ]);

        $existing = \App\Models\InstructorInvitation::where('email', $data['email'])
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->first();

        $invite = $existing ?: \App\Models\InstructorInvitation::issue(
            $data['email'], $data['name'] ?? null, $request->user()->id
        );

        return response()->json([
            'ok'      => true,
            'link'    => route('instructor.onboarding.show', ['token' => $invite->token]),
            'reused'  => (bool) $existing,
            'expires' => $invite->expires_at->format('M j, Y'),
        ]);
    }

    private function parseFollowUp(?string $value): ?Carbon
    {
        if (! $value || $value === 'Not scheduled') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
