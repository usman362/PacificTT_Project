<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyUpdate;
use App\Models\Enrollment;
use App\Models\Lead;
use App\Models\OwnerObjective;
use App\Models\Payment;
use App\Models\Program;
use App\Models\Setting;
use App\Services\SeatAvailability;
use App\Support\OwnerSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Pete's Operations Dashboard (A1). Owner only. */
class OwnerController extends Controller
{
    public function __construct(private SeatAvailability $seats) {}

    public function index(Request $request): View
    {
        $user    = $request->user();
        $targets = OwnerSettings::targets();

        return view('admin.owner', [
            'user'          => $user,
            'initials'      => collect(preg_split('/\s+/', trim($user->name)))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode(''),
            'today'         => now(),
            'notifications' => CompanyUpdate::whereIn('type', ['priority', 'alert'])->where('created_at', '>=', now()->startOfDay())->count(),
            'targets'       => $targets,
            'controls'      => OwnerSettings::controls(),
            'floor'         => $this->trainingFloor(),
            'pulse'         => $this->operatingPulse(),
            'enroll'        => $this->enrollmentView(),
            'bootstrap'     => [
                'routes' => [
                    'live'           => route('admin.live'),
                    'liveMission'    => route('admin.live.missions.update', ['mission' => '__ID__']),
                    'objectives'     => route('admin.owner.objectives'),
                    'objective'      => route('admin.owner.objectives.update', ['objective' => '__ID__']),
                    'targets'        => route('admin.owner.targets'),
                    'controls'       => route('admin.owner.controls'),
                ],
                'blendedTuition'  => $this->blendedTuition(),
                'enrolledThisMonth' => $this->enrollmentView()['enrolled'],
            ],
        ]);
    }

    /* ── Owner Settings ───────────────────────────────────────────────── */

    public function saveTargets(Request $request): JsonResponse
    {
        $data = $request->validate([
            'target_enrollments' => ['required', 'integer', 'min:1', 'max:100000'],
            'target_utilization' => ['required', 'integer', 'min:1', 'max:100'],
            'target_margin'      => ['required', 'integer', 'min:1', 'max:100'],
            'target_reserve'     => ['required', 'numeric', 'min:0.1', 'max:120'],
            'target_leads'       => ['required', 'integer', 'min:1', 'max:1000000'],
            'target_noise'       => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        foreach ($data as $k => $v) {
            Setting::put($k, (string) $v);
        }

        return response()->json(['ok' => true, 'targets' => OwnerSettings::targets()]);
    }

    public function saveControls(Request $request): JsonResponse
    {
        $data = $request->validate([
            'seats_per_instructor'  => ['required', 'integer', 'min:1', 'max:50'],
            'deposit_percent'       => ['required', 'integer', 'min:0', 'max:100'],
            'seat_hold_minutes'     => ['required', 'integer', 'min:1', 'max:120'],
            'payment_merchant'      => ['required', Rule::in(OwnerSettings::MERCHANTS)],
            'payment_mode'          => ['required', Rule::in(OwnerSettings::MODES)],
            // Blank means "keep what is stored".
            'stripe_key'            => ['nullable', 'string', 'max:255', 'regex:/^pk_(live|test)_[A-Za-z0-9]+$/'],
            'stripe_secret'         => ['nullable', 'string', 'max:255', 'regex:/^(sk|rk)_(live|test)_[A-Za-z0-9]+$/'],
            'stripe_webhook_secret' => ['nullable', 'string', 'max:255', 'regex:/^whsec_[A-Za-z0-9]+$/'],
        ], [
            'stripe_key.regex'            => 'The publishable key starts with pk_live_ or pk_test_.',
            'stripe_secret.regex'         => 'The secret key starts with sk_live_ or sk_test_ (or a restricted rk_ key).',
            'stripe_webhook_secret.regex' => 'The webhook signing secret starts with whsec_.',
        ]);

        // Keys must match the mode: live keys for Live, test keys for Test.
        if (in_array($data['payment_mode'], ['Live', 'Test'], true)) {
            $want = $data['payment_mode'] === 'Live' ? '_live_' : '_test_';
            foreach (['stripe_key' => 'publishable key', 'stripe_secret' => 'secret key'] as $k => $label) {
                $value = $data[$k] ?? OwnerSettings::secret($k);
                if ($value && ! str_contains($value, $want)) {
                    return response()->json([
                        'message' => "Payment mode is {$data['payment_mode']}, but the {$label} is a ".($want === '_live_' ? 'test' : 'live').' key.',
                        'errors'  => [$k => ['Key does not match the payment mode.']],
                    ], 422);
                }
            }
        }

        foreach (['seats_per_instructor', 'deposit_percent', 'seat_hold_minutes', 'payment_merchant', 'payment_mode'] as $k) {
            Setting::put($k, (string) $data[$k]);
        }
        foreach (OwnerSettings::SECRETS as $k) {
            if (! empty($data[$k])) {
                OwnerSettings::putSecret($k, $data[$k]);
            }
        }
        OwnerSettings::apply();

        return response()->json(['ok' => true, 'controls' => OwnerSettings::controls()]);
    }

    /* ── Progress Tracker objectives ──────────────────────────────────── */

    public function objectives(): JsonResponse
    {
        OwnerObjective::settleExpired();

        return response()->json(OwnerObjective::orderByDesc('deadline')->get()->map(fn ($o) => $this->objectiveJson($o)));
    }

    public function storeObjective(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'    => ['required', 'string', 'max:200'],
            'category' => ['required', Rule::in(OwnerObjective::CATEGORIES)],
            'deadline' => ['required', 'date', 'after:now'],
        ], ['deadline.after' => 'Choose a completion date and time in the future.']);

        $o = OwnerObjective::create([
            'title'      => $data['title'],
            'category'   => $data['category'],
            'deadline'   => Carbon::parse($data['deadline']),
            'status'     => 'open',
            'created_by' => $request->user()->id,
        ]);

        return response()->json($this->objectiveJson($o), 201);
    }

