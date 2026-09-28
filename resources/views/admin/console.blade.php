<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>PACIFIC TRADE TECH™ — Assistant Command</title>
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="{{ \App\Support\Asset::url('css/admin.css') }}">
</head>
<body>
<div class="app">
<aside>
  <div class="brand">PACIFIC <span>TRADE TECH™</span></div>
  <div class="sub">ASSISTANT OPERATIONS COMMAND</div>
  <nav id="nav">
    <div class="navgroup">COMMAND</div>
    <button class="active" data-page="dashboard">Overview</button>
    <button data-page="command">Realtime Command</button>
    <div class="navgroup">ENROLLMENT</div>
    <button data-page="enrollments">Enrollments</button>
    <button data-page="students">Students</button>
    <button data-page="leads">Leads CRM</button>
    <div class="navgroup">TRAINING</div>
    <button data-page="schedule">Schedule</button>
    <button data-page="instructors">Instructors & Onboarding</button>
    <div class="navgroup">COMPLIANCE</div>
    <button data-page="waivers">Waivers</button>
    <button data-page="certificates">Certificates</button>
    <div class="navgroup">FINANCE</div>
    <button data-page="payments">Payments</button>
    <button data-page="reports">Reports</button>
  </nav>
  <div class="foot">PacificTT.com<br>© 2026 PACIFIC TRADE TECH™</div>
</aside>

<main>
<div class="top">
 <div><h1 id="pageTitle">Assistant Overview</h1><p>Daily operations, enrollment, training, compliance, finance, and realtime company command.</p></div>
 <div class="actions"><button class="btn light" type="button" onclick="exportEnrollments()">Export</button><button class="btn gold" type="button" onclick="newEnrollment()">+ New Enrollment</button></div>
</div>

<section class="page active" id="dashboard">
 <div class="cards">
  <div class="card"><div class="k">Revenue Today</div><div class="v">${{ number_format($metrics['revenue_today'], 0) }}</div><div class="delta">{{ $metrics['paid_enrollments'] }} paid enrollments</div></div>
  <div class="card"><div class="k">Students Today</div><div class="v">{{ $metrics['students_today'] }}</div><div class="delta">{{ $metrics['sessions_today'] }} sessions scheduled</div></div>
  <div class="card"><div class="k">Seat Occupancy</div><div class="v" id="occMetric">{{ $metrics['occupancy'] }}%</div><div class="delta" id="seatMetric">{{ $metrics['booked'] }} / {{ $metrics['capacity'] }} booked</div></div>
  <div class="card"><div class="k">Outstanding</div><div class="v">${{ number_format($metrics['outstanding'], 0) }}</div><div class="delta">{{ $metrics['balances_due'] }} balances due</div></div>
  <div class="card"><div class="k">New Leads</div><div class="v">{{ $metrics['new_leads'] }}</div><div class="delta">{{ $metrics['need_contact'] }} need contact</div></div>
 </div>
 <div class="grid2">
  <div class="panel today">
   <h2>Today — Training Operations</h2>
   <div class="todaygrid">
    <div class="todaymetric">
     <div class="k">Available Capacity</div>
     <div class="metric-accent"></div>
     <div class="v" id="todayCapacity">{{ $todayOps['capacity'] }}</div>
     <div class="metric-mark">{{ $todayOps['capacity'] }}</div>
    </div>
    <div class="todaymetric">
     <div class="k">Seats Remaining</div>
     <div class="metric-accent"></div>
     <div class="v" id="todayRemaining">{{ $todayOps['remaining'] }}</div>
     <div class="metric-mark">{{ $todayOps['remaining'] }}</div>
    </div>
    <div class="todaymetric">
     <div class="k">Core</div>
     <div class="metric-accent"></div>
     <div class="v">{{ $todayOps['core'] }}</div>
     <div class="metric-mark">C</div>
    </div>
    <div class="todaymetric">
     <div class="k">Advanced</div>
     <div class="metric-accent"></div>
     <div class="v">{{ $todayOps['advanced'] }}</div>
     <div class="metric-mark">A</div>
    </div>
   </div>
  </div>
  <div class="panel"><h2>Action Required</h2>
   @if($actions['waivers_to_review'])<div class="alert bad">{{ $actions['waivers_to_review'] }} student waiver{{ $actions['waivers_to_review'] == 1 ? '' : 's' }} require{{ $actions['waivers_to_review'] == 1 ? 's' : '' }} review</div>@endif
   @if($actions['balances_today'])<div class="alert">{{ $actions['balances_today'] }} onsite balance{{ $actions['balances_today'] == 1 ? '' : 's' }} due today</div>@endif
   @if($actions['leads_uncontacted'])<div class="alert">{{ $actions['leads_uncontacted'] }} new lead{{ $actions['leads_uncontacted'] == 1 ? '' : 's' }} {{ $actions['leads_uncontacted'] == 1 ? 'has' : 'have' }} not been contacted</div>@endif
   @if($actions['certs_ready'])<div class="alert good">{{ $actions['certs_ready'] }} certificate{{ $actions['certs_ready'] == 1 ? '' : 's' }} ready to issue</div>@endif
   @if(!array_filter($actions))<div class="alert good">Nothing needs attention right now.</div>@endif
  </div>
 </div>
 <div class="grid2">
  <div class="panel"><h2>Today's Sessions</h2><table><thead><tr><th>Time</th><th>Instructor</th><th>Booked</th><th>Available</th><th>Status</th></tr></thead><tbody id="dashSessions"></tbody></table></div>
  <div class="panel"><h2>Enrollment Funnel</h2><div class="funnel">
   @php $peak = max(1, collect($funnel)->filter(fn($v)=>is_array($v))->max('value')); @endphp
   @foreach(collect($funnel)->filter(fn($v)=>is_array($v)) as $step)
    <div style="width:{{ max(30, round($step['value'] / $peak * 100)) }}%">{{ number_format($step['value']) }} {{ $step['label'] }}</div>
   @endforeach
  </div><p class="note"><b>{{ $funnel['conversion'] }}%</b> lead-to-paid conversion.</p></div>
 </div>

  <div class="panel assistant-scope" style="margin-top:14px">
    <div><b>Assistant operational access</b><span>All day-to-day Admin functions are available here. Owner Settings and merchant/configuration controls are intentionally excluded.</span></div>
    <button class="btn gold" data-jump="command">Open Realtime Command</button>
  </div>
