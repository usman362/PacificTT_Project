<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\Payment;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $paidStatuses = ['deposit_paid', 'paid'];

        return view('admin.dashboard', [
            'totalEnrollments' => Enrollment::whereIn('status', $paidStatuses)->count(),
            'pending'          => Enrollment::whereIn('status', ['started', 'waiver_signed'])->count(),
            'revenueCents'     => (int) Payment::where('status', 'succeeded')->sum('amount_cents'),
            'certificates'     => Certificate::count(),
            'recent'           => Enrollment::with(['program', 'classSession'])
                                    ->latest()->limit(10)->get(),
        ]);
    }
}
