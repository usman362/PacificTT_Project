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
use App\Support\OwnerFigures;
use App\Support\OwnerSettings;
use App\Models\Instructor;
use App\Models\InstructorInvitation;
use App\Models\Mission;
use App\Models\PublishedMetric;
use App\Models\User;
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
        $floor   = $this->trainingFloor();
        $enroll  = $this->enrollmentView();
        $p2      = $this->part2($targets, $floor, $enroll);

        return view('admin.owner', [
            'user'          => $user,
            'initials'      => collect(preg_split('/\s+/', trim($user->name)))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode(''),
            'today'         => now(),
            'notifications' => CompanyUpdate::whereIn('type', ['priority', 'alert'])->where('created_at', '>=', now()->startOfDay())->count(),
            'targets'       => $targets,
            'controls'      => OwnerSettings::controls(),
            'floor'         => $floor,
            'pulse'         => $this->operatingPulse(),
            'enroll'        => $enroll,
            'p2'            => $p2,
            'figures'       => OwnerFigures::all(),
            'displayUrl'    => ($t = Setting::get('staff_display_token')) ? route('staff.progress', ['display' => $t]) : null,
            'bootstrap'     => [
                'routes' => [
                    'live'           => route('admin.live'),
                    'liveMission'    => route('admin.live.missions.update', ['mission' => '__ID__']),
                    'objectives'     => route('admin.owner.objectives'),
                    'objective'      => route('admin.owner.objectives.update', ['objective' => '__ID__']),
                    'targets'        => route('admin.owner.targets'),
                    'controls'       => route('admin.owner.controls'),
                    'figures'        => route('admin.owner.figures', ['group' => '__GROUP__']),
                    'displayLink'    => route('admin.owner.display-link'),
                ],
                'figures'         => OwnerFigures::all(),
                'sopStatuses'     => OwnerFigures::SOP_STATUSES,
                'gatesBase'       => $p2['gatesBase'],
                'targetUtil'      => (int) $targets['target_utilization'],
                'blendedTuition'  => $this->blendedTuition(),
                'enrolledThisMonth' => $enroll['enrolled'],
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
            'prices'                => ['sometimes', 'array'],
            'prices.*'              => ['required', 'numeric', 'min:1', 'max:100000'],
        ], [
            'prices.*.min'                => 'A programme price must be at least $1.',
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
        // Tuition per programme. New enrollments are charged the new price;
        // existing enrollments keep the tuition they signed up at.
        foreach ($data['prices'] ?? [] as $programId => $dollars) {
            Program::whereKey((int) $programId)->update(['price_cents' => (int) round($dollars * 100)]);
        }
        foreach (OwnerSettings::SECRETS as $k) {
            if (! empty($data[$k])) {
                OwnerSettings::putSecret($k, $data[$k]);
            }
        }
        OwnerSettings::apply();

        return response()->json(['ok' => true, 'controls' => OwnerSettings::controls()]);
    }

    /** A new private link for the office Staff Progress screen; the old one stops working. */
    public function newDisplayLink(): JsonResponse
    {
        $token = \Illuminate\Support\Str::random(40);
        Setting::put('staff_display_token', $token);

        return response()->json(['url' => route('staff.progress', ['display' => $token])]);
    }

    /* ── Owner-entered figures (A1 part 2) ────────────────────────────── */

    public function saveFigures(Request $request, string $group): JsonResponse
    {
        $rules = OwnerFigures::rules($group);
        // "total (row 2)" rather than "equipment.1.total".
        $names = [];
        foreach (array_keys($rules) as $key) {
            $names[$key] = str_contains($key, '.*.')
                ? str_replace('_', ' ', substr($key, strrpos($key, '.') + 1)).' (row :position)'
                : str_replace('_', ' ', $key);
        }
        $data = $request->validate($rules, [
            'equipment.*.total.gte' => 'Online cannot be more than the total (row :position).',
        ], $names);
        OwnerFigures::save(OwnerFigures::changes($group, $data));

        return response()->json(['ok' => true]);
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

    /* ── A1 part 2: training, finance, people, growth, weekly, SOPs ───── */

    private function part2(array $targets, array $floor, array $enroll): array
    {
        $f       = OwnerFigures::all();
        $live    = PublishedMetric::current();
        $from    = now()->startOfMonth();
        $holding = $this->seats->holdingStatuses();

        // ── equipment
        $eqOnline = array_sum(array_column($f['equipment'], 'online'));
        $eqTotal  = array_sum(array_column($f['equipment'], 'total'));
        $eqDown   = count(array_filter($f['equipment'], fn ($e) => $e['online'] < $e['total']));
        $uptime   = $eqTotal ? round($eqOnline / $eqTotal * 100, 1) : null;

        // ── money
        $paidSince = fn ($a, $b = null) => Payment::where('status', 'succeeded')
            ->where('paid_at', '>=', $a)->when($b, fn ($q) => $q->where('paid_at', '<', $b))->sum('amount_cents') / 100;
        $cashMtd  = $paidSince($from);
        $active   = Enrollment::whereNotIn('status', ['cancelled', 'abandoned'])->with('payments')->get();
        $billed   = $active->sum('tuition_cents') / 100;
        $ar       = $active->sum(fn ($e) => $e->balanceCents()) / 100;
        $costs    = OwnerFigures::costs();
        $costTot  = array_sum(array_column($costs, 'amount'));
        // Revenue for the margin: tuition collected plus the specialty and B2B
        // revenue the owner records (the same four engines shown below).
        $revenue  = $cashMtd + (float) ($f['engines']['specialty'] ?? 0) + (float) ($f['engines']['b2b'] ?? 0);
        $profit   = $revenue - $costTot;
        $margin   = ($revenue > 0 && $costs) ? round($profit / $revenue * 100) : null;
        $reserve  = ($f['reserve_cash'] !== null && $costTot > 0) ? round($f['reserve_cash'] / $costTot, 1) : null;
        $advert   = collect($costs)->first(fn ($c) => stripos($c['name'], 'advert') !== false || stripos($c['name'], 'marketing') !== false);

        $trend = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = now()->startOfMonth()->subMonthsNoOverflow($i);
            $trend[] = ['label' => $m->format('M'), 'value' => $paidSince($m, $m->copy()->addMonthNoOverflow())];
        }
        $peak = max(1, max(array_column($trend, 'value')));
        foreach ($trend as &$t) { $t['height'] = max(3, round($t['value'] / $peak * 100)); }
        unset($t);

        $tierRevenue = function (string $tier) use ($from) {
            $slugs = array_keys(array_filter(config('ptt.program_tiers', []), fn ($v) => $v === $tier));

            return Payment::where('status', 'succeeded')->where('paid_at', '>=', $from)
                ->whereHas('enrollment.program', fn ($q) => $q->whereIn('slug', $slugs))->sum('amount_cents') / 100;
        };
        $engines = [
            ['Core training',     $tierRevenue('Core'),                      'Stable',   '', 'Electrical · HVAC/R · PLC'],
            ['Advanced programs', $tierRevenue('Advanced'),                  'Growing',  '', 'HMI · SCADA · Chillers'],
            ['Specialty training',(float) ($f['engines']['specialty'] ?? 0), 'Build',    'warn', 'VFDs · Rectifiers · Troubleshooting'],
            ['B2B training',      (float) ($f['engines']['b2b'] ?? 0),       'Priority', 'warn', 'Employer cohorts · Custom programs'],
        ];
        $engTotal = max(1, array_sum(array_column($engines, 1)));
        $engines = array_map(fn ($e) => ['name' => $e[0], 'revenue' => $e[1], 'share' => round($e[1] / $engTotal * 100),
                                          'status' => $e[2], 'class' => $e[3], 'programs' => $e[4]], $engines);
        $costPeak = max(1, $costs ? max(array_column($costs, 'amount')) : 1);

        // ── people
        $users = User::where('is_active', true)->orderByRaw("role = 'owner' desc")->orderBy('name')->get();
        $instructors = Instructor::where('status', 'active')->with('availability')->orderBy('name')->get();
        $today = today();
        $todaySlots = [];
        foreach (config('ptt.session_slots') as $slot) {
            $todaySlots[$slot] = $this->seats->isOpenOn($today) ? $this->seats->instructorsForSlot($today, $slot)->pluck('id')->all() : [];
        }
        $per = (int) config('ptt.seats_per_instructor');
        $team = $users->map(fn ($u) => ['initials' => $this->initials($u->name), 'name' => $u->name,
                                         'role' => $u->roleLabel(), 'load' => null, 'status' => 'Active'])->all();
        foreach ($instructors as $i) {
            $slots = array_keys(array_filter($todaySlots, fn ($ids) => in_array($i->id, $ids, true)));
            $load = null;
            if ($slots) {
                $share = 0;
                foreach ($slots as $slot) {
                    $n = count($todaySlots[$slot]);
                    $share += $n ? $this->seats->bookedForSlot($today, $slot) / $n : 0;
                }
                $load = min(100, round($share / (count($slots) * $per) * 100));
            }
            $team[] = ['initials' => $this->initials($i->name), 'name' => $i->name, 'role' => 'Instructor',
                       'load' => $load, 'status' => $slots ? 'Teaching today' : 'Off today'];
        }
        $monthCap = $enroll['programs'][0]['capacity'] ?? 0;
        $monthBooked = Enrollment::where('created_at', '>=', $from)->whereIn('status', $holding)->count();
        $instUtil = $monthCap ? round($monthBooked / $monthCap * 100) : 0;
        $dep = $f['dependency'];
        $depPct = $dep ? round(count(array_filter($dep, fn ($d) => $d['delegated'])) / count($dep) * 100) : null;
        $safeDays = $f['safety']['last_incident'] ? Carbon::parse($f['safety']['last_incident'])->startOfDay()->diffInDays(today()) : null;

        // ── growth gates
        $utilNow = $floor['capacity'] ? round($floor['booked'] / $floor['capacity'] * 100) : 0;
        $utilMonth = $instUtil;
        $pipeline = InstructorInvitation::whereNull('consumed_at')->where('expires_at', '>', now())->count();
        $gates = [
            'profit'   => ['Consistent profitability', ($f['profitable_months'] ?? 0) >= 6,
                           $f['profitable_months'] === null ? 'Not entered' : $f['profitable_months'].' profitable months'],
            'reserve'  => ['Six-month cash reserve', $reserve !== null && $reserve >= $targets['target_reserve'],
                           $reserve === null ? 'Not entered' : $reserve.' months current'],
            'leads'    => ['Lead volume supports expansion', $enroll['leads'] >= $targets['target_leads'],
                           $enroll['leads'].' leads this month'],
            'pipeline' => ['Instructor pipeline exists', $pipeline > 0,
                           $pipeline.' candidate'.($pipeline === 1 ? '' : 's').' invited'],
        ];
        $gatesBase = count(array_filter($gates, fn ($g) => $g[1]));

        // ── weekly review: Mission Focus tasks and owner objectives together
        $week = now()->startOfWeek(); $lastWeek = $week->copy()->subWeek();
        Mission::settleExpired(); OwnerObjective::settleExpired();
        $count = function ($start, $end, array $statuses) {
            return Mission::whereBetween('deadline', [$start, $end])->whereIn('status', $statuses)->count()
                 + OwnerObjective::whereBetween('deadline', [$start, $end])
                     ->whereIn('status', array_map(fn ($s) => $s === 'noise' ? 'missed' : $s, $statuses))->count();
        };
        $wf = $count($week, $week->copy()->endOfWeek(), ['complete']);
        $wn = $count($week, $week->copy()->endOfWeek(), ['noise']);
        $lf = $count($lastWeek, $lastWeek->copy()->endOfWeek(), ['complete']);
        $ln = $count($lastWeek, $lastWeek->copy()->endOfWeek(), ['noise']);
        $score = fn ($a, $b) => ($a + $b) ? round($a / ($a + $b) * 100) : 0;
        $open = Mission::where('status', 'open')->count() + OwnerObjective::where('status', 'open')->count();
        $soon = Mission::where('status', 'open')->where('deadline', '<=', now()->addHours(48))->count()
              + OwnerObjective::where('status', 'open')->where('deadline', '<=', now()->addHours(48))->count();

        // ── SOPs
        $sops = $f['sops'];
        $sopTotal = count($sops);
        $sopApproved = count(array_filter($sops, fn ($x) => $x['status'] === 'Approved'));

        return [
            'uptime' => $uptime, 'eqDown' => $eqDown, 'equipment' => $f['equipment'],
            'attendance' => $live?->attendance, 'completion' => $live?->completion, 'quality' => $f['quality'],
            'cashMtd' => $cashMtd, 'ar' => $ar, 'arPct' => $billed ? round($ar / $billed * 100, 1) : 0,
            'margin' => $margin, 'profit' => $profit, 'reserve' => $reserve,
            'reservePass' => $reserve !== null && $reserve >= $targets['target_reserve'],
            'trend' => $trend, 'costs' => $costs, 'costTotal' => $costTot, 'costPeak' => $costPeak,
            'engines' => $engines, 'costPerLead' => ($advert && $enroll['leads']) ? round($advert['amount'] / $enroll['leads']) : null,
            'staffUsers' => $users->count(), 'staffInstructors' => $instructors->count(), 'team' => $team,
            'instUtil' => $instUtil, 'satisfaction' => $f['satisfaction'], 'safeDays' => $safeDays,
            'incidents' => $f['safety']['incidents'], 'dependency' => $dep, 'depPct' => $depPct,
            'growth' => $f['growth'], 'utilNow' => $utilMonth, 'gates' => $gates, 'gatesBase' => $gatesBase,
            'managerVerified' => (bool) $f['growth']['manager_verified'],
            'weekNo' => now()->weekOfYear, 'wf' => $wf, 'wn' => $wn, 'lf' => $lf, 'ln' => $ln,
            'wScore' => $score($wf, $wn), 'lScore' => $score($lf, $ln), 'open' => $open, 'soon' => $soon,
            'weekly' => $f['weekly'],
            'sops' => $sops, 'sopTotal' => $sopTotal, 'sopApproved' => $sopApproved,
            'sopPct' => $sopTotal ? round($sopApproved / $sopTotal * 100) : 0,
            'sopReview' => count(array_filter($sops, fn ($x) => $x['status'] === 'Review')),
            'sopDraft' => count(array_filter($sops, fn ($x) => $x['status'] === 'Draft')),
            'sopFounder' => count(array_filter($sops, fn ($x) => $x['status'] === 'Founder only')),
        ];
    }

    private function initials(string $name): string
    {
        return collect(preg_split('/\s+/', trim($name)))->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('');
    }
}
