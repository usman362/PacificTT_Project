<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Program;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EnrollmentAdminController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.enrollments', [
            'enrollments' => $this->filtered($request)->paginate(25)->withQueryString(),
            'programs'    => Program::orderBy('sort_order')->get(),
            'filters'     => $request->only(['q', 'status', 'program_id', 'from', 'to']),
        ]);
    }

    public function show(Enrollment $enrollment): View
    {
        $enrollment->load(['program', 'classSession', 'waiver', 'payments', 'certificate']);

        return view('admin.enrollment-show', compact('enrollment'));
    }

    public function update(Request $request, Enrollment $enrollment): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:started,waiver_signed,deposit_paid,paid,cancelled,abandoned'],
        ]);

        $enrollment->update($data);

        return back()->with('status', 'Enrollment updated.');
    }

    /** CSV of the current filter selection, streamed so large exports stay cheap. */
    public function export(Request $request): StreamedResponse
    {
        $query    = $this->filtered($request)->with(['program', 'classSession', 'waiver']);
        $filename = 'enrollments-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'Reference', 'Name', 'Phone', 'Email', 'Program', 'Session', 'Start date',
                'Status', 'Tuition', 'Paid', 'Balance',
                'Electrical experience', 'PLC experience',
                'Waiver signed', 'Emergency contact', 'Emergency phone', 'Created',
            ]);

            $query->chunk(200, function ($rows) use ($out) {
                foreach ($rows as $e) {
                    fputcsv($out, [
                        $e->reference,
                        $e->name,
                        $e->phone,
                        $e->email,
                        optional($e->program)->name,
                        optional($e->classSession)->label,
                        optional($e->preferred_date)->toDateString(),
                        $e->status,
                        number_format($e->tuition_cents / 100, 2),
                        number_format($e->amountPaidCents() / 100, 2),
                        number_format($e->balanceDueCents() / 100, 2),
                        $e->electrical_experience,
                        $e->plc_experience,
                        optional(optional($e->waiver)->signed_at)->toDateTimeString(),
                        optional($e->waiver)->emergency_contact,
                        optional($e->waiver)->emergency_phone,
                        $e->created_at->toDateTimeString(),
                    ]);
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=utf-8']);
    }

    private function filtered(Request $request)
    {
        return Enrollment::query()
            ->with(['program', 'classSession'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . $request->string('q')->trim() . '%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                          ->orWhere('email', 'like', $term)
                          ->orWhere('phone', 'like', $term)
                          ->orWhere('reference', 'like', $term);
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('program_id'), fn ($q) => $q->where('program_id', $request->integer('program_id')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('to')))
            ->latest();
    }
}
