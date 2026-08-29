<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\Instructor;
use App\Models\Lead;
use App\Models\Program;
use App\Models\Setting;
use App\Services\SeatAvailability;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ConsoleCrudController extends Controller
{
    public function __construct(private SeatAvailability $seats) {}

    /* ── Enrollments ───────────────────────────────────────────────── */

    /** Everything the edit modal needs for one enrolment. */
    public function showEnrollment(Enrollment $enrollment): JsonResponse
    {
        $enrollment->load(['program', 'classSession', 'waiver', 'payments']);

        return response()->json([
            'id'         => $enrollment->id,
            'reference'  => $enrollment->reference,
            'name'       => $enrollment->name,
            'phone'      => $enrollment->phone,
            'email'      => $enrollment->email,
            'electrical' => $enrollment->electrical_experience,
            'plc'        => $enrollment->plc_experience,
            'program_id' => $enrollment->program_id,
            'session'    => $enrollment->classSession?->label,
            'date'       => $enrollment->preferred_date?->toDateString(),
            'status'     => $enrollment->status,
            'tuition'    => $enrollment->tuition_cents / 100,
            'paid'       => $enrollment->paidCents() / 100,
            'balance'    => $enrollment->balanceCents() / 100,
            'waiver'     => $enrollment->waiver ? [
                'legal_name'   => $enrollment->waiver->legal_name,
                'address'      => $enrollment->waiver->address,
                'emergency'    => $enrollment->waiver->emergency_contact,
                'emergency_ph' => $enrollment->waiver->emergency_phone,
                'signed_at'    => $enrollment->waiver->signed_at?->format('M j, Y · g:i A'),
                'signature'    => $enrollment->waiver->signatureUrl(),
                'staff'        => $enrollment->waiver->staff_name,
            ] : null,
            'payments' => $enrollment->payments->map(fn ($p) => [
                'type'   => ucfirst($p->type),
                'method' => ucfirst($p->method ?? 'stripe'),
                'amount' => $p->amount_cents / 100,
                'card'   => $p->card_brand ? ucfirst($p->card_brand).' ····'.$p->card_last4 : '—',
                'status' => $p->status,
                'paid'   => $p->paid_at?->format('M j, Y g:i A'),
            ]),
        ]);
    }

    public function storeEnrollment(Request $request): JsonResponse
    {
        $data = $this->enrollmentRules($request);

        $program = Program::findOrFail($data['program_id']);
        $date    = Carbon::parse($data['date']);

        $check = $this->seats->check($program, $data['session'], $date);
        if (! $check['open']) {
            return response()->json(['ok' => false, 'message' => $check['reason']], 422);
        }

        $session = $this->seats->findOrCreateSession($program, $data['session'], $date);

        $enrollment = Enrollment::create([
            'reference'             => Enrollment::nextReference(),
            'name'                  => $data['name'],
            'phone'                 => $data['phone'],
            'email'                 => $data['email'],
            'electrical_experience' => $data['electrical'] ?? null,
            'plc_experience'        => $data['plc'] ?? null,
            'program_id'            => $program->id,
            'class_session_id'      => $session->id,
            'preferred_date'        => $date->toDateString(),
            'status'                => $data['status'] ?? 'started',
            'tuition_cents'         => $program->price_cents,
            'ip_address'            => $request->ip(),
        ]);

        return response()->json(['ok' => true, 'id' => $enrollment->id, 'reference' => $enrollment->reference], 201);
    }

    public function updateEnrollment(Request $request, Enrollment $enrollment): JsonResponse
    {
        $data = $this->enrollmentRules($request);

        $program = Program::findOrFail($data['program_id']);
        $date    = Carbon::parse($data['date']);

        // Only re-check seats when the booking actually moves.
        $moved = $enrollment->preferred_date?->toDateString() !== $date->toDateString()
              || $enrollment->classSession?->label !== $data['session']
              || $enrollment->program_id !== $program->id;

        if ($moved) {
            $check = $this->seats->check($program, $data['session'], $date, ignore: $enrollment);
            if (! $check['open']) {
                return response()->json(['ok' => false, 'message' => $check['reason']], 422);
            }
        }

        $session = $this->seats->findOrCreateSession($program, $data['session'], $date);

        $enrollment->update([
            'name'                  => $data['name'],
            'phone'                 => $data['phone'],
            'email'                 => $data['email'],
            'electrical_experience' => $data['electrical'] ?? null,
            'plc_experience'        => $data['plc'] ?? null,
            'program_id'            => $program->id,
            'class_session_id'      => $session->id,
            'preferred_date'        => $date->toDateString(),
            'status'                => $data['status'],
            'tuition_cents'         => $program->price_cents,
        ]);

        return response()->json(['ok' => true]);
    }

    public function destroyEnrollment(Enrollment $enrollment): JsonResponse
    {
        if ($enrollment->paidCents() > 0) {
            return response()->json([
                'ok' => false,
                'message' => 'This enrolment has money against it. Cancel it instead of deleting, so the payment record survives.',
            ], 422);
        }

        $enrollment->waiver?->delete();
        $enrollment->delete();

        return response()->json(['ok' => true]);
    }

    private function enrollmentRules(Request $request): array
    {
        return $request->validate([
            'name'       => ['required', 'string', 'max:120'],
            'phone'      => ['required', 'string', 'max:40'],
            'email'      => ['required', 'email', 'max:180'],
            'electrical' => ['nullable', 'string', 'max:80'],
            'plc'        => ['nullable', 'string', 'max:80'],
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'session'    => ['required', 'string', 'in:'.implode(',', config('ptt.session_slots'))],
            'date'       => ['required', 'date'],
            'status'     => ['nullable', 'in:started,waiver_signed,deposit_paid,paid,completed,cancelled,abandoned'],
        ]);
    }

    /* ── Certificates ──────────────────────────────────────────────── */

    public function issueCertificate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'enrollment_id' => ['required', 'integer', 'exists:enrollments,id'],
            'instructor_id' => ['nullable', 'integer', 'exists:instructors,id'],
            'completed_on'  => ['required', 'date'],
        ]);

        $enrollment = Enrollment::with('program')->findOrFail($data['enrollment_id']);

        if ($enrollment->certificate) {
            return response()->json(['ok' => false, 'message' => 'This enrolment already has a certificate.'], 422);
        }

        $certificate = Certificate::create([
            'enrollment_id'      => $enrollment->id,
            'instructor_id'      => $data['instructor_id'] ?? null,
            'certificate_number' => Certificate::nextNumber(),
            'student_name'       => $enrollment->name,
            'course'             => $enrollment->program->name,
            'completed_on'       => $data['completed_on'],
            'is_public'          => true,
            'status'             => 'issued',
            'issued_at'          => now(),
        ]);

        return response()->json(['ok' => true, 'number' => $certificate->certificate_number], 201);
    }

    public function revokeCertificate(Request $request, Certificate $certificate): JsonResponse
    {
        $request->validate(['reason' => ['required', 'string', 'min:4', 'max:255']]);

        $certificate->update(['status' => 'revoked', 'is_public' => false]);

        return response()->json(['ok' => true]);
    }

    /** A reissue keeps the original number — only the audit trail moves on. */
    public function reissueCertificate(Certificate $certificate): JsonResponse
    {
        $certificate->update(['status' => 'issued', 'is_public' => true, 'issued_at' => now()]);

        return response()->json(['ok' => true, 'number' => $certificate->certificate_number]);
    }

    /* ── Settings ──────────────────────────────────────────────────── */

    public function saveSetting(Request $request): JsonResponse
    {
        $data = $request->validate([
            'key'   => ['required', 'string', 'max:60'],
            'value' => ['required'],
        ]);

        // Programme prices live in their own table, not the settings store.
        if (str_starts_with($data['key'], 'program_price_')) {
            $id     = (int) str_replace('program_price_', '', $data['key']);
            $amount = (int) round(((float) preg_replace('/[^0-9.]/', '', (string) $data['value'])) * 100);

            if ($amount < 100) {
                return response()->json(['ok' => false, 'message' => 'Enter a price of at least $1.'], 422);
            }

            Program::whereKey($id)->update(['price_cents' => $amount]);

            return response()->json(['ok' => true]);
        }

        Setting::put($data['key'], (string) $data['value']);

        return response()->json(['ok' => true]);
    }

    /* ── Export ────────────────────────────────────────────────────── */

    public function export(Request $request): StreamedResponse
    {
        $rows = Enrollment::with(['program', 'classSession'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(fn ($w) => $w->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term)
                    ->orWhere('reference', 'like', $term));
            })
            ->latest()->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Reference', 'Name', 'Phone', 'Email', 'Program', 'Session',
                           'Start date', 'Status', 'Tuition', 'Paid', 'Balance']);

            foreach ($rows as $e) {
                fputcsv($out, [
                    $e->reference, $e->name, $e->phone, $e->email,
                    $e->program?->name, $e->classSession?->label,
                    $e->preferred_date?->toDateString(), $e->statusLabel(),
                    number_format($e->tuition_cents / 100, 2),
                    number_format($e->paidCents() / 100, 2),
                    number_format($e->balanceCents() / 100, 2),
                ]);
            }
            fclose($out);
        }, 'enrollments-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    /* ── Lookups for the modals ────────────────────────────────────── */

    public function lookups(): JsonResponse
    {
        return response()->json([
            'programs'    => Program::orderBy('price_cents')->get(['id', 'name', 'price_cents']),
            'sessions'    => array_values(config('ptt.session_slots')),
            'instructors' => Instructor::where('status', 'active')->get(['id', 'name']),
            'completable' => Enrollment::whereIn('status', ['paid', 'deposit_paid', 'completed'])
                                ->doesntHave('certificate')
                                ->with('program')
                                ->get()
                                ->map(fn ($e) => [
                                    'id' => $e->id,
                                    'label' => $e->reference.' — '.$e->name.' ('.$e->program?->name.')',
                                ]),
        ]);
    }
}