    public function updateObjective(Request $request, OwnerObjective $objective): JsonResponse
    {
        $data = $request->validate([
            'action'   => ['required', Rule::in(['complete', 'reopen'])],
            'deadline' => ['required_if:action,reopen', 'nullable', 'date', 'after:now'],
        ], ['deadline.after' => 'Choose a completion date and time in the future.']);

        OwnerObjective::settleExpired();
        $objective->refresh();

        if ($data['action'] === 'complete') {
            if ($objective->status !== 'open') {
                return response()->json(['message' => 'Only a planned objective can be completed. Reopen it with a new deadline first.'], 422);
            }
            $objective->update(['status' => 'complete', 'completed_at' => now()]);
        } else {
            $objective->update(['status' => 'open', 'completed_at' => null, 'deadline' => Carbon::parse($data['deadline'])]);
        }

        return response()->json($this->objectiveJson($objective));
    }

    public function deleteObjective(OwnerObjective $objective): JsonResponse
    {
        $objective->delete();

        return response()->json(['ok' => true]);
    }

    private function objectiveJson(OwnerObjective $o): array
    {
        return [
            'id'       => $o->id,
            'title'    => $o->title,
            'category' => $o->category,
            'deadline' => $o->deadline->toIso8601String(),
            'status'   => $o->status,
        ];
    }

    /* ── Dashboard data ───────────────────────────────────────────────── */

    /** Today's sessions as the Command Center's "Training Floor". */
    private function trainingFloor(): array
    {
        $date = today();
        $sessions = [];
        foreach (config('ptt.session_slots') as $slot) {
            $open  = $this->seats->isOpenOn($date);
            $inst  = $open ? $this->seats->instructorsForSlot($date, $slot) : collect();
            $cap   = $open ? $this->seats->capacityForSlot($date, $slot) : 0;
            $booked= $this->seats->bookedForSlot($date, $slot);
            preg_match('/(\d{1,2}:\d{2})\s*([AP]M).*?(\d{1,2}:\d{2}\s*[AP]M)/i', $slot, $m);
            $start = Carbon::parse($date->toDateString().' '.($m[1] ?? '00:00').' '.($m[2] ?? 'AM'));
            $end   = Carbon::parse($date->toDateString().' '.($m[3] ?? '11:59 PM'));

            $status = match (true) {
                ! $open         => ['Closed', 'warn'],
                $cap === 0      => ['Needs instructor', 'warn'],
                now()->between($start, $end) => ['In session', ''],
                now()->gt($end) => ['Complete', ''],
                default         => ['Ready', ''],
            };

            $sessions[] = [
                'time'        => $m[1] ?? $slot,
                'meridiem'    => strtoupper($m[2] ?? ''),
                'title'       => implode(' / ', $this->seats->tiersForSlot($date, $slot)) ?: 'No students booked',
                'instructors' => $inst->pluck('name')->implode(', ') ?: 'Instructor TBD',
                'booked'      => $booked,
                'capacity'    => $cap,
                'fill'        => $cap ? min(100, round($booked / $cap * 100, 1)) : 0,
                'status'      => $status[0],
                'statusClass' => $status[1],
            ];
        }

        return [
            'sessions' => $sessions,
            'count'    => count($sessions),
            'capacity' => array_sum(array_column($sessions, 'capacity')),
            'booked'   => array_sum(array_column($sessions, 'booked')),
        ];
    }

