<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\Instructor;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\Program;
use App\Models\Waiver;
use App\Services\SeatAvailability;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ConsoleController extends Controller
{
    public function __construct(private SeatAvailability $seats) {}

    public function index(): View
    {
        $today = Carbon::today();

        return view('admin.console', [
            'metrics'      => $this->metrics($today),
            'todayOps'     => $this->todayOperations($today),
            'actions'      => $this->actionsRequired($today),
            'todaySessions'=> $this->todaySessions($today),
            'funnel'       => $this->funnel(),
            'enrollments'  => Enrollment::with(['program', 'classSession', 'waiver', 'payments'])
                                  ->latest()->limit(50)->get(),
            'students'     => $this->students(),
            'leads'        => Lead::with('program')->latest()->get(),
            'leadMetrics'  => $this->leadMetrics(),
            'leadSources'  => $this->leadSources(),
            'payMetrics'   => $this->paymentMetrics($today),
            'zelleQueue'   => Payment::with('enrollment')
                                  ->where('method', 'zelle')->where('status', 'pending')->get(),
            'waivers'      => Waiver::with(['enrollment.program'])->latest()->get(),
            'waiverStats'  => $this->waiverStats(),
            'certificates' => Certificate::with(['enrollment.program', 'instructor'])->latest()->get(),
            'certStats'    => $this->certificateStats(),
            'instructors'  => Instructor::with('availability')->get(),
            'programs'     => Program::orderBy('price_cents')->get(),
            'reports'      => $this->reports(),
            'settings'     => config('ptt'),
            'bootstrap'    => $this->bootstrap(),
        ]);
    }

    /* ── Dashboard ─────────────────────────────────────────────────── */

    private function metrics(Carbon $day): array
    {
        $paidToday = Payment::whereDate('paid_at', $day)->where('status', 'succeeded');

        $capacity  = $this->seats->capacityForDate($day);
        $booked    = $this->seats->bookedForDate($day);

        return [
            'revenue_today'    => $paidToday->sum('amount_cents') / 100,
            'paid_enrollments' => $paidToday->distinct('enrollment_id')->count('enrollment_id'),
            'students_today'   => Enrollment::whereDate('preferred_date', $day)
                                    ->whereIn('status', ['paid', 'deposit_paid', 'waiver_signed'])->count(),
            'sessions_today'   => ClassSession::whereDate('start_date', $day)->count(),
            'occupancy'        => $capacity > 0 ? round($booked / $capacity * 100, 1) : 0.0,
            'booked'           => $booked,
            'capacity'         => $capacity,
            'outstanding'      => Enrollment::outstandingTotal() / 100,
            'balances_due'     => Enrollment::withBalance()->count(),
            'new_leads'        => Lead::where('status', 'new')->count(),
            'need_contact'     => Lead::where('status', 'new')->whereNull('follow_up_at')->count(),
        ];
    }

    private function todayOperations(Carbon $day): array
    {
        $capacity = $this->seats->capacityForDate($day);
        $booked   = $this->seats->bookedForDate($day);

        return [
            'capacity'  => $capacity,
            'remaining' => max(0, $capacity - $booked),
            'core'      => $this->seats->bookedForProgramOnDate($day, 'plc-electrical-controls'),
            'advanced'  => $this->seats->bookedForProgramOnDate($day, 'advanced-plc-automation'),
        ];
    }

    private function actionsRequired(Carbon $day): array
    {
        return [
            'waivers_to_review' => Waiver::where('needs_review', true)->count(),
            'balances_today'    => Enrollment::withBalance()->whereDate('preferred_date', $day)->count(),
            'leads_uncontacted' => Lead::where('status', 'new')->count(),
            'certs_ready'       => Certificate::where('status', 'ready')->count(),
        ];
    }

    private function todaySessions(Carbon $day): array
    {
        return collect(config('ptt.session_slots'))->map(function ($slot) use ($day) {
            $cap    = $this->seats->capacityForSlot($day, $slot);
            $booked = $this->seats->bookedForSlot($day, $slot);

            return [
                'time'       => $slot,
                'instructors'=> $this->seats->instructorsForSlot($day, $slot)->count(),
                'booked'     => $booked,
                'available'  => max(0, $cap - $booked),
                'status'     => $cap === 0 ? 'CLOSED' : ($booked >= $cap ? 'FULL' : 'OPEN'),
            ];
        })->all();
    }

    private function funnel(): array
    {
        $leads   = Lead::count();
        $started = Enrollment::count();
        $waivers = Waiver::count();
        $checkout= Enrollment::whereIn('status', ['deposit_paid', 'paid'])->count();
        $paid    = Enrollment::where('status', 'paid')->count();

        return [
            ['label' => 'Leads',    'value' => $leads],
            ['label' => 'Started',  'value' => $started],
            ['label' => 'Waivers',  'value' => $waivers],
            ['label' => 'Checkout', 'value' => $checkout],
            ['label' => 'Paid',     'value' => $paid],
            'conversion' => $leads > 0 ? round($paid / $leads * 100, 1) : 0.0,
        ];
    }

    /* ── Other pages ───────────────────────────────────────────────── */

    private function students(): \Illuminate\Support\Collection
    {
        return Enrollment::selectRaw('email, MAX(name) as name, MAX(phone) as phone,
                COUNT(*) as courses,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed')
            ->groupBy('email')
            ->get()
            ->map(function ($row) {
                $row->total_paid = Payment::whereIn('enrollment_id',
                        Enrollment::where('email', $row->email)->pluck('id'))
                    ->where('status', 'succeeded')->sum('amount_cents') / 100;

                return $row;
            });
    }

    private function leadMetrics(): array
    {
        return [
            'new'       => Lead::where('status', 'new')->count(),
            'contacted' => Lead::where('status', 'contacted')->count(),
            'started'   => Lead::where('status', 'enrollment_started')->count(),
            'paid'      => Lead::where('status', 'paid')->count(),
            'rate'      => Lead::count() > 0
                                ? round(Lead::where('status', 'paid')->count() / Lead::count() * 100, 1)
                                : 0.0,
        ];
    }

    private function leadSources(): array
    {
        $rows = Lead::selectRaw('source, COUNT(*) as total')
            ->whereMonth('created_at', now()->month)
            ->groupBy('source')->orderByDesc('total')->get();

        $max = $rows->max('total') ?: 1;

        return $rows->map(fn ($r) => [
            'source'  => $r->source,
            'total'   => $r->total,
            'percent' => round($r->total / $max * 100),
        ])->all();
    }

    private function paymentMetrics(Carbon $day): array
    {
        $ok = Payment::where('status', 'succeeded');

        return [
            'today'       => (clone $ok)->whereDate('paid_at', $day)->sum('amount_cents') / 100,
            'mtd'         => (clone $ok)->whereMonth('paid_at', $day->month)
                                        ->whereYear('paid_at', $day->year)->sum('amount_cents') / 100,
            'outstanding' => Enrollment::outstandingTotal() / 100,
            'refunds'     => Payment::where('status', 'refunded')->sum('amount_cents') / 100,
        ];
    }

    private function waiverStats(): array
    {
        return [
            'signed'    => Waiver::count(),
            'staff_due' => Waiver::whereNull('staff_accepted_on')->count(),
            'executed'  => Waiver::whereNotNull('staff_accepted_on')->count(),
            'review'    => Waiver::where('needs_review', true)->count(),
        ];
    }

    private function certificateStats(): array
    {
        return [
            'issued'      => Certificate::where('status', 'issued')->count(),
            'ready'       => Certificate::where('status', 'ready')->count(),
            'this_month'  => Certificate::where('status', 'issued')
                                ->whereMonth('issued_at', now()->month)->count(),
            'revoked'     => Certificate::where('status', 'revoked')->count(),
        ];
    }

    private function reports(): array
    {
        $mtd = Payment::where('status', 'succeeded')
            ->whereMonth('paid_at', now()->month)->sum('amount_cents') / 100;

        $count = Enrollment::whereIn('status', ['paid', 'deposit_paid'])->count();
        $cap   = $this->seats->capacityForMonth(now());
        $bk    = $this->seats->bookedForMonth(now());

        return [
            'gross_mtd'      => $mtd,
            'avg_enrollment' => $count > 0 ? round($mtd / $count) : 0,
            'occupancy'      => $cap > 0 ? round($bk / $cap * 100, 1) : 0.0,
            'no_show'        => Enrollment::where('status', 'abandoned')->count(),
        ];
    }

    /** Everything the console's own script needs, instead of its mock arrays. */
    private function bootstrap(): array
    {
        $weekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        return [
            'seatsPerInstructor' => (int) config('ptt.seats_per_instructor'),
            'sessionSlots'       => array_values(config('ptt.session_slots')),
            'weekDays'           => $weekdays,
            'instructors' => Instructor::with('availability')->get()->map(function ($i) use ($weekdays) {
                $avail = [];
                foreach ($weekdays as $n => $label) {
                    $row = $i->availability->firstWhere('weekday', $n + 1);
                    $avail[$label] = [
                        'on'    => (bool) ($row?->is_available ?? false),
                        'start' => substr($row?->starts_at ?? '08:00', 0, 5),
                        'end'   => substr($row?->ends_at   ?? '20:00', 0, 5),
                    ];
                }

                return [
                    'id' => $i->id, 'name' => $i->name, 'email' => $i->email,
                    'phone' => $i->phone, 'rate' => $i->daily_rate_cents / 100,
                    'status' => ucfirst($i->status),
                    'course' => match ($i->courses) {
                        'core' => 'Core Only', 'advanced' => 'Advanced Only', default => 'Core + Advanced',
                    },
                    'avail' => $avail,
                ];
            })->values(),
            'leads' => Lead::with('program')->latest()->get()->map(fn ($l) => [
                'id' => $l->id, 'name' => $l->name, 'phone' => $l->phone, 'email' => $l->email,
                'program' => $l->program?->name ?? 'Undecided',
                'date' => $l->preferred_date?->toDateString() ?? '',
                'session' => $l->preferred_session ?? 'Flexible',
                'source' => $l->source, 'status' => $l->statusLabel(),
                'follow' => $l->follow_up_at?->format('M j · g:i A') ?? 'Not scheduled',
                'electrical' => $l->electrical_experience, 'plc' => $l->plc_experience,
                'notes' => $l->notes,
            ])->values(),
            'waivers' => Waiver::with('enrollment')->get()->mapWithKeys(fn ($w) => [$w->id => [
                'enrollment' => $w->enrollment?->reference,
                'student'    => $w->legal_name,
                'program'    => $w->enrollment?->program?->name,
                'signedAt'   => $w->signed_at?->format('F j, Y · g:i A'),
                'staffName'  => $w->staff_name,
                'staffDate'  => $w->staff_accepted_on?->toDateString(),
                'executed'   => (bool) $w->staff_accepted_on,
            ]]),
            'routes' => [
                'saveInstructor'   => route('admin.instructors.save'),
                'deleteInstructor' => route('admin.instructors.delete'),
                'saveLead'         => route('admin.leads.save'),
                'acceptWaiver'     => route('admin.waivers.accept'),
                'verifyZelle'      => route('admin.payments.verify'),
                'schedule'         => route('admin.schedule'),
                'lookups'          => route('admin.lookups'),
                'enrollShow'       => route('admin.enroll.show',    ['enrollment' => '__ID__']),
                'enrollStore'      => route('admin.enroll.store'),
                'enrollUpdate'     => route('admin.enroll.update',  ['enrollment' => '__ID__']),
                'enrollDestroy'    => route('admin.enroll.destroy', ['enrollment' => '__ID__']),
                'certIssue'        => route('admin.cert.issue'),
                'certRevoke'       => route('admin.cert.revoke',    ['certificate' => '__ID__']),
                'certReissue'      => route('admin.cert.reissue',   ['certificate' => '__ID__']),
                'settingsSave'     => route('admin.settings.save'),
                'export'           => route('admin.export'),
            ],
        ];
    }
}