</section>

<section class="page" id="enrollments"><div class="panel"><h2>Enrollment Management</h2><div class="toolbar">
   <input id="enrollSearch" placeholder="Search name, email, phone or reference">
   <select id="enrollStatus">
     <option value="">All statuses</option>
     <option value="started">Started</option>
     <option value="waiver_signed">Waiver signed</option>
     <option value="deposit_paid">Deposit</option>
     <option value="paid">Paid</option>
     <option value="completed">Completed</option>
     <option value="cancelled">Cancelled</option>
     <option value="abandoned">Abandoned</option>
   </select>
   <button class="btn" type="button" onclick="filterEnrollments()">Search</button>
   <button class="btn light" type="button" onclick="exportEnrollments()">Export CSV</button>
   <button class="btn gold" type="button" onclick="newEnrollment()">+ New Enrollment</button>
  </div><table><thead><tr><th>ID</th><th>Student</th><th>Program</th><th>Date / Session</th><th>Paid</th><th>Waiver</th><th>Status</th><th></th></tr></thead><tbody id="enrollRows">
@forelse($enrollments as $e)
@php $w = $e->waiver; $bal = $e->balanceCents(); @endphp
<tr data-enrollment="{{ $e->id }}" data-status="{{ $e->status }}"><td>{{ $e->reference }}</td><td>{{ $e->name }}</td><td>{{ $e->program?->short_name ?? $e->program?->name }}</td>
 <td>{{ $e->preferred_date?->format('M j') }} · {{ $e->classSession?->label }}</td>
 <td>@if($bal === 0)<span class="pill paid">PAID</span>@else<span class="pill due">${{ number_format($bal / 100, 2) }} DUE</span>@endif</td>
 <td>@if(!$w)<span class="pill missing">MISSING</span>@elseif($w->needs_review)<span class="pill missing">REVIEW</span>@else<span class="pill paid">SIGNED</span>@endif</td>
 <td>{{ $e->statusLabel() }}</td>
 <td><div class="rowactions"><button class="btn light" type="button" onclick="editEnrollment({{ $e->id }})">Open</button></div></td></tr>
@empty
<tr><td colspan="8" class="note">No enrollments yet.</td></tr>
@endforelse
<tr id="enrollEmpty" style="display:none"><td colspan="8" class="note">Nothing matches that search.</td></tr>
</tbody></table></div></section>

<section class="page" id="schedule">
 <div class="logic"><strong>LIVE SEAT RULE:</strong> Public calendar capacity is calculated from instructors actually available for that date/session. <b>1 instructor = 8 seats, 2 = 16 seats, 3 = 24 seats</b>. Closed/unavailable instructors contribute zero seats. Booked seats are then subtracted to show real availability.</div>
 <div class="panel"><div class="toolbar"><label>Date <input type="date" id="schedDate" value="{{ today()->toDateString() }}" onchange="renderSchedule()"></label><label>Available instructors <select id="instructorCount"><option value="1">1 instructor</option><option value="2" selected>2 instructors</option><option value="3">3 instructors</option><option value="4">4 instructors</option></select></label><button class="btn gold" onclick="renderSchedule()">Apply Capacity</button></div>
 <div class="sessions" id="sessionGrid"></div></div>
</section>

<section class="page" id="students"><div class="panel"><h2>Student Database</h2><table><thead><tr><th>Student ID</th><th>Name</th><th>Phone</th><th>Courses</th><th>Completed</th><th>Total Paid</th></tr></thead><tbody>@forelse($students as $s)
<tr><td>{{ $s->email }}</td><td>{{ $s->name }}</td><td>{{ $s->phone }}</td>
 <td>{{ $s->courses }}</td><td>{{ $s->completed }}</td><td>${{ number_format($s->total_paid, 2) }}</td></tr>
@empty
<tr><td colspan="6" class="note">No students yet.</td></tr>
@endforelse</tbody></table></div></section>

