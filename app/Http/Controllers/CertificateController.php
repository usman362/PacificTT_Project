<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    /**
     * Public graduate lookup by certificate number or full name.
     *
     * Only records explicitly marked public are returned, and only the fields
     * a stranger is meant to see — never contact details.
     */
    public function verify(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'min:3', 'max:120'],
        ]);

        $q = trim($data['q']);

        $match = Certificate::query()
            ->public()
            ->where(function ($query) use ($q) {
                $query->where('certificate_number', strtoupper($q))
                      ->orWhereRaw('LOWER(student_name) = ?', [mb_strtolower($q)]);
            })
            ->first();

        if (! $match) {
            return response()->json([
                'found'   => false,
                'message' => 'No public completion record found. Check the spelling or certificate number and try again.',
            ]);
        }

        return response()->json([
            'found'       => true,
            'certificate' => [
                'number'       => $match->certificate_number,
                'student_name' => $match->student_name,
                'course'       => $match->course,
                'completed_on' => $match->completed_on->format('F j, Y'),
                'photo_url'    => $match->photoUrl(),
            ],
        ]);
    }
}
