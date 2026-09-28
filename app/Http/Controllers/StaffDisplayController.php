<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\LiveController;
use App\Models\Setting;
use App\Support\OwnerSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * General Staff Progress (S1). Read-only. Open to anyone signed in, or — for
 * an office screen that nobody signs in to — through the owner's private
 * display link. Issuing a new link retires the old one at once.
 */
class StaffDisplayController extends Controller
{
    public function show(Request $request)
    {
        if (! $this->allowed($request)) {
            return $request->filled('display') ? abort(404) : redirect()->route('admin.login');
        }

        return view('staff.progress', [
            'targets' => OwnerSettings::targets(),
            'bootstrap' => [
                'live' => route('staff.live', array_filter(['display' => $request->query('display')])),
            ],
        ]);
    }

    public function live(Request $request): JsonResponse
    {
        abort_unless($this->allowed($request), 403);

        $payload = app(LiveController::class)->payload();
        unset($payload['owners']);   // assignment lists are for the Assistant screen

        return response()->json($payload);
    }

    private function allowed(Request $request): bool
    {
        if (Auth::check() && Auth::user()->is_active) {
            return true;
        }

        $token = (string) Setting::get('staff_display_token', '');
        $given = (string) $request->query('display', '');

        return $token !== '' && $given !== '' && hash_equals($token, $given);
    }
}