<section class="page" id="leads">
 <div class="cards">
  <div class="card"><div class="k">New Leads</div><div class="v" id="leadNew">{{ $leadMetrics['new'] }}</div><div class="delta">{{ $metrics['need_contact'] }} need contact</div></div>
  <div class="card"><div class="k">Contacted</div><div class="v">{{ $leadMetrics['contacted'] }}</div><div class="delta">This month</div></div>
  <div class="card"><div class="k">Enrollment Started</div><div class="v">{{ $leadMetrics['started'] }}</div><div class="delta">Active prospects</div></div>
  <div class="card"><div class="k">Paid Enrollments</div><div class="v">{{ $leadMetrics['paid'] }}</div><div class="delta">From lead pipeline</div></div>
  <div class="card"><div class="k">Lead → Paid</div><div class="v">{{ $leadMetrics['rate'] }}%</div><div class="delta">Conversion rate</div></div>
 </div>

 <div class="panel" style="margin-top:14px">
  <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;margin-bottom:12px">
   <div><h2 style="margin:0">Lead Pipeline</h2><div class="note">Capture prospects before enrollment and track every follow-up through payment.</div></div>
   <button class="btn gold" onclick="newLead()">+ Add Lead</button>
  </div>
  <div class="lead-stage"><span class="stage active">NEW</span><span class="stage">CONTACTED</span><span class="stage">INTERESTED</span><span class="stage">ENROLLMENT STARTED</span><span class="stage">PAID</span><span class="stage">LOST</span></div>
  <div class="toolbar">
   <input id="leadSearch" placeholder="Search name, phone or email">
   <select id="leadStatusFilter" onchange="filterLeadRows()"><option value="">All statuses</option><option>New</option><option>Contacted</option><option>Interested</option><option>Enrollment Started</option><option>Paid</option><option>Lost</option></select>
   <select><option>All sources</option><option>Google</option><option>Instagram</option><option>Facebook</option><option>TikTok</option><option>Referral</option><option>Walk-In</option><option>Employer</option><option>Organic</option></select>
   <button class="btn" onclick="filterLeadRows()">Search</button>
  </div>
  <table><thead><tr><th>Lead</th><th>Contact</th><th>Program</th><th>Preferred</th><th>Source</th><th>Status</th><th>Follow-Up</th><th></th></tr></thead>
  <tbody id="leadRows"></tbody></table>
 </div>

 <div class="lead-grid" style="margin-top:14px">
  <div class="panel lead-form">
   <h2 id="leadFormTitle">Lead Details</h2>
   <input type="hidden" id="leadId">
   <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
    <div><label>Name *</label><input id="leadName" placeholder="Full name"></div>
    <div><label>Phone *</label><input id="leadPhone" placeholder="(000) 000-0000"></div>
   </div>
   <label>Email</label><input id="leadEmail" type="email" placeholder="student@email.com">
   <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
    <div><label>Electrical Experience</label><select id="leadElectrical"><option>None</option><option>Beginner</option><option>1–3 Years</option><option>3–5 Years</option><option>5+ Years</option></select></div>
    <div><label>PLC Experience</label><select id="leadPLC"><option>None</option><option>Beginner</option><option>Some Experience</option><option>Experienced</option></select></div>
   </div>
   <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
    <div><label>Desired Program</label><select id="leadProgram"><option>Core — $1,495</option><option>Advanced — $2,500</option><option>Undecided</option></select></div>
    <div><label>Lead Source</label><select id="leadSource"><option>Google</option><option>Instagram</option><option>Facebook</option><option>TikTok</option><option>Organic</option><option>Referral</option><option>Employer</option><option>Walk-In</option><option>Other</option></select></div>
   </div>
   <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
    <div><label>Preferred Date</label><input id="leadDate" type="date"></div>
    <div><label>Preferred Session</label><select id="leadSession"><option>8:00 AM – 12:00 PM</option><option>12:00 PM – 4:00 PM</option><option>4:00 PM – 8:00 PM</option><option>Flexible</option></select></div>
   </div>
   <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
    <div><label>Status</label><select id="leadStatus"><option>New</option><option>Contacted</option><option>Interested</option><option>Enrollment Started</option><option>Paid</option><option>Lost</option></select></div>
    <div><label>Next Follow-Up</label><input id="leadFollow" type="datetime-local"></div>
   </div>
   <label>Notes</label><textarea id="leadNotes" placeholder="Call notes, training goals, questions, employer information..."></textarea>
   <div class="lead-actions"><button class="btn gold" onclick="saveLead()">Save Lead</button><button class="btn" onclick="markContacted()">Mark Contacted</button><button class="btn" onclick="startEnrollmentFromLead()">Start Enrollment</button><button class="btn light" onclick="newLead()">Clear</button></div>
  </div>

  <div>
   <div class="panel"><h2>Follow-Up Queue</h2>
    <div class="alert bad">4 new leads need first contact</div>
    <div class="alert">3 follow-ups due today</div>
    <div class="alert">2 interested leads have no scheduled date</div>
    <div class="alert good">5 leads converted this week</div>
    <p class="note">Production backend should record every call/text/email attempt with staff member and timestamp.</p>
   </div>
   <div class="panel"><h2>Lead Sources — MTD</h2>
    @forelse($leadSources as $src)
    <div class="sourcebar"><b>{{ $src['source'] }}</b><div class="bar"><div class="fill" style="width:{{ $src['percent'] }}%"></div></div><b>{{ $src['total'] }}</b></div>
    @empty
    <p class="note">No leads recorded this month.</p>
    @endforelse
   </div>
  </div>
 </div>
</section>
<section class="page" id="payments"><div class="logic"><strong>ZELLE RULE:</strong> Zelle transactions are recorded as <b>PENDING VERIFICATION</b> and do not count as paid until authorized staff confirms the funds were received. Intended primarily for walk-in/in-house payments.</div><div class="cards"><div class="card"><div class="k">Collected Today</div><div class="v">${{ number_format($payMetrics['today'], 0) }}</div></div><div class="card"><div class="k">MTD</div><div class="v">${{ number_format($payMetrics['mtd'], 0) }}</div></div><div class="card"><div class="k">Outstanding</div><div class="v">${{ number_format($payMetrics['outstanding'], 0) }}</div></div><div class="card"><div class="k">Refunds</div><div class="v">${{ number_format($payMetrics['refunds'], 0) }}</div></div></div><div class="panel" style="margin-top:14px"><h2>Zelle Verification Queue</h2>
<table><thead><tr><th>Enrollment</th><th>Student</th><th>Amount</th><th>Reference / Note</th><th>Status</th><th>Staff Action</th></tr></thead>
<tbody>
@forelse($zelleQueue as $p)
<tr data-payment="{{ $p->id }}"><td>{{ $p->enrollment?->reference }}</td><td>{{ $p->enrollment?->name }}</td>
 <td>${{ number_format($p->amount_cents / 100, 2) }}</td><td>{{ $p->reference_note }}</td>
 <td><span class="pill due">PENDING VERIFICATION</span></td>
 <td><button class="btn gold" onclick="verifyZelle({{ $p->id }})">Verify Received</button></td></tr>
@empty
<tr><td colspan="6" class="note">Nothing awaiting Zelle verification.</td></tr>
@endforelse
</tbody></table>
<p class="note">Verification should eventually store the staff member, verification date/time, amount confirmed, payment reference, and an audit-log entry. A student should not be checked in as paid based only on a screenshot or unverified payment claim.</p>
</div>
<div class="panel" style="margin-top:14px"><h2>Transactions</h2><p class="note">Full card payment, 30% deposit / balance onsite, or verified Zelle. Backend should calculate deposits and remaining balances automatically from the selected program price.</p></div></section>

