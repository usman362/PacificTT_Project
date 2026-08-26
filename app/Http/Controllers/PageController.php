<?php

namespace App\Http\Controllers;

use App\Models\Program;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    public function home(): View
    {
        return view('home', [
            'minDate'  => now()->addDay()->toDateString(),
            'maxDate'  => now()->addYear()->toDateString(),
            'programs'     => Program::where('is_active', true)->orderBy('sort_order')->get(),
            'sessionSlots' => config('ptt.session_slots'),
            'holdSeconds'  => config('ptt.seat_hold_minutes') * 60,
            'stripeKey'    => config('services.stripe.key'),
        ]);
    }
}
