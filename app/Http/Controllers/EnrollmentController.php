<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEnrollmentRequest;
use App\Models\Enrollment;
use App\Models\Program;
use App\Services\SeatAvailability;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EnrollmentController extends Controller
{
    public function __construct(private readonly SeatAvailability $seats)
    {
    }

    /** Live seat count for a program + session + date. */
    public function availability(Request $request): JsonResponse
    {
        $data = $request->validate([
            'program_id'   => ['required', 'exists:programs,id'],
            'session_slot' => ['required', Rule::in(config('ptt.session_slots'))],
            'date'         => ['required', 'date'],
        ]);

        $program = Program::findOrFail($data['program_id']);
        $result  = $this->seats->check($program, $data['session_slot'], Carbon::parse($data['date']));

        return response()->json($result);
    }

    /**
     * Creates the enrolment and starts the seat hold. Re-checks availability
     * inside a transaction so two people cannot take the last seat at once.
     */
    public function store(StoreEnrollmentRequest $request): JsonResponse
    {
        $data    = $request->validated();
        $program = Program::findOrFail($data['program_id']);
        $date    = Carbon::parse($data['preferred_date']);

        try {
            $enrollment = DB::transaction(function () use ($data, $program, $date, $request) {
                $session = $this->seats->findOrCreateSession($program, $data['session_slot'], $date);

                // Lock the class row so concurrent bookings serialise here.
                $session = $session->newQuery()->lockForUpdate()->find($session->id);

                $check = $this->seats->check($program, $data['session_slot'], $date);
                if (! $check['open']) {
                    abort(422, $check['reason'] ?? 'That class is no longer available.');
                }

                return Enrollment::create([
                    'reference'             => Enrollment::nextReference(),
                    'name'                  => $data['name'],
                    'phone'                 => $data['phone'],
                    'email'                 => $data['email'],
                    'electrical_experience' => $data['electrical_experience'] ?? null,
                    'plc_experience'        => $data['plc_experience'] ?? null,
                    'program_id'            => $program->id,
                    'class_session_id'      => $session->id,
                    'preferred_date'        => $date->toDateString(),
                    'status'                => 'started',
                    'seat_hold_expires_at'  => now()->addMinutes(config('ptt.seat_hold_minutes')),
                    'tuition_cents'         => $program->price_cents,
                    'ip_address'            => $request->ip(),
                    'user_agent'            => substr((string) $request->userAgent(), 0, 500),
                ]);
            });
        } catch (\Illuminate\Http\Exceptions\HttpResponseException $e) {
            throw $e;
        }

        return response()->json([
            'ok'         => true,
            'reference'  => $enrollment->reference,
            'enrollment' => [
                'id'             => $enrollment->id,
                'reference'      => $enrollment->reference,
                'name'           => $enrollment->name,
                'program'        => $program->name,
                'program_price'  => $program->price_label,
                'session_slot'   => $data['session_slot'],
                'preferred_date' => $enrollment->preferred_date->toDateString(),
                'hold_expires'   => $enrollment->seat_hold_expires_at->toIso8601String(),
                'hold_seconds'   => config('ptt.seat_hold_minutes') * 60,
            ],
        ], 201);
    }
}