<section class="page" id="waivers">
 <div class="cards">
  <div class="card"><div class="k">Signed by Students</div><div class="v">{{ $waiverStats['signed'] }}</div></div>
  <div class="card"><div class="k">Staff Acceptance Due</div><div class="v" id="waiverDue">{{ $waiverStats['staff_due'] }}</div></div>
  <div class="card"><div class="k">Fully Executed</div><div class="v" id="waiverExecuted">{{ $waiverStats['executed'] }}</div></div>
  <div class="card"><div class="k">Requires Review</div><div class="v">{{ $waiverStats['review'] }}</div></div>
 </div>
 <div class="panel" style="margin-top:14px">
  <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;margin-bottom:12px">
   <div><h2 style="margin:0">Waiver Management</h2><div class="note">Every new enrollment requires student execution before checkout and PACIFIC TRADE TECH™ staff acceptance afterward.</div></div>
  </div>
  <div class="toolbar"><input id="waiverSearch" placeholder="Search student or enrollment #"><select><option>All statuses</option><option>Staff Acceptance Due</option><option>Fully Executed</option><option>Review</option></select><button class="btn" onclick="filterWaivers()">Search</button></div>
  <table><thead><tr><th>Enrollment</th><th>Student</th><th>Program</th><th>Student Signed</th><th>PTT Acceptance</th><th>Status</th><th></th></tr></thead>
  <tbody id="waiverRows">
   @forelse($waivers as $w)
   @php $e = $w->enrollment; $done = (bool) $w->staff_accepted_on; @endphp
   <tr><td>{{ $e?->reference }}</td><td>{{ $w->legal_name }}</td><td>{{ $e?->program?->short_name ?? $e?->program?->name }}</td>
    <td>{{ $w->signed_at?->format('M j · g:i A') }}</td>
    <td>{{ $done ? $w->staff_name.' · '.$w->staff_accepted_on->format('M j') : 'Pending' }}</td>
    <td>@if($w->needs_review)<span class="pill missing">REVIEW</span>@elseif($done)<span class="pill paid">EXECUTED</span>@else<span class="pill due">STAFF DUE</span>@endif</td>
    <td><button class="btn light" onclick="openWaiver({{ $w->id }})">{{ $done ? 'View' : 'Open' }}</button></td></tr>
   @empty
   <tr><td colspan="7" class="note">No waivers yet.</td></tr>
   @endforelse
  </tbody></table>
 </div>

 <div class="waiver-grid" style="margin-top:14px">
  <div class="panel">
   <h2>Enrollment Waiver</h2>
   <div class="waiver-card"><div class="k">Enrollment</div><div class="v" style="font-size:18px" id="wvEnrollment">{{ $waivers->first()?->enrollment?->reference ?? '—' }}</div></div>
   <div class="waiver-card"><b id="wvStudent">{{ $waivers->first()?->legal_name ?? '—' }}</b><div class="note" id="wvProgram">{{ $waivers->first()?->enrollment?->program?->name ?? '—' }}</div></div>
   <div class="waiver-card"><b>Student Execution</b><div class="note">Student signature: <b>{{ $waivers->first()?->signature_path ? 'ON FILE' : 'NOT ON FILE' }}</b><br>Automatically timestamped: <b>{{ $waivers->first()?->signed_at?->format('F j, Y · g:i A') ?? '—' }}</b><br>Waiver version: <b>PTT-WVR-2026.1</b></div></div>
   <div class="waiver-card"><b>Required Acknowledgments</b><div class="note">✓ Training scope acknowledged<br>✓ Assumption of risk acknowledged<br>✓ Safety responsibilities acknowledged<br>✓ Liability terms acknowledged<br>✓ Cancellation/refund terms acknowledged</div></div>
   <p class="note">Student-signed waiver data is treated as read-only in the admin interface. Staff acceptance is stored separately with the enrollment audit record.</p>
  </div>

  <div class="panel">
   <div class="staff-accept" id="staffBox">
    <h2 style="margin:0">PACIFIC TRADE TECH™ ACCEPTANCE</h2>
    <p class="note"><b>REQUIRED FOR EACH NEW ENROLLMENT.</b> To be completed by authorized staff after reviewing the student's executed waiver.</p>
    <label>Representative Name *</label>
    <input id="repName" placeholder="Authorized staff full name">
    <label>Representative Signature *</label>
    <div class="signature-pad" id="repSignature" onclick="signRep()">Click to apply staff signature</div>
    <input type="hidden" id="repSigned" value="">
    <label>Date *</label>
    <input id="repDate" type="date">
    <div class="note" style="margin-top:8px">Date defaults to today's date when staff signs and is required before acceptance can be completed.</div>
    <button class="btn gold" style="width:100%;margin-top:15px" onclick="completeAcceptance()">COMPLETE STAFF ACCEPTANCE</button>
   </div>
   <div class="verifybox" id="acceptComplete" style="display:none"><b>✓ WAIVER FULLY EXECUTED</b><br><span id="acceptSummary"></span></div>
  </div>
 </div>
</section>

