<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pacific Trade Tech — Operations Dashboard</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="{{ \App\Support\Asset::url('css/owner.css') }}">
</head>
<body>
  <div class="app">
    <aside id="sidebar">
      <div class="brand"><div class="mark">PTT</div><div><strong>PACIFIC</strong><small>TRADE TECH</small></div></div>
      <div class="nav-label">OPERATIONS</div>
      <nav>
        <button class="active" data-view="overview">Command Center</button>
        <button data-view="enrollment">Enrollment</button>
        <button data-view="training">Training Operations</button>
        <button data-view="finance">Financial Control</button>
        <button data-view="people">People & Quality</button>
        <button data-view="growth">Growth Strategy</button>
      </nav>
      <div class="nav-label">MANAGEMENT</div>
      <nav>
        <button data-view="weekly">Weekly Review</button>
        <button data-view="progress">Progress Tracker</button>
        <button data-view="sops">SOP Library — 75%</button>
        <button data-view="targets">Owner Settings</button>
      </nav>
      <div class="nav-label">DAILY OPERATIONS</div>
      <nav>
        <button data-href="{{ route('admin.dashboard') }}">Assistant Command</button>
      </nav>
      <div class="owner"><div class="avatar">{{ $initials }}</div><div><strong>{{ $user->name }}</strong><small>Owner / CEO</small></div></div>
    </aside>

    <main>
      <header>
        <button class="mobile-menu" id="menuButton">☰</button>
        <div class="title"><small>{{ strtoupper($today->format('l')) }} · {{ strtoupper($today->format('M j')) }}</small><h1 id="pageTitle">Command Center</h1></div>
        <div class="header-actions"><select><option>Today</option><option>This Week</option><option>This Month</option></select><button>Notifications {{ $notifications }}</button></div>
      </header>

      <div class="content">
        <section class="view active" id="overview">
          <div class="kpis">
            <div class="kpi"><label>Cash Collected Today</label><strong id="ownerCashMetric">—</strong><small class="up">Assistant-published live metric</small></div>
            <div class="kpi"><label>Seat Utilization</label><strong id="ownerUtilMetric">—</strong><small>Healthy threshold: {{ (int) $targets['target_utilization'] }}%</small></div>
            <div class="kpi"><label>Monthly Enrollments</label><strong id="ownerEnrollmentMetric">—</strong><small>Goal: {{ (int) $targets['target_enrollments'] }} confirmed students</small></div>
            <div class="kpi"><label>Completion Forecast</label><strong id="ownerCompletionMetric">—</strong><small class="up">Assistant-published live metric</small></div>
          </div>
          <div class="grid">
            <article class="card">
              <div class="card-head"><div><h2>Today's Training Floor</h2><p>{{ $floor['count'] }} sessions · {{ $floor['capacity'] }}-seat daily capacity</p></div><span class="badge">{{ $floor['booked'] }} / {{ $floor['capacity'] }} booked</span></div>
              <div class="schedule">
            @foreach($floor['sessions'] as $s)
            <div class="session"><div class="time"><strong>{{ $s['time'] }}</strong><small>{{ $s['meridiem'] }}</small></div><div class="session-info"><strong>{{ $s['title'] }}</strong><small>{{ $s['instructors'] }} · {{ $s['booked'] }}/{{ $s['capacity'] }} seats</small><div class="progress"><i style="width:{{ $s['fill'] }}%"></i></div></div><span class="badge {{ $s['statusClass'] }}">{{ $s['status'] }}</span></div>
            @endforeach
          </div>
        </article>
            <article class="card">
              <div class="card-head"><div><h2>Assistant Live Operations</h2><p>Latest Priority and Alert updates requiring owner awareness</p></div><span class="badge" id="ownerLiveRevision">Waiting</span></div>
              <div class="alerts" id="ownerLiveUpdates"><div class="alert"><div class="alert-icon">i</div><div><strong>Waiting for Assistant updates</strong><small>Live data appears when both dashboards use the same browser and domain.</small></div></div></div>
            </article>
          </div>
          <div class="grid equal">
            <article class="card">
              <div class="card-head"><div><h2>Daily Execution</h2><p>Opening, delivery, collections, and closeout</p></div><span class="badge" id="taskScore">0 / 0 done</span></div>
          <div id="dailyTasks"></div>
        </article>
            <article class="card">
              <div class="card-head"><div><h2>Operating Pulse</h2><p>Current month against target</p></div></div>
              <table><tbody>
                <tr><td>Lead conversion</td><td><strong>{{ $pulse['conversion'] }}%</strong></td><td class="{{ $pulse['conversionDelta'] >= 0 ? 'up' : 'down' }}">{{ $pulse['conversionDelta'] >= 0 ? '+' : '' }}{{ $pulse['conversionDelta'] }} pts</td></tr>
                <tr><td>Gross margin</td><td><strong>—</strong></td><td></td></tr>
                <tr><td>Cash reserve</td><td><strong>—</strong></td><td></td></tr>
                <tr><td>Equipment uptime</td><td><strong>—</strong></td><td></td></tr>
              </tbody></table>
            </article>
          </div>
        </section>

        <section class="view" id="enrollment">
          <div class="kpis">
            <div class="kpi"><label>Leads This Month</label><strong>{{ $enroll['leads'] }}</strong><small>— cost per lead</small></div>
            <div class="kpi"><label>Consultations</label><strong>{{ $enroll['consult'] }}</strong><small>{{ $enroll['consultPct'] }}% of all leads</small></div>
            <div class="kpi"><label>Enrollments</label><strong id="enrollmentKpi">{{ $enroll['enrolled'] }}</strong><small>Goal: {{ (int) $targets['target_enrollments'] }}</small></div>
            <div class="kpi"><label>Projected Tuition</label><strong id="tuitionKpi">${{ number_format($enroll['enrolled'] * $bootstrap['blendedTuition']) }}</strong><small>Blended enrollment value</small></div>
          </div>
          <div class="grid">
            <article class="card">
              <div class="card-head"><div><h2>Enrollment Funnel</h2><p>Month-to-date conversion</p></div></div>
              <div class="funnel">
            @foreach($enroll['funnel'] as $f)
            <div class="funnel-row"><div style="width:{{ $f['width'] }}%"><span>{{ $f['label'] }}</span><b>{{ number_format($f['value']) }}</b></div><small>{{ $f['pct'] }}%</small></div>
            @endforeach
          </div>
        </article>
            <article class="card">
              <div class="card-head"><div><h2>Monthly Seat Target</h2><p>Model enrollments and tuition</p></div></div>
              <div class="counter"><button id="minusEnrollment">−</button><strong id="enrollmentCount">{{ $enroll['enrolled'] }}</strong><button id="plusEnrollment">+</button></div>
              <div class="big-result"><small>Projected tuition</small><strong id="projectedTuition">${{ number_format($enroll['enrolled'] * $bootstrap['blendedTuition']) }}</strong></div>
            </article>
          </div>
          <article class="card">
            <div class="card-head"><div><h2>Program Performance</h2><p>Seat demand and gross margin</p></div></div>
            <div class="table-wrap"><table>
              <thead><tr><th>Program</th><th>Seats</th><th>Utilization</th><th>Tuition</th><th>Margin</th><th>Trend</th></tr></thead>
              <tbody>
                @foreach($enroll['programs'] as $p)
                <tr><td><strong>{{ $p['name'] }}</strong></td><td>{{ $p['seats'] }} / {{ $p['capacity'] }}</td><td>{{ $p['utilization'] }}%</td><td>${{ number_format($p['tuition']) }}</td><td>—</td><td class="{{ ($p['trend'] ?? 0) >= 0 ? 'up' : 'down' }}">{{ $p['trend'] === null ? '—' : (($p['trend'] >= 0 ? '+' : '').$p['trend'].'%') }}</td></tr>
                @endforeach
              </tbody>
            </table></div>
          </article>
        </section>

        <section class="view" id="training">
          <div class="kpis">
            <div class="kpi"><label>Today's Sessions</label><strong>3</strong><small>Morning · afternoon · evening</small></div>
            <div class="kpi"><label>Seats Booked</label><strong>19 / 24</strong><small>79% utilization</small></div>
            <div class="kpi"><label>Equipment Uptime</label><strong>96.4%</strong><small class="down">1 rig needs service</small></div>
            <div class="kpi"><label>Attendance</label><strong>97%</strong><small>0 no-shows</small></div>
          </div>
          <article class="card" style="margin-bottom:17px">
            <div class="card-head"><div><h2>Training Floor Readiness</h2><p>Classes, instructors, rooms, and equipment</p></div></div>
            <div class="floor">
              <article><span class="badge">In session</span><h3>Industrial Electrical</h3><small>8:00 AM · Lab A · 8/8 seats</small><div class="checklist"><span>✓ Curriculum loaded</span><span>✓ Roster verified</span><span>✓ Instructor confirmed</span><span>✓ Equipment ready</span></div></article>
              <article><span class="badge">Ready</span><h3>PLC Fundamentals</h3><small>1:00 PM · Controls Lab · 6/8 seats</small><div class="checklist"><span>✓ Curriculum loaded</span><span>✓ Roster verified</span><span>✓ Instructor confirmed</span><span>✓ Equipment ready</span></div></article>
              <article class="warn"><span class="badge warn">Action required</span><h3>HVAC/R — Chillers</h3><small>6:00 PM · Lab B · 5/8 seats</small><div class="checklist"><span>✓ Curriculum loaded</span><span>✓ Roster verified</span><span class="fail">✕ Instructor unassigned</span><span class="fail">✕ Chiller rig offline</span></div></article>
            </div>
          </article>
          <div class="grid equal">
            <article class="card"><div class="card-head"><div><h2>Equipment Status</h2><p>Hands-on training stations</p></div></div><table><tbody>
              <tr><td>Electrical boards</td><td>8 / 8 online</td><td><span class="badge">Operational</span></td></tr>
              <tr><td>PLC / HMI stations</td><td>8 / 8 online</td><td><span class="badge">Operational</span></td></tr>
              <tr><td>HVAC package trainers</td><td>4 / 4 online</td><td><span class="badge">Operational</span></td></tr>
              <tr><td>Chiller training rig</td><td>0 / 1 online</td><td><span class="badge warn">Service required</span></td></tr>
            </tbody></table></article>
            <article class="card"><div class="card-head"><div><h2>Quality Controls</h2><p>Student experience and delivery</p></div></div><table><tbody>
              <tr><td>Modules completed</td><td><strong>87 / 92</strong></td></tr>
              <tr><td>Skills assessments passed</td><td><strong>91%</strong></td></tr>
              <tr><td>Safety compliance</td><td><strong>100%</strong></td></tr>
              <tr><td>Completion forecast</td><td><strong>94%</strong></td></tr>
            </tbody></table></article>
          </div>
        </section>

        <section class="view" id="finance">
          <div class="kpis">
            <div class="kpi"><label>Cash Collected</label><strong>$142,680</strong><small>Month to date</small></div>
            <div class="kpi"><label>Accounts Receivable</label><strong>$18,600</strong><small class="down">12.5% of billed tuition</small></div>
            <div class="kpi"><label>Gross Margin</label><strong>61%</strong><small>$87,035 gross profit</small></div>
            <div class="kpi"><label>Operating Reserve</label><strong>6.8 mo</strong><small class="up">Expansion gate passed</small></div>
          </div>
          <div class="grid">
            <article class="card">
              <div class="card-head"><div><h2>Revenue Trend</h2><p>Six-month collected revenue</p></div></div>
              <div class="bars">
                <div class="bar"><i style="height:46%"></i><small>Apr</small></div><div class="bar"><i style="height:51%"></i><small>May</small></div><div class="bar"><i style="height:57%"></i><small>Jun</small></div><div class="bar"><i style="height:66%"></i><small>Jul</small></div><div class="bar"><i style="height:77%"></i><small>Aug</small></div><div class="bar"><i style="height:92%"></i><small>Sep</small></div>
              </div>
            </article>
            <article class="card">
              <div class="card-head"><div><h2>Operating Costs</h2><p>$71,000 month to date</p></div></div>
              <div class="costs">
                <div class="cost-row"><div><span>Instructor payroll</span><strong>$31,240</strong></div><div class="progress"><i style="width:78%;background:var(--ink)"></i></div></div>
                <div class="cost-row"><div><span>Facility & utilities</span><strong>$18,400</strong></div><div class="progress"><i style="width:46%;background:#536575"></i></div></div>
                <div class="cost-row"><div><span>Advertising</span><strong>$12,400</strong></div><div class="progress"><i style="width:31%;background:#7d8e9d"></i></div></div>
                <div class="cost-row"><div><span>Supplies & equipment</span><strong>$8,960</strong></div><div class="progress"><i style="width:22%;background:#aab6be"></i></div></div>
              </div>
            </article>
          </div>
          <article class="card"><div class="card-head"><div><h2>Four Revenue Engines</h2><p>Protect core cash flow while building higher-value channels</p></div></div><div class="table-wrap"><table>
            <thead><tr><th>Engine</th><th>Revenue</th><th>Share</th><th>Status</th><th>Programs</th></tr></thead>
            <tbody>
              <tr><td><strong>Core training</strong></td><td>$88,240</td><td>62%</td><td><span class="badge">Stable</span></td><td>Electrical · HVAC/R · PLC</td></tr>
              <tr><td><strong>Advanced programs</strong></td><td>$27,500</td><td>19%</td><td><span class="badge">Growing</span></td><td>HMI · SCADA · Chillers</td></tr>
              <tr><td><strong>Specialty training</strong></td><td>$11,940</td><td>8%</td><td><span class="badge warn">Build</span></td><td>VFDs · Rectifiers · Troubleshooting</td></tr>
              <tr><td><strong>B2B training</strong></td><td>$15,000</td><td>11%</td><td><span class="badge warn">Priority</span></td><td>Employer cohorts · Custom programs</td></tr>
            </tbody>
          </table></div></article>
        </section>

        <section class="view" id="people">
          <div class="kpis">
            <div class="kpi"><label>Active Staff</label><strong>4</strong><small>2 instructors · 1 admin · 1 lab</small></div>
            <div class="kpi"><label>Instructor Utilization</label><strong>74%</strong><small>Healthy range</small></div>
            <div class="kpi"><label>Student Satisfaction</label><strong>4.8 / 5</strong><small>58 responses</small></div>
            <div class="kpi"><label>Safety Streak</label><strong>47 days</strong><small class="up">0 incidents</small></div>
          </div>
          <div class="grid equal">
            <article class="card">
              <div class="card-head"><div><h2>Team Coverage</h2><p>Roles and operating load</p></div></div>
              <div class="staff">
                <div class="person"><div class="avatar">PS</div><div><strong>Pete Sotelo</strong><small>Owner / Lead Instructor</small></div><div class="progress"><i style="width:82%;background:var(--ink)"></i></div><span class="badge">On site</span></div>
                <div class="person"><div class="avatar">MR</div><div><strong>Marco Ruiz</strong><small>PLC Instructor</small></div><div class="progress"><i style="width:68%;background:var(--ink)"></i></div><span class="badge">In class</span></div>
                <div class="person"><div class="avatar">AL</div><div><strong>Ana Lopez</strong><small>Enrollment / Admin</small></div><div class="progress"><i style="width:74%;background:var(--ink)"></i></div><span class="badge">Available</span></div>
                <div class="person"><div class="avatar">JT</div><div><strong>James Tran</strong><small>Lab Assistant</small></div><div class="progress"><i style="width:61%;background:var(--ink)"></i></div><span class="badge">On site</span></div>
              </div>
            </article>
            <article class="card">
              <div class="card-head"><div><h2>Owner Dependency</h2><p>Move these functions off Pete before scaling</p></div><span class="badge warn">63% delegated</span></div>
              <table><tbody>
                <tr><td>Equipment diagnostics</td><td><span class="badge warn">Founder only</span></td></tr>
                <tr><td>Employer partnerships</td><td><span class="badge warn">Founder only</span></td></tr>
                <tr><td>Curriculum approval</td><td><span class="badge warn">Founder only</span></td></tr>
                <tr><td>Lead follow-up</td><td><span class="badge">Delegated</span></td></tr>
              </tbody></table>
            </article>
          </div>
        </section>

        <section class="view" id="growth">
          <div class="growth-hero"><div><label>CURRENT PHASE · YEAR 1</label><h2>Prove the Operating Machine</h2><p>Standardize delivery, increase practical seat utilization, and reduce founder dependency before adding fixed overhead.</p></div><div class="phase"><strong>68% complete</strong><div class="progress"><i style="width:68%"></i></div></div></div>
          <div class="roadmap">
            <article class="active"><small>YEAR 1 · PROVE</small><h3>Build the operating machine</h3><ul><li>Repeatable curriculum</li><li>Reliable sales funnel</li><li>70–80% utilization</li></ul></article>
            <article><small>YEAR 2 · OPTIMIZE</small><h3>Increase capacity</h3><ul><li>Fill off-peak sessions</li><li>Build instructor bench</li><li>Grow B2B revenue</li></ul></article>
            <article><small>YEAR 3 · REPLICATE</small><h3>Launch location two</h3><ul><li>Pass expansion gates</li><li>Install location manager</li><li>Duplicate labs and SOPs</li></ul></article>
            <article><small>YEAR 4 · REGIONALIZE</small><h3>Centralize support</h3><ul><li>2–4 training hubs</li><li>Shared enrollment</li><li>Corporate training</li></ul></article>
            <article><small>YEAR 5 · SCALE</small><h3>Expand the platform</h3><ul><li>Licensing options</li><li>Workforce contracts</li><li>Hybrid theory + labs</li></ul></article>
          </div>
          <div class="grid">
            <article class="card">
              <div class="card-head"><div><h2>Expansion Readiness Gate</h2><p>Maintain all six for 3–6 months</p></div><span class="badge warn" id="gateScore">4 / 6 passed</span></div>
              <div class="gate-list" id="gateList">
                <div class="gate" id="utilGate"><b>✕</b><div><strong>80%+ prime-time utilization</strong><small id="utilDetail">79% current</small></div></div>
                <div class="gate pass"><b>✓</b><div><strong>Consistent profitability</strong><small>6 profitable months</small></div></div>
                <div class="gate pass"><b>✓</b><div><strong>Six-month cash reserve</strong><small>6.8 months current</small></div></div>
                <div class="gate pass"><b>✓</b><div><strong>Lead volume supports expansion</strong><small>186 leads per month</small></div></div>
                <div class="gate" id="managerGate"><b>✕</b><div><strong>Runs without founder</strong><small>Not yet verified</small></div></div>
                <div class="gate pass"><b>✓</b><div><strong>Instructor pipeline exists</strong><small>2 candidates ready</small></div></div>
              </div>
            </article>
            <article class="card sim">
              <div class="card-head"><div><h2>Readiness Simulator</h2><p>Test expansion conditions</p></div></div>
              <label>Prime-time utilization: <strong id="utilValue">79%</strong></label>
              <input id="utilSlider" type="range" min="50" max="100" value="79">
              <label class="sim-check"><input id="managerCheck" type="checkbox"> Location manager can operate five days without Pete</label>
              <div class="big-result"><small>Expansion readiness</small><strong id="readinessValue">67%</strong></div>
            </article>
          </div>
        </section>

        <section class="view" id="weekly">
          <div class="management-hero"><div><small>MANAGEMENT RHYTHM</small><h2>Weekly Review</h2><p>Review performance, identify missed objectives, and assign the next actions before the new operating week begins.</p></div><span class="badge">30–45 minute review</span></div>
          <div class="kpis">
            <div class="kpi"><label>Mission Focus</label><strong>18</strong><small class="up">+4 vs. last week</small></div>
            <div class="kpi"><label>Noise</label><strong>3</strong><small class="up">−2 missed objectives</small></div>
            <div class="kpi"><label>Weekly Score</label><strong>86%</strong><small class="up">+11 points</small></div>
            <div class="kpi"><label>Open Actions</label><strong>5</strong><small>2 due within 48 hours</small></div>
          </div>
          <article class="card" style="margin-bottom:17px">
            <div class="card-head"><div><h2>Monday Management Agenda</h2><p>One decision per exception—avoid reviewing healthy metrics in detail</p></div><span class="badge">Week 36</span></div>
            <div class="review-grid">
              <article><h3>Sales & Enrollment</h3><ul><li>186 leads generated</li><li>19 students enrolled</li><li>36 qualified leads need consultation</li><li>Decision: add Friday call block</li></ul></article>
              <article><h3>Operations & Quality</h3><ul><li>79% seat utilization</li><li>94% completion forecast</li><li>Chiller training rig offline</li><li>Decision: service before next cohort</li></ul></article>
              <article><h3>Finance & Capacity</h3><ul><li>61% gross margin</li><li>6.8-month cash reserve</li><li>Ad spend 8% above plan</li><li>Decision: retain channel, cap CPL at $45</li></ul></article>
            </div>
          </article>
          <div class="grid equal">
            <article class="card"><div class="card-head"><div><h2>Wins to Repeat</h2><p>Actions that created measurable progress</p></div></div><label class="task done"><input type="checkbox" checked><span>Evening PLC session reached 88% utilization</span></label><label class="task done"><input type="checkbox" checked><span>Employer partner booked an eight-seat cohort</span></label><label class="task done"><input type="checkbox" checked><span>Student completion remained above 90%</span></label></article>
            <article class="card"><div class="card-head"><div><h2>Next Seven Days</h2><p>Critical commitments for the upcoming week</p></div></div><label class="task"><input type="checkbox"><span>Restore chiller training rig</span></label><label class="task"><input type="checkbox"><span>Confirm backup HVAC/R instructor</span></label><label class="task"><input type="checkbox"><span>Convert six qualified leads</span></label><label class="task"><input type="checkbox"><span>Finish enrollment and refund SOPs</span></label></article>
          </div>
        </section>

        <section class="view" id="progress">
          <div class="management-hero"><div><small>MISSION CONTROL</small><h2>Progress Tracker</h2><p>Every objective begins as a planned Mission Focus task. Complete it to earn Mission Focus; miss it and that same task becomes Noise.</p></div><span class="badge" id="periodLabel">Today</span></div>
          <div class="period-tabs" id="periodTabs">
            <button class="active" data-period="day">Day</button>
            <button data-period="week">Week</button>
            <button data-period="month">Month</button>
            <button data-period="q1">Q1</button>
            <button data-period="q2">Q2</button>
            <button data-period="q3">Q3</button>
            <button data-period="q4">Q4</button>
            <button data-period="year">Annual</button>
          </div>
          <div class="tug-card">
            <div class="tug-heading"><div><h2>Mission Focus vs. Noise</h2><p>Completed Mission Focus tasks pull toward Focus. Missed Mission Focus tasks pull toward Noise. Planned tasks remain unscored.</p></div><span class="tug-result" id="tugResult">No decided objectives</span></div>
            <div class="tug-labels">
              <div class="focus-side"><strong id="focusPercentLabel">0%</strong><span>MISSION FOCUS</span></div>
              <div class="noise-side"><span>NOISE</span><strong id="noisePercentLabel">0%</strong></div>
            </div>
            <div class="tug-bar">
              <i class="focus-fill" id="focusFill" style="width:0%"><span>MISSION FOCUS</span></i>
              <i class="noise-fill" id="noiseFill" style="width:0%"><span>NOISE</span></i>
              <b class="tug-knot" id="tugKnot" style="left:50%"></b>
            </div>
            <div class="tug-scale"><span>Focus pulls left</span><span>Dominant percentage controls the space</span><span>Noise pulls right</span></div>
          </div>
          <div class="kpis">
            <div class="kpi score-card"><label>Mission Focus</label><strong id="focusCount">0%</strong><small id="focusRaw">0 completed objectives</small><small id="focusCompare" class="comparison">No prior comparison</small></div>
            <div class="kpi"><label>Noise</label><strong id="noiseCount">0%</strong><small id="noiseRaw">0 missed objectives</small><small id="noiseCompare" class="comparison">No prior comparison</small></div>
            <div class="kpi"><label>Focus Advantage</label><strong id="executionScore">0 pts</strong><small id="scoreCompare" class="comparison">Focus percentage minus Noise</small></div>
            <div class="kpi"><label>Planned Mission Tasks</label><strong id="openCount">0</strong><small id="totalObjectives">0 mission tasks in period</small></div>
          </div>
          <div class="grid">
            <article class="card">
              <div class="card-head"><div><h2>Seven-Day Focus vs. Noise</h2><p>Daily completed and missed objectives</p></div></div>
              <div class="trend-chart" id="trendChart"></div>
              <div class="legend"><span><i></i>Mission Focus</span><span><i class="noise"></i>Noise</span></div>
            </article>
            <article class="card">
              <div class="card-head"><div><h2>Period Comparison</h2><p id="comparisonLabel">Today compared with yesterday</p></div></div>
              <table><tbody>
                <tr><td>Mission Focus</td><td id="currentFocus">0</td><td id="previousFocus">0 prior</td></tr>
                <tr><td>Noise</td><td id="currentNoise">0</td><td id="previousNoise">0 prior</td></tr>
                <tr><td>Execution score</td><td id="currentScore">0%</td><td id="previousScore">0% prior</td></tr>
                <tr><td>Net focus</td><td id="currentNet">0</td><td id="previousNet">0 prior</td></tr>
              </tbody></table>
              <div class="big-result" style="margin-top:14px"><small>Performance signal</small><strong id="performanceSignal">Add objectives to begin tracking</strong></div>
            </article>
          </div>
          <article class="card">
            <div class="card-head"><div><h2>Mission Focus Objectives</h2><p>Add the task as planned. Its outcome becomes Mission Focus only when completed, or Noise when missed.</p></div><span class="badge" id="savedStatus">Saved</span></div>
            <form class="objective-form" id="objectiveForm">
              <input id="objectiveTitle" required placeholder="Planned Mission Focus task">
              <input id="objectiveDeadline" required type="datetime-local" aria-label="Scheduled completion date and time">
              <select id="objectiveCategory"><option>Operations</option><option>Enrollment</option><option>Finance</option><option>Training</option><option>Growth</option><option>Personal Leadership</option></select>
              <button type="submit">Add Mission Focus</button>
            </form>
            <p class="deadline-note">If the scheduled completion time expires before the task is completed, it automatically becomes Noise.</p>
            <div class="objective-list" id="objectiveList"></div>
          </article>
        </section>

        <section class="view" id="sops">
          <div class="management-hero"><div><small>OPERATING SYSTEM</small><h2>SOP Library</h2><p>Document the repeatable procedures required to operate location one without founder intervention and eventually replicate the school.</p></div><span class="badge">18 of 24 complete</span></div>
          <div class="kpis">
            <div class="kpi"><label>SOP Completion</label><strong>75%</strong><small>18 of 24 approved</small></div>
            <div class="kpi"><label>Needs Review</label><strong>3</strong><small>Owner approval required</small></div>
            <div class="kpi"><label>In Draft</label><strong>3</strong><small>Assigned to operations</small></div>
            <div class="kpi"><label>Founder-Only Functions</label><strong>3</strong><small class="down">Must be documented</small></div>
          </div>
          <article class="card">
            <div class="card-head"><div><h2>Management Procedures</h2><p>Core documents needed for consistent operation</p></div><button class="save-button">+ New SOP</button></div>
            <div class="sop-list">
              <div class="sop-row"><div><strong>Facility Opening & Safety Walk</strong><small>OPS-001 · Operations</small></div><span>100%</span><div class="progress"><i style="width:100%"></i></div><span class="badge">Approved</span></div>
              <div class="sop-row"><div><strong>Student Enrollment & Payment</strong><small>ADM-002 · Enrollment</small></div><span>90%</span><div class="progress"><i style="width:90%"></i></div><span class="badge warn">Review</span></div>
              <div class="sop-row"><div><strong>Instructor Class Setup</strong><small>TRN-004 · Training</small></div><span>100%</span><div class="progress"><i style="width:100%"></i></div><span class="badge">Approved</span></div>
              <div class="sop-row"><div><strong>Refund & Cancellation Handling</strong><small>FIN-006 · Finance</small></div><span>65%</span><div class="progress"><i style="width:65%"></i></div><span class="badge warn">Draft</span></div>
              <div class="sop-row"><div><strong>Equipment Diagnostic Escalation</strong><small>OPS-010 · Maintenance</small></div><span>40%</span><div class="progress"><i style="width:40%"></i></div><span class="badge warn">Founder only</span></div>
              <div class="sop-row"><div><strong>Facility Closeout & Cash Reconciliation</strong><small>OPS-012 · Operations</small></div><span>100%</span><div class="progress"><i style="width:100%"></i></div><span class="badge">Approved</span></div>
            </div>
          </article>
        </section>

        <section class="view" id="targets">
          <div class="management-hero"><div><small>OWNER CONTROL LIMITS</small><h2>Owner Settings</h2><p>Set operating thresholds, enrollment rules, capacity, and payment controls. This section belongs only on Pete's owner dashboard.</p></div><span class="badge" id="targetSaved">Owner only</span></div>
          <div class="owner-access">OWNER-ONLY ACCESS — Do not expose this section, its controls, or payment configuration in the Assistant Command Screen.</div>
          <article class="card">
            <div class="card-head"><div><h2>Operating Targets</h2><p>Saved to the system and used by every screen</p></div></div>
            <form id="targetForm" class="target-form">
              <div class="target-field"><label>Monthly enrollment target</label><input id="targetEnrollments" type="number" min="1" value="{{ $targets['target_enrollments'] + 0 }}"><small>Confirmed paid enrollments per month</small></div>
              <div class="target-field"><label>Healthy seat utilization (%)</label><input id="targetUtilization" type="number" min="1" max="100" value="{{ $targets['target_utilization'] + 0 }}"><small>Prime-time capacity threshold</small></div>
              <div class="target-field"><label>Minimum gross margin (%)</label><input id="targetMargin" type="number" min="1" max="100" value="{{ $targets['target_margin'] + 0 }}"><small>Required before expansion spending</small></div>
              <div class="target-field"><label>Operating reserve (months)</label><input id="targetReserve" type="number" min="1" step=".1" value="{{ $targets['target_reserve'] + 0 }}"><small>Minimum unrestricted cash coverage</small></div>
              <div class="target-field"><label>Monthly lead target</label><input id="targetLeads" type="number" min="1" value="{{ $targets['target_leads'] + 0 }}"><small>Marketing pipeline requirement</small></div>
              <div class="target-field"><label>Maximum noise ratio (%)</label><input id="targetNoise" type="number" min="0" max="100" value="{{ $targets['target_noise'] + 0 }}"><small>Missed objectives ÷ decided objectives</small></div>
              <button class="save-button" type="submit">Save Targets</button>
            </form>
          </article>
          <article class="card" style="margin-top:17px">
            <div class="card-head"><div><h2>Owner-Only Operating Controls</h2><p>Relevant controls retained from the retired standalone Admin Dashboard</p></div><span class="badge">Protected settings</span></div>
            <form id="ownerControlForm" class="target-form">
              <div class="target-field"><label>Seats per instructor / session</label><input id="ownerSeatsPerInstructor" type="number" min="1" value="{{ $controls['seats_per_instructor'] }}"><small>Controls practical training capacity</small></div>
              <div class="target-field"><label>Sessions per day</label><input id="ownerSessionsPerDay" type="number" value="{{ $controls['sessions_per_day'] }}" readonly><small>{{ implode(' · ', config('ptt.session_slots')) }}</small></div>
              <div class="target-field"><label>Required deposit (%)</label><input id="ownerDepositPercent" type="number" min="0" max="100" value="{{ $controls['deposit_percent'] }}"><small>Applied to seat reservations</small></div>
              <div class="target-field"><label>Seat hold duration (minutes)</label><input id="ownerSeatHold" type="number" min="1" value="{{ $controls['seat_hold_minutes'] }}"><small>Checkout reservation window</small></div>
              <div class="target-field"><label>Payment merchant</label><select id="ownerMerchant"><option @selected($controls['payment_merchant'] === 'Stripe')>Stripe</option><option @selected($controls['payment_merchant'] === 'PayPal')>PayPal</option><option @selected($controls['payment_merchant'] === 'Zelle')>Zelle</option><option @selected($controls['payment_merchant'] === 'Other')>Other</option></select><small>Active checkout provider</small></div>
              <div class="target-field"><label>Payment mode</label><select id="ownerPaymentMode"><option @selected($controls['payment_mode'] === 'Live')>Live</option><option @selected($controls['payment_mode'] === 'Test')>Test</option><option @selected($controls['payment_mode'] === 'Disabled')>Disabled</option></select><small>Secret keys remain server-side</small></div>
              <div class="target-field">
              <label>Stripe publishable key</label>
              <input id="ownerStripeKey" autocomplete="off" spellcheck="false" placeholder="{{ $controls['stripe_key'] ?? 'pk_live_…' }}">
              <small>{{ $controls['stripe_key'] ? 'Saved · leave blank to keep' : 'Not set' }}</small>
            </div>
            <div class="target-field">
              <label>Stripe secret key</label>
              <input id="ownerStripeSecret" type="password" autocomplete="new-password" placeholder="{{ $controls['stripe_secret'] ?? 'sk_live_…' }}">
              <small>{{ $controls['stripe_secret'] ? 'Saved encrypted · leave blank to keep' : 'Not set' }}</small>
            </div>
            <div class="target-field">
              <label>Stripe webhook signing secret</label>
              <input id="ownerStripeWebhook" type="password" autocomplete="new-password" placeholder="{{ $controls['stripe_webhook_secret'] ?? 'whsec_…' }}">
              <small>{{ $controls['stripe_webhook_secret'] ? 'Saved encrypted · leave blank to keep' : 'Not set' }}</small>
            </div>
            <button class="save-button" type="submit">Save Owner Controls</button>
            </form>
          </article>
        </section>
      </div>
    </main>
  </div>

  <div class="modal-backdrop" id="reopenModal" hidden>
    <form class="modal-box" id="reopenForm">
      <h2>Schedule a New Mission Deadline</h2>
      <p>Reopening requires a new future completion date and time. The task will return to Planned Mission Focus.</p>
      <label for="reopenDeadline">New completion date and time</label>
      <input id="reopenDeadline" type="datetime-local" required>
      <p class="modal-error" id="reopenError"></p>
      <div class="modal-actions"><button type="button" id="cancelReopen">Cancel</button><button type="submit">Reopen Mission Task</button></div>
    </form>
  </div>

  <script>window.PTT = @json($bootstrap);</script>
  <script src="{{ \App\Support\Asset::url('js/owner.js') }}"></script>
</body>
</html>
