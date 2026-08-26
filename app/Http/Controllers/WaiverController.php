<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Waiver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WaiverController extends Controller
{
    /** Max decoded size for the signature PNG (200 KB is generous for a canvas trace). */
    private const MAX_SIGNATURE_BYTES = 200 * 1024;

    public function store(Request $request, Enrollment $enrollment): JsonResponse
    {
        if ($enrollment->isSeatHoldExpired()) {
            return response()->json([
                'ok'      => false,
                'expired' => true,
                'message' => 'Your seat hold expired. Please start again to reselect a date.',
            ], 409);
        }

        $data = $request->validate([
            'legal_name'        => ['required', 'string', 'min:2', 'max:150'],
            'phone'             => ['required', 'string', 'min:7', 'max:40'],
            'address'           => ['required', 'string', 'min:5', 'max:255'],
            'emergency_contact' => ['required', 'string', 'min:2', 'max:150'],
            'emergency_phone'   => ['required', 'string', 'min:7', 'max:40'],
            'agreed'            => ['accepted'],
            'photo_consent'     => ['nullable', 'boolean'],
            'signature'         => ['required', 'string'],
        ], [
            'agreed.accepted'   => 'The waiver must be agreed to before continuing.',
            'signature.required'=> 'A signature is required.',
        ]);

        $path = $this->storeSignature($data['signature'], $enrollment);

        if ($path === null) {
            return response()->json([
                'ok'      => false,
                'message' => 'The signature could not be read. Please clear it and sign again.',
            ], 422);
        }

        DB::transaction(function () use ($enrollment, $data, $path, $request) {
            // Replacing a previous attempt should not leave an orphan file.
            if ($enrollment->waiver && $enrollment->waiver->signature_path !== $path) {
                Storage::disk('public')->delete($enrollment->waiver->signature_path);
            }

            Waiver::updateOrCreate(
                ['enrollment_id' => $enrollment->id],
                [
                    'legal_name'        => $data['legal_name'],
                    'phone'             => $data['phone'],
                    'address'           => $data['address'],
                    'emergency_contact' => $data['emergency_contact'],
                    'emergency_phone'   => $data['emergency_phone'],
                    'agreed'            => true,
                    'photo_consent'     => (bool) ($data['photo_consent'] ?? false),
                    'signature_path'    => $path,
                    'signed_at'         => now(),
                    'ip_address'        => $request->ip(),
                    'user_agent'        => substr((string) $request->userAgent(), 0, 500),
                ]
            );

            $enrollment->update([
                'status'               => 'waiver_signed',
                // Signing renews the hold so the student gets a full window to pay.
                'seat_hold_expires_at' => now()->addMinutes(config('ptt.seat_hold_minutes')),
            ]);
        });

        $enrollment->refresh();

        return response()->json([
            'ok'           => true,
            'hold_expires' => $enrollment->seat_hold_expires_at->toIso8601String(),
            'hold_seconds' => config('ptt.seat_hold_minutes') * 60,
        ]);
    }

    /**
     * Decodes the canvas data URL and writes a PNG. Returns null if the payload
     * is not a plausible PNG — never trust a base64 blob from the browser.
     */
    private function storeSignature(string $dataUrl, Enrollment $enrollment): ?string
    {
        if (! preg_match('#^data:image/png;base64,([A-Za-z0-9+/=]+)$#', trim($dataUrl), $m)) {
            return null;
        }

        $binary = base64_decode($m[1], true);

        if ($binary === false || strlen($binary) < 100 || strlen($binary) > self::MAX_SIGNATURE_BYTES) {
            return null;
        }

        // PNG magic number — rejects anything that merely claims to be a PNG.
        if (substr($binary, 0, 8) !== "\x89PNG\r\n\x1a\n") {
            return null;
        }

        $path = 'signatures/' . $enrollment->reference . '-' . Str::random(8) . '.png';
        Storage::disk('public')->put($path, $binary);

        return $path;
    }
}