<section class="page" id="certificates">
 <div class="cert-cards">
  <div class="card"><div class="k">Certificates Issued</div><div class="v">{{ $certStats['issued'] }}</div></div>
  <div class="card"><div class="k">Ready to Issue</div><div class="v">{{ $certStats['ready'] }}</div></div>
  <div class="card"><div class="k">Issued This Month</div><div class="v">{{ $certStats['this_month'] }}</div></div>
  <div class="card"><div class="k">Revoked</div><div class="v">{{ $certStats['revoked'] }}</div></div>
 </div>
 <div class="panel">
  <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;margin-bottom:12px">
   <div><h2 style="margin:0">Certificate Management</h2><div class="note">Issue, verify, reprint and manage Certificates of Completion.</div></div>
   <button class="btn gold" type="button" onclick="issueCertificate()">+ Issue Certificate</button>
  </div>
  <div class="toolbar">
   <input id="certSearch" placeholder="Search name or certificate #">
   <select><option>All programs</option><option>Core — PLC + Electrical Controls</option><option>Advanced — PLC / Automation + HMI</option></select>
   <select><option>All statuses</option><option>Issued</option><option>Ready</option><option>Revoked</option></select>
   <button class="btn" onclick="filterCertRows()">Search</button>
  </div>
  <table><thead><tr><th>Certificate #</th><th>Student</th><th>Program</th><th>Completed</th><th>Instructor</th><th>Status</th><th>Registry</th></tr></thead>
  <tbody id="certRows">
   @forelse($certificates as $c)
   <tr onclick="selectCert(@js($c->student_name), @js($c->certificate_number), @js($c->course), @js($c->completed_on?->format('F j, Y')), @js($c->instructor?->name ?? '—'))" style="cursor:pointer">
    <td>{{ $c->status === 'ready' ? 'Pending' : $c->certificate_number }}</td><td>{{ $c->student_name }}</td>
    <td>{{ $c->course }}</td><td>{{ $c->completed_on?->format('M j, Y') }}</td>
    <td>{{ $c->instructor?->name ?? '—' }}</td>
    <td>@if($c->status === 'issued')<span class="pill paid">ISSUED</span>@elseif($c->status === 'ready')<span class="pill due">READY</span>@else<span class="pill missing">REVOKED</span>@endif</td>
    <td>{{ $c->is_public ? 'Published' : 'Not published' }}</td></tr>
   @empty
   <tr><td colspan="7" class="note">No certificates yet.</td></tr>
   @endforelse
  </tbody></table>
 </div>

 <div class="cert-grid">
  <div class="panel"><h2>Certificate Preview</h2>
   <div class="certificate-preview">
    <div class="certbrand">PACIFIC <span>TRADE TECH™</span></div>
    <div class="certtitle">Certificate of Completion</div>
    <div class="note">This certifies that</div>
    <div class="certname" id="certName">{{ $certificates->first()?->student_name ?? '—' }}</div>
    <div class="note" style="margin-top:14px">has successfully completed the 4-hour standalone training course</div>
    <div class="certcourse" id="certCourse">{{ $certificates->first()?->course ?? '—' }}</div>
    <div class="certmeta">
     <div><span>COMPLETED</span><b id="certDate">{{ $certificates->first()?->completed_on?->format('F j, Y') ?? '—' }}</b></div>
     <div><span>INSTRUCTOR</span><b id="certInstructor">{{ $certificates->first()?->instructor?->name ?? '—' }}</b></div>
     <div><span>CERTIFICATE NO.</span><b id="certNo">{{ $certificates->first()?->certificate_number ?? '—' }}</b></div>
    </div>
   </div>
  </div>
  <div class="panel">
   <h2>Certificate Record</h2>
   <div class="cert-row"><span>Student</span><b id="recName">{{ $certificates->first()?->student_name ?? '—' }}</b></div>
   <div class="cert-row"><span>Certificate #</span><b id="recNo">{{ $certificates->first()?->certificate_number ?? '—' }}</b></div>
   <div class="cert-row"><span>Enrollment</span><b>{{ $certificates->first()?->enrollment?->reference ?? '—' }}</b></div>
   <div class="cert-row"><span>Program</span><b id="recCourse">{{ $certificates->first()?->course ?? '—' }}</b></div>
   <div class="cert-row"><span>Completion</span><b id="recDate">{{ $certificates->first()?->completed_on?->format('F j, Y') ?? '—' }}</b></div>
   <div class="cert-row"><span>Issued</span><b>{{ $certificates->first()?->issued_at?->format('F j, Y · g:i A') ?? '—' }}</b></div>
   <div class="cert-row"><span>Status</span><b style="color:var(--good)">VALID / ISSUED</b></div>
   <div class="cert-row"><span>Public Registry</span><b style="color:var(--good)">PUBLISHED</b></div>
   <div class="verifybox"><b>✓ COMPLETION VERIFIED</b><br>This certificate is active in the PACIFIC TRADE TECH™ completion registry.</div>
   <div class="cert-actions">
    <button class="btn" type="button" onclick="window.print()">Print</button>
    <button class="btn" type="button" id="certReissueBtn">Reissue</button>
    <button class="btn danger" type="button" id="certRevokeBtn">Revoke</button>
   </div>
   <p class="note"><b>Backend rule:</b> certificate numbers are generated only after an enrollment is marked Completed. Numbers are unique and never reused. Reissues retain the original certificate number and create an audit record.</p>
  </div>
 </div>
</section>


