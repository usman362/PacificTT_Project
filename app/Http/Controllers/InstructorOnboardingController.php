<?php

namespace App\Http\Controllers;

use App\Mail\InstructorOnboardingCode;
use App\Models\Instructor;
use App\Models\InstructorAgreement;
use App\Models\InstructorAvailability;
use App\Models\InstructorInvitation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class InstructorOnboardingController extends Controller
{
    private const RESEND_SECONDS = 60;

    /** The invite link. Single use — once submitted it stops working. */
    public function show(string $token): View
    {
        $invite = InstructorInvitation::where('token', $token)->first();

        // A spent or missing link shows the same closed page either way, so a
        // guessed token can't be told apart from a used one.
        if (! $invite || ! $invite->isUsable()) {
            return view('instructor.onboarding', ['invite' => null, 'closed' => true]);
        }

        // Send the code on first open, then only when the last one has aged out.
        if (! $invite->email_verified
            && (! $invite->otp_sent_at || $invite->otp_expires_at?->isPast())) {
            $this->sendCode($invite);
        }

        return view('instructor.onboarding', ['invite' => $invite, 'closed' => false]);
    }

    public function verify(Request $request, string $token): JsonResponse
    {
        $request->validate(['code' => ['required', 'digits:6']]);

        $invite = $this->usableInvite($token);
        if (! $invite) {
            return $this->closed();
        }

        if ($invite->isLockedOut()) {
            return response()->json([
                'ok' => false,
                'message' => 'Too many incorrect codes. Ask the office to send a new invitation.',
            ], 429);
        }

        if (! $invite->codeMatches($request->string('code'))) {
            $invite->increment('otp_attempts');
            $left = max(0, InstructorInvitation::MAX_ATTEMPTS - $invite->otp_attempts);

            return response()->json([
                'ok' => false,
                'message' => $invite->otp_expires_at?->isPast()
                    ? 'That code has expired. Reload the page to get a new one.'
                    : "That code is not right. {$left} attempt".($left === 1 ? '' : 's').' left.',
            ], 422);
        }

        $invite->update(['email_verified' => true, 'otp_hash' => null]);

        return response()->json(['ok' => true]);
    }

    public function submit(Request $request, string $token): JsonResponse
    {
        $invite = $this->usableInvite($token);
        if (! $invite) {
            return $this->closed();
        }

        if (! $invite->email_verified) {
            return response()->json(['ok' => false, 'message' => 'Verify your email address first.'], 422);
        }

        $data = $request->validate([
            'name'      => ['required', 'string', 'max:120'],
            'phone'     => ['required', 'string', 'max:40'],
            'email'     => ['required', 'email', 'max:180'],
            'address'   => ['required', 'string', 'max:200'],
            'city'      => ['required', 'string', 'max:120'],
            'signature' => ['required', 'string', 'max:120'],
            'agreed'    => ['accepted'],
            'certified' => ['accepted'],
        ]);

        // The signature has to be the name they typed above it.
        if (mb_strtolower(trim($data['signature'])) !== mb_strtolower(trim($data['name']))) {
            return response()->json([
                'ok' => false,
                'message' => 'The signature must match the legal name exactly.',
            ], 422);
        }

        $instructor = DB::transaction(function () use ($invite, $data, $request) {
            $instructor = Instructor::firstOrNew(['email' => $invite->email]);
            $instructor->fill([
                'name'   => $data['name'],
                'phone'  => $data['phone'],
                'status' => 'active',
            ]);
            $instructor->daily_rate_cents ??= 120000;
            $instructor->courses ??= 'both';
            $instructor->save();

            // Monday–Saturday, closed by default until the office sets a rota.
            foreach (range(1, 6) as $weekday) {
                InstructorAvailability::firstOrCreate(
                    ['instructor_id' => $instructor->id, 'weekday' => $weekday],
                    ['is_available' => false, 'starts_at' => '08:00', 'ends_at' => '20:00']
                );
            }

            InstructorAgreement::create([
                'instructor_id' => $instructor->id,
                'legal_name'    => $data['name'],
                'phone'         => $data['phone'],
                'email'         => $data['email'],
                'address'       => $data['address'],
                'city'          => $data['city'],
                'signature'     => $data['signature'],
                'signed_at'     => now(),
                'ip_address'    => $request->ip(),
                'user_agent'    => substr((string) $request->userAgent(), 0, 500),
            ]);

            $invite->update([
                'consumed_at'   => now(),
                'instructor_id' => $instructor->id,
                'name'          => $data['name'],
            ]);

            return $instructor;
        });

        return response()->json(['ok' => true, 'instructor' => $instructor->name]);
    }


    /** A new code, but not on demand every second. */
    public function resend(string $token): JsonResponse
    {
        $invite = $this->usableInvite($token);
        if (! $invite) {
            return $this->closed();
        }

        if ($invite->email_verified) {
            return response()->json(['ok' => false, 'message' => 'This email is already verified.'], 422);
        }

        if ($invite->isLockedOut()) {
            return response()->json([
                'ok' => false,
                'message' => 'Too many incorrect codes. Ask the office to send a new invitation.',
            ], 429);
        }

        // One a minute is plenty, and stops the inbox filling up.
        $wait = self::RESEND_SECONDS;
        if ($invite->otp_sent_at && $invite->otp_sent_at->diffInSeconds(now()) < $wait) {
            $left = $wait - (int) $invite->otp_sent_at->diffInSeconds(now());

            return response()->json([
                'ok' => false,
                'message' => "A code was just sent. Try again in {$left} seconds.",
            ], 429);
        }

        $this->sendCode($invite);

        return response()->json(['ok' => true, 'message' => 'A new code is on its way.']);
    }

    private function usableInvite(string $token): ?InstructorInvitation
    {
        $invite = InstructorInvitation::where('token', $token)->first();

        return $invite && $invite->isUsable() ? $invite : null;
    }

    private function closed(): JsonResponse
    {
        return response()->json([
            'ok' => false,
            'closed' => true,
            'message' => 'This onboarding link has already been used or has expired.',
        ], 410);
    }

    private function sendCode(InstructorInvitation $invite): void
    {
        $code = $invite->freshCode();

        try {
            Mail::to($invite->email)->send(new InstructorOnboardingCode($invite, $code));
        } catch (\Throwable $e) {
            report($e);   // the page still loads; they can ask for a new invite
        }
    }
}