    private function leadConversion(Carbon $from, Carbon $to): float
    {
        $leads = Lead::whereBetween('created_at', [$from, $to])->count();
        $paid  = Enrollment::where('status', 'paid')->whereBetween('created_at', [$from, $to])->count();

        return $leads ? round($paid / $leads * 100, 1) : 0.0;
    }

    private function operatingPulse(): array
    {
        $now  = $this->leadConversion(now()->startOfMonth(), now());
        $prev = $this->leadConversion(now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth());

        return ['conversion' => $now, 'conversionDelta' => round($now - $prev, 1)];
    }

    private function blendedTuition(): int
    {
        $paid = Enrollment::whereIn('status', ['deposit_paid', 'paid'])
            ->where('created_at', '>=', now()->startOfMonth())->avg('tuition_cents');

        return (int) round(($paid ?: Program::where('is_active', true)->avg('price_cents') ?: 0) / 100);
    }

    private function enrollmentView(): array
    {
        $from  = now()->startOfMonth();
        $leads = Lead::where('created_at', '>=', $from);
        $total = (clone $leads)->count();
        $count = fn (array $s) => (clone $leads)->whereIn('status', $s)->count();

        $contacted = $count(['contacted', 'interested', 'enrollment_started', 'paid']);
        $qualified = $count(['interested', 'enrollment_started', 'paid']);
        $consult   = $count(['enrollment_started', 'paid']);
        $mtd       = Enrollment::where('created_at', '>=', $from);
        $deposits  = (clone $mtd)->whereIn('status', ['deposit_paid', 'paid'])->count();
        $enrolled  = (clone $mtd)->where('status', 'paid')->count();

        $pct = fn (int $n) => $total ? round($n / $total * 100, 1) : 0;
        $peak = max(1, $total, $deposits, $enrolled);
        $funnel = collect([
            ['New leads', $total], ['Contacted', $contacted], ['Qualified', $qualified],
            ['Consultations', $consult], ['Deposits', $deposits], ['Enrolled', $enrolled],
        ])->map(fn ($r) => ['label' => $r[0], 'value' => $r[1], 'pct' => $pct($r[1]),
                             'width' => max(12, round($r[1] / $peak * 100))])->all();

        // Programme performance: this month's seats, tuition collected and
        // the change in bookings against last month.
        $monthCap = $this->seats->capacityForMonth(now());
        $programs = Program::orderBy('sort_order')->get()->map(function ($p) use ($from, $monthCap) {
            $held = fn ($q) => $q->where('program_id', $p->id)->whereIn('status', $this->seats->holdingStatuses());
            $now  = $held(Enrollment::where('created_at', '>=', $from))->count();
            $prev = $held(Enrollment::whereBetween('created_at', [
                now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth(),
            ]))->count();
            $collected = Payment::where('status', 'succeeded')->where('paid_at', '>=', $from)
                ->whereHas('enrollment', fn ($q) => $q->where('program_id', $p->id))->sum('amount_cents');

            return [
                'name'        => $p->name,
                'seats'       => $now,
                'capacity'    => $monthCap,
                'utilization' => $monthCap ? round($now / $monthCap * 100) : 0,
                'tuition'     => $collected / 100,
                'trend'       => $prev ? round(($now - $prev) / $prev * 100) : null,
            ];
        })->all();

        return [
            'leads'    => $total,
            'consult'  => $consult,
            'consultPct' => $pct($consult),
            'enrolled' => $enrolled,
            'funnel'   => $funnel,
            'programs' => $programs,
        ];
    }
}