<section class="page" id="instructors">
 <div class="inst-toolbar">
  <div><h2 style="margin:0">Instructor Management</h2><div class="note">Add, edit or remove instructors. Availability directly controls public calendar seat capacity.</div></div>
  <div class="inst-actions"><button class="btn" onclick="document.getElementById('inviteInstructor').scrollIntoView({behavior:'smooth'})">Send Onboarding</button><button class="btn gold" onclick="newInstructor()">+ Add Instructor</button></div>
 </div>
 <div class="logic"><strong>CAPACITY RULE:</strong> Each available instructor adds <b>8 seats per session</b>. If only one instructor is available for a session, the public calendar shows 8 total seats. Two available instructors show 16, three show 24, and so on. Unavailable instructors add zero capacity.</div>
 <div class="panel">
  <table><thead><tr><th>Instructor</th><th>Status</th><th>Daily Rate</th><th>Mon–Sat Availability</th><th>Capacity</th><th>Actions</th></tr></thead>
  <tbody id="instructorRows"></tbody></table>
 </div>

 <div class="inst-grid" style="margin-top:14px">
  <div class="panel inst-form">
   <h2 id="instFormTitle">Add / Edit Instructor</h2>
   <input type="hidden" id="instId">
   <label>Instructor Name</label><input id="instName" placeholder="Full name">
   <label>Email</label><input id="instEmail" type="email" placeholder="instructor@pacifictt.com">
   <label>Phone</label><input id="instPhone" placeholder="(000) 000-0000">
   <label>Daily Rate</label><input id="instRate" type="number" value="1200">
   <label>Status</label><select id="instStatus"><option>Active</option><option>Inactive</option></select>
   <label>Courses Authorized</label><select id="instCourse"><option>Core + Advanced</option><option>Core Only</option><option>Advanced Only</option></select>
   <h2 style="margin-top:20px">Weekly Availability</h2>
   <div class="note">Set the instructor's available time window for each operating day.</div>
   <div id="availabilityEditor"></div>
   <div class="inst-actions" style="margin-top:15px"><button class="btn gold" onclick="saveInstructor()">Save Instructor</button><button class="btn light" onclick="newInstructor()">Clear</button></div>
  </div>
  <div class="panel">
   <h2>Capacity Impact</h2>
   <div class="bigmetric" id="activeInstructorCount">2</div>
   <div class="note">Active instructors currently configured.</div>
   <div style="margin-top:18px"><div class="k">Maximum Seats / Session</div><div class="v" id="instSeatCapacity">16</div></div>
   <div style="margin-top:18px"><div class="k">Maximum Seats / Day</div><div class="v" id="instDayCapacity">48</div></div>
   <p class="note">Actual calendar capacity is calculated per date and session from the instructors whose availability overlaps that session.</p>
  </div>
 </div>

 
 <div class="panel" id="inviteInstructor" style="margin-top:14px">
  <div style="display:flex;justify-content:space-between;gap:12px;align-items:center">
   <div><h2 style="margin:0">Instructor Onboarding Invitation</h2><div class="note">Instructor completes onboarding through a private, single-use email link protected by a one-time 6-digit verification code.</div></div>
  </div>
  <div class="invitebox">
   <div class="invitegrid">
    <div><label>Instructor Name *</label><input id="inviteName" placeholder="Instructor full name"></div>
    <div><label>Instructor Email *</label><input id="inviteEmail" type="email" placeholder="instructor@email.com"></div>
   </div>
   <div class="invitegrid">
    <div><label>Daily Rate</label><input id="inviteRate" value="$1,200 / day" inputmode="decimal"></div>
    <div><label>Agreement Version</label><input value="PTT-ICA-2026.1" readonly></div>
   </div>
   <div class="lead-actions">
    <button class="btn gold" onclick="sendInvite()">SEND SECURE ONBOARDING EMAIL</button>
    <button class="btn light" onclick="resetInvite()">Reset</button>
   </div>
   <div class="invite-status">
    <div><div class="k">Invitation</div><b id="inviteState">NOT SENT</b></div>
    <div><div class="k">6-Digit Code</div><b id="codeState">NOT GENERATED</b></div>
    <div><div class="k">Link</div><b id="linkState">INACTIVE</b></div>
    <div><div class="k">Onboarding</div><b id="completeState">PENDING</b></div>
   </div>
   <p class="note"><b>Production behavior:</b> the code is generated server-side, stored as a secure hash, sent only to the instructor's email, and accepted once. After successful submission, the invitation token is consumed and the onboarding URL permanently returns an expired/completed page.</p>
  </div>
 </div>

<div class="onboard-grid" id="contractOnboarding" style="display:none">
  <div class="panel">
   <h2>Independent Contractor Instructor Agreement</h2>
   <div class="note">Required onboarding document. This mockup should be reviewed by California employment counsel before production use.</div>
   <div class="docscroll">
    <h2>PACIFIC TRADE TECH™<br>INDEPENDENT CONTRACTOR INSTRUCTOR AGREEMENT</h2>
    <p>This Independent Contractor Instructor Agreement (“Agreement”) is entered into between PACIFIC TRADE TECH™ and the undersigned Instructor (“Contractor”) for authorized vocational training services.</p>
    <h3>1. SERVICES</h3><p>Contractor may provide instruction in authorized PACIFIC TRADE TECH™ Core and/or Advanced industrial electrical, PLC, automation and HMI courses. Contractor shall provide instruction professionally, follow the approved curriculum and comply with applicable safety requirements.</p>
    <h3>2. SESSIONS AND CAPACITY</h3><p>A scheduled instructional day may include up to three four-hour sessions. Maximum classroom capacity is eight students per assigned instructor per session unless PACIFIC TRADE TECH™ establishes a lower limit for safety or operational reasons.</p>
    <h3>3. COMPENSATION</h3><p>Contractor compensation is $1,200 per scheduled instructional day unless a different rate is approved in writing. No minimum number of training days is guaranteed. Payment is subject to completion of required onboarding and tax documentation.</p>
    <h3>4. W-9 / TAX REPORTING</h3><p>Contractor shall provide a completed IRS Form W-9 before first payment. Contractor is responsible for taxes and reporting obligations applicable to compensation received, subject to applicable law.</p>
    <h3>5. CLASSIFICATION</h3><p>The parties intend the relationship described by this Agreement to be an independent contractor relationship only to the extent permitted by applicable law. Nothing in this Agreement overrides worker-classification requirements imposed by California or federal law.</p>
    <h3>6. AVAILABILITY</h3><p>Contractor shall provide accurate availability. PACIFIC TRADE TECH™ may offer training assignments based on student enrollment, course qualifications, facility availability and operational needs. Acceptance of a training assignment creates an obligation to appear and perform the scheduled instruction unless excused.</p>
    <h3>7. SAFETY</h3><p>Contractor shall enforce PACIFIC TRADE TECH™ safety procedures, required PPE, equipment restrictions and applicable lockout/tagout practices. Contractor shall immediately stop unsafe activities and report injuries, near misses, equipment damage and hazardous conditions.</p>
    <h3>8. STUDENT RECORDS AND CONFIDENTIALITY</h3><p>Student identities, contact information, payment information, waivers, attendance records and training records are confidential and may be used only for authorized PACIFIC TRADE TECH™ purposes.</p>
    <h3>9. CURRICULUM AND INTELLECTUAL PROPERTY</h3><p>PACIFIC TRADE TECH™ course materials, curriculum, diagrams, exercises, presentations, branding and proprietary training resources remain the property of their respective owner(s). Contractor receives only the limited permission necessary to perform authorized training and may not commercially reproduce, sell or distribute proprietary materials without written authorization.</p>
    <h3>10. STUDENT RELATIONSHIPS</h3><p>Contractor shall not misrepresent PACIFIC TRADE TECH™ programs or divert enrolled students, payments or PACIFIC TRADE TECH™ business opportunities for personal benefit. Any restrictive covenant shall apply only to the extent enforceable under California law.</p>
    <h3>11. CERTIFICATES</h3><p>Contractor may verify attendance and successful course completion when authorized. Contractor may not independently create, issue, alter or represent an unofficial document as an official PACIFIC TRADE TECH™ Certificate of Completion. Official certificates are issued through PACIFIC TRADE TECH™ administration.</p>
    <h3>12. EQUIPMENT AND PROPERTY</h3><p>Contractor shall use training equipment only for authorized purposes and promptly report damage or malfunction. Responsibility for losses or damage shall be determined under applicable law and the circumstances involved.</p>
    <h3>13. INSURANCE / QUALIFICATIONS</h3><p>PACIFIC TRADE TECH™ may require evidence of relevant qualifications, experience, licenses or insurance when appropriate to an assignment or required by law.</p>
    <h3>14. CANCELLATION / FAILURE TO APPEAR</h3><p>Contractor shall promptly notify administration when unable to perform an accepted assignment. Repeated late cancellations or failures to appear may result in removal from future scheduling or termination of this Agreement.</p>
    <h3>15. TERMINATION</h3><p>Either party may terminate this Agreement subject to any written notice requirement established by PACIFIC TRADE TECH™ and applicable law. Confidentiality, intellectual-property and record-protection obligations that by their nature survive termination shall continue as legally enforceable.</p>
    <h3>16. NO AUTHORITY TO BIND</h3><p>Contractor may not enter contracts, incur obligations, promise refunds, alter tuition, issue credentials or otherwise bind PACIFIC TRADE TECH™ unless specifically authorized in writing.</p>
    <h3>17. GOVERNING LAW</h3><p>This Agreement is governed by applicable California law. If any provision is unenforceable, the remaining lawful provisions shall remain effective to the extent permitted.</p>
    <h3>18. ENTIRE AGREEMENT / ELECTRONIC SIGNATURES</h3><p>This Agreement and incorporated written policies constitute the parties’ agreement concerning the covered services. Electronic signatures and electronic records may be used to the extent permitted by law.</p>
   </div>
  </div>

  <div class="panel onboard-form">
   <h2>Instructor Onboarding</h2>
   <div class="logic"><strong>ACTIVATION RULE:</strong> Instructor remains <b>PENDING / NOT SCHEDULABLE</b> until the contractor agreement and PACIFIC TRADE TECH™ acceptance are complete.</div>
   <label>Instructor *</label><select id="onboardInstructor"><option>Instructor A</option><option>Instructor B</option><option>New Instructor</option></select>
   <label>Agreement Version</label><input value="PTT-ICA-2026.1" readonly>
   <div class="checkline"><input type="checkbox" id="contractAgree" style="width:auto"><span>I acknowledge that I have read and agree to the Independent Contractor Instructor Agreement.</span></div>
   <label>Instructor Signature *</label><div class="onboard-sign" id="contractSign" onclick="signContract()">Click to sign</div><input type="hidden" id="contractSigned">
   <label>Signed Date / Time</label><input id="contractStamp" readonly placeholder="Automatically stamped">

   <div class="docbox">
    <h2 style="margin-top:0">PACIFIC TRADE TECH™ ACCEPTANCE</h2>
    <label>Representative Name *</label><input id="contractRep" placeholder="Authorized staff full name">
    <label>Representative Signature *</label><div class="onboard-sign" id="contractRepSign" onclick="signContractRep()">Click to apply staff signature</div><input type="hidden" id="contractRepSigned">
    <label>Date *</label><input id="contractRepDate" type="date">
   </div>

   <button class="btn gold" style="width:100%" onclick="activateInstructor()">COMPLETE ONBOARDING / ACTIVATE</button>
   <div class="verifybox" id="onboardComplete" style="display:none"><b>✓ INSTRUCTOR ONBOARDING COMPLETE</b><br>Agreement executed and staff acceptance completed. Instructor may now be made schedulable.</div>
  </div>
 </div>

</section>

<section class="page" id="command">
  <div class="command-hero">
    <div><div class="eyebrow">REALTIME OPERATIONS</div><h2>Assistant Command Center</h2><p>Publish company metrics, assign Mission Focus work, and push staff updates from one controlled workspace.</p></div>
    <div class="live-state"><i></i><span>LIVE DATA CHANNEL</span><b id="cmdRevision">Revision 1</b></div>
  </div>

  <div class="command-kpis">
    <div class="cmd-kpi"><span>Cash Today</span><b id="cmdCash">$0</b><small>Collected funds</small></div>
    <div class="cmd-kpi"><span>Enrollments</span><b id="cmdEnroll">0</b><small>Monthly confirmed</small></div>
    <div class="cmd-kpi"><span>Utilization</span><b id="cmdUtil">0%</b><small>Seat occupancy</small></div>
    <div class="cmd-kpi"><span>Attendance</span><b id="cmdAttend">0%</b><small>Active sessions</small></div>
    <div class="cmd-kpi"><span>Mission Focus</span><b id="cmdFocus">0%</b><small id="cmdFocusNote">No decided missions</small></div>
  </div>

  <div class="command-grid">
    <div class="panel compact-panel">
      <div class="section-head"><div><h2>Business Metrics</h2><p class="note">Publish verified operating numbers to the live staff display.</p></div><span class="pill paid">REALTIME</span></div>
      <div class="metric-grid">
        <label>Cash collected today<input id="cmdCashInput" type="number" min="0" step=".01"></label>
        <label>Monthly enrollments<input id="cmdEnrollInput" type="number" min="0"></label>
        <label>Seat utilization %<input id="cmdUtilInput" type="number" min="0" max="100"></label>
        <label>Attendance %<input id="cmdAttendInput" type="number" min="0" max="100"></label>
        <label>Completion forecast %<input id="cmdCompletionInput" type="number" min="0" max="100"></label>
      </div>
      <button class="btn gold" id="cmdPublishMetrics">Publish Metrics</button>
    </div>

    <div class="panel compact-panel">
      <div class="section-head"><div><h2>Push Realtime Update</h2><p class="note">Send a short company alert or operating update.</p></div><span class="pill">LIVE FEED</span></div>
      <textarea id="cmdUpdateMessage" class="cmd-textarea" placeholder="What does the team need to know now?"></textarea>
      <div class="cmd-row"><select id="cmdUpdateType"><option value="priority">Priority</option><option value="alert">Alert</option><option value="success">Success</option><option value="general">General</option></select><select id="cmdUpdateDepartment"><option>Company-wide</option><option>Enrollment</option><option>Training</option><option>Operations</option><option>Finance</option><option>Leadership</option></select></div>
      <button class="btn gold" id="cmdPushUpdate">Push Update</button>
    </div>
  </div>

  <div class="command-grid">
    <div class="panel compact-panel">
      <div class="section-head"><div><h2>Mission Focus</h2><p class="note">Assign work with a deadline. Expired unfinished work becomes Noise.</p></div><span class="pill" id="cmdMissionCount">0 MISSIONS</span></div>
      <div class="mission-form">
        <input id="cmdTaskTitle" placeholder="Mission Focus task">
        <select id="cmdTaskOwner" aria-label="Assignment owner">
          <option value="">Select registered owner</option>
        </select>
        <select id="cmdTaskDepartment"><option>Operations</option><option>Enrollment</option><option>Training</option><option>Finance</option><option>Growth</option></select>
        <input id="cmdTaskDeadline" type="datetime-local">
        <button class="btn gold" id="cmdAssignTask">Assign</button>
      </div>
      <div class="owner-rule-note"><b>Owner:</b> only active registered Pacific Trade Tech users can receive new assignments.</div>
      <div class="focusbar"><div id="cmdFocusFill"></div></div>
      <div class="focuslabels"><b id="cmdFocusLabel">MISSION FOCUS 0%</b><span id="cmdNoiseLabel">NOISE 0%</span></div>
      <div id="cmdTaskList" class="cmd-list"></div>
    </div>

    <div class="panel compact-panel">
      <div class="section-head"><div><h2>Realtime Activity</h2><p class="note">Newest assistant-published company updates.</p></div><span class="pill paid" id="cmdFeedCount">0 UPDATES</span></div>
      <div id="cmdFeed" class="cmd-feed"></div>
    </div>
  </div>

  <div class="panel compact-panel command-tools">
    <div class="section-head"><div><h2>Operational Workspaces</h2><p class="note">Jump directly to the work area you need without crowding this command screen.</p></div></div>
    <div class="workspace-grid">
      <button data-jump="leads"><b>Leads CRM</b><span>Prospects & follow-up</span></button>
      <button data-jump="enrollments"><b>Enrollments</b><span>Student enrollment records</span></button>
      <button data-jump="schedule"><b>Schedule</b><span>Sessions & seat capacity</span></button>
      <button data-jump="waivers"><b>Waivers</b><span>Acceptance & review</span></button>
      <button data-jump="certificates"><b>Certificates</b><span>Issue & verify</span></button>
      <button data-jump="payments"><b>Payments</b><span>Transactions & Zelle</span></button>
      <button data-jump="instructors"><b>Instructors</b><span>Availability & onboarding</span></button>
      <button data-jump="reports"><b>Reports</b><span>Operating & financial reports</span></button>
    </div>
  </div>
</section>

<section class="page" id="reports"><div class="cards"><div class="card"><div class="k">Gross Tuition MTD</div><div class="v">${{ number_format($reports['gross_mtd'], 0) }}</div></div><div class="card"><div class="k">Avg Enrollment</div><div class="v">${{ number_format($reports['avg_enrollment'], 0) }}</div></div><div class="card"><div class="k">Occupancy</div><div class="v">{{ $reports['occupancy'] }}%</div></div><div class="card"><div class="k">No-Show</div><div class="v">{{ $reports['no_show'] }}</div></div></div><div class="panel" style="margin-top:14px"><h2>Financial Reporting</h2><p class="note">Today / Week / Month / Quarter / Year / Custom. Track tuition, refunds, processing, instructor payroll, advertising, materials, operating expense, IP royalty and operating profit.</p></div></section>
</main>
</div>



<div class="reschedule-modal" id="rescheduleModal" aria-hidden="true">
  <div class="reschedule-dialog" role="dialog" aria-modal="true" aria-labelledby="rescheduleTitle">
    <div class="reschedule-head">
      <div class="eyebrow">MISSION FOCUS</div>
      <h2 id="rescheduleTitle">Reschedule Task</h2>
      <p>Move this mission to a new completion deadline without losing the task or its assignment.</p>
      <button class="reschedule-close" type="button" aria-label="Close" onclick="closeRescheduleModal()">×</button>
    </div>
    <div class="reschedule-body">
      <div class="reschedule-task">
        <small>Task</small>
        <b id="rescheduleTaskName">Mission Focus Task</b>
      </div>
      <div class="reschedule-meta">
        <div class="reschedule-field">
          <label for="rescheduleCurrent">Current Deadline</label>
          <input id="rescheduleCurrent" type="datetime-local" readonly>
        </div>
        <div class="reschedule-field">
          <label for="rescheduleNew">New Deadline</label>
          <input id="rescheduleNew" type="datetime-local">
        </div>
      </div>
      <div class="reschedule-note">Saving a new deadline returns the task to <b>Open</b> Mission Focus status.</div>
      <div class="reschedule-actions">
        <button class="btn light" type="button" onclick="closeRescheduleModal()">Cancel</button>
        <button class="btn gold" type="button" onclick="saveReschedule()">Save Reschedule</button>
      </div>
    </div>
  </div>
</div>

<script>
  /* Server data the console's own script reads instead of its mock arrays. */
  window.PTT = @json($bootstrap);
</script>
<script src="{{ \App\Support\Asset::url('js/admin-design.js') }}"></script>
<script src="{{ \App\Support\Asset::url('js/admin-crud.js') }}"></script>
<script src="{{ \App\Support\Asset::url('js/admin-live.js') }}"></script>
</body></html>