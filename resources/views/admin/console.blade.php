<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>PACIFIC TRADE TECH&trade; — Admin</title>
<link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body>
<div class="app">
<aside>
  <div class="brand">PACIFIC <span>TRADE TECH™</span></div>
  <div class="sub">ADMINISTRATION CONSOLE</div>
  <nav id="nav">
    <button class="active" data-page="dashboard">Dashboard</button>
    <button data-page="enrollments">Enrollments</button>
    <button data-page="schedule">Schedule</button>
    <button data-page="students">Students</button>
    <button data-page="leads">Leads</button>
    <button data-page="payments">Payments</button>
    <button data-page="waivers">Waivers</button>
    <button data-page="certificates">Certificates</button>
    
    <button data-page="instructors">Instructors</button>
    <button data-page="reports">Reports</button>
    <button data-page="settings">Settings</button>
  </nav>
  <div class="foot">PacificTT.com<br>© 2026 PACIFIC TRADE TECH™</div>
</aside>

<main>
<div class="top">
 <div><h1 id="pageTitle">Dashboard</h1><p>Operations, enrollment, payments and training management.</p></div>
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
  <div class="panel today"><h2>Today — Training Operations</h2><div class="todaygrid">
   <div><div class="k">Available Capacity</div><div class="v" id="todayCapacity">{{ $todayOps['capacity'] }}</div></div>
   <div><div class="k">Seats Remaining</div><div class="v" id="todayRemaining">{{ $todayOps['remaining'] }}</div></div>
   <div><div class="k">Core</div><div class="v">{{ $todayOps['core'] }}</div></div>
   <div><div class="k">Advanced</div><div class="v">{{ $todayOps['advanced'] }}</div></div>
  </div></div>
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
 <div class="panel"><div class="toolbar"><label>Date <input type="date" id="schedDate" value="2026-08-12"></label><label>Available instructors <select id="instructorCount"><option value="1">1 instructor</option><option value="2" selected>2 instructors</option><option value="3">3 instructors</option><option value="4">4 instructors</option></select></label><button class="btn gold" onclick="renderSchedule()">Apply Capacity</button></div>
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
  <button class="btn gold" onclick="newInstructor()">+ Add Instructor</button>
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
</section>
<section class="page" id="reports"><div class="cards"><div class="card"><div class="k">Gross Tuition MTD</div><div class="v">${{ number_format($reports['gross_mtd'], 0) }}</div></div><div class="card"><div class="k">Avg Enrollment</div><div class="v">${{ number_format($reports['avg_enrollment'], 0) }}</div></div><div class="card"><div class="k">Occupancy</div><div class="v">{{ $reports['occupancy'] }}%</div></div><div class="card"><div class="k">No-Show</div><div class="v">{{ $reports['no_show'] }}</div></div></div><div class="panel" style="margin-top:14px"><h2>Financial Reporting</h2><p class="note">Today / Week / Month / Quarter / Year / Custom. Track tuition, refunds, processing, instructor payroll, advertising, materials, operating expense, IP royalty and operating profit.</p></div></section>
<section class="page" id="settings"><div class="panel"><h2>Operating Settings</h2>
@foreach($programs as $p)
<div class="settingrow"><div><b>{{ $p->name }}</b><div class="note">{{ $p->short_name }}</div></div><input id="price_{{ $p->id }}" value="${{ number_format($p->price_cents / 100, 0) }}"><button class="btn light" type="button" onclick="saveSetting(this, 'program_price_{{ $p->id }}', 'price_{{ $p->id }}')">Save</button></div>
@endforeach

<div class="settingrow"><div><b>Seats per instructor / session</b><div class="note">Used by public calendar capacity engine</div></div><input id="seatSetting" type="number" min="1" value="{{ $settings['seats_per_instructor'] }}"><button class="btn light" type="button" onclick="saveSetting(this, 'seats_per_instructor', 'seatSetting'); renderSchedule()">Apply</button></div>
<div class="settingrow"><div><b>Sessions per day</b></div><input id="setSessions" value="{{ \App\Models\Setting::get('sessions_per_day', count($settings['session_slots'])) }}"><button class="btn light" type="button" onclick="saveSetting(this, 'sessions_per_day', 'setSessions')">Save</button></div>
<div class="settingrow"><div><b>Deposit</b></div><input id="depositPct" value="{{ $settings['deposit_percent'] }}"><button class="btn light" type="button" onclick="saveSetting(this, 'deposit_percent', 'depositPct')">Save</button></div>
<div class="settingrow"><div><b>Operating days</b></div><input id="setDays" value="{{ \App\Models\Setting::get('operating_days', 'Monday–Saturday; Sunday Closed') }}"><button class="btn light" type="button" onclick="saveSetting(this, 'operating_days', 'setDays')">Save</button></div>
<div class="settingrow"><div><b>Contact</b></div><input id="contactPhone" value="{{ \App\Models\Setting::get('contact_phone', '1 (800) 997-4607') }}"><button class="btn light" type="button" onclick="saveSetting(this, 'contact_phone', 'contactPhone')">Save</button></div>
<div style="margin-top:24px;padding-top:18px;border-top:2px solid var(--line)">
  <h2 style="margin:0 0 5px">Payment Merchant</h2>
  <div class="note">Configure the payment processor used by student checkout. Credentials shown here are mockup fields only.</div>
</div>

<div class="settingrow">
  <div><b>Primary Payment Merchant</b><div class="note">Processor used for online checkout</div></div>
  <select id="merchantSelect" onchange="showMerchantFields()">
    <option value="stripe">Stripe</option>
    <option value="paypal">PayPal</option>
    <option value="zelle">Zelle — In-House Verification</option>
    <option value="other">Other</option>
  </select>
  <button class="btn light" type="button" onclick="saveSetting(this, 'payment_merchant', 'merchantSelect')">Save</button>
</div>

<div id="stripeFields">
  <div class="settingrow"><div><b>Stripe Publishable Key</b></div><input type="password" value="" disabled placeholder="{{ config('services.stripe.key') ? 'configured on the server' : 'not set' }}"><span class="note">Set in the server environment</span></div>
<input type="password" value="" disabled placeholder="{{ config('services.stripe.key') ? 'configured on the server' : 'not set' }}"><span class="note">Set in the server environment</span></div>
  <div class="settingrow"><div><b>Stripe Secret Key</b><div class="note">Server-side credential</div></div><input type="password" value="" disabled placeholder="{{ config('services.stripe.secret') ? 'configured on the server' : 'not set' }}"><span class="note">Set in the server environment</span></div>
</div><input type="password" value="" disabled placeholder="{{ config('services.stripe.secret') ? 'configured on the server' : 'not set' }}"><span class="note">Set in the server environment</span></div>
  <div class="settingrow"><div><b>Webhook Secret</b></div><input type="password" value="" disabled placeholder="{{ config('services.stripe.webhook_secret') ? 'configured on the server' : 'not set' }}"><span class="note">Set in the server environment</span></div>
<input type="password" value="" disabled placeholder="{{ config('services.stripe.webhook_secret') ? 'configured on the server' : 'not set' }}"><span class="note">Set in the server environment</span></div>
</div>

<div id="paypalFields" style="display:none">
  <div class="settingrow"><div><b>PayPal Client ID</b></div><input type="password" placeholder="Set in the server environment" disabled><span class="note">Server environment</span></div>
  <div class="settingrow"><div><b>PayPal Client Secret</b><div class="note">Server-side credential</div></div><input type="password" placeholder="Set in the server environment" disabled><span class="note">Server environment</span></div>
</div>


<div id="zelleFields" style="display:none">
  <div class="settingrow">
    <div><b>Zelle Payment</b><div class="note">Primarily for walk-in payments. Payments remain pending until verified by authorized staff.</div></div>
    <select id="zelleEnabled"><option>Enabled</option><option>Disabled</option></select>
    <button class="btn light" type="button" onclick="saveSetting(this, 'zelle_enabled', 'zelleEnabled')">Save</button>
  </div>
  <div class="settingrow">
    <div><b>Zelle Recipient</b><div class="note">Business email or phone displayed to staff/customer</div></div>
    <input id="zelleRecipient" placeholder="Enter Zelle business email or phone">
    <button class="btn light" type="button" onclick="saveSetting(this, 'zelle_recipient', 'zelleRecipient')">Save</button>
  </div>
  <div class="settingrow">
    <div><b>Verification Requirement</b><div class="note">Enrollment is not marked paid until staff confirms receipt.</div></div>
    <select id="zelleVerify"><option>Required — In-House Verification</option></select>
    <button class="btn light" type="button" onclick="saveSetting(this, 'zelle_verification', 'zelleVerify')">Save</button>
  </div>
  <div class="settingrow">
    <div><b>Default Use</b></div>
    <select id="zelleUse"><option>Walk-In / In-House</option><option>Allow Online Selection</option></select>
    <button class="btn light" type="button" onclick="saveSetting(this, 'zelle_default_use', 'zelleUse')">Save</button>
  </div>
</div>

<div id="otherFields" style="display:none">
  <div class="settingrow"><div><b>Merchant Name</b></div><input id="otherMerchant" placeholder="Merchant / gateway name" value="{{ \App\Models\Setting::get('other_merchant_name', '') }}"><button class="btn light" type="button" onclick="saveSetting(this, 'other_merchant_name', 'otherMerchant')">Save</button></div>
  <div class="settingrow"><div><b>API / Merchant ID</b></div><input type="password" placeholder="Set in the server environment" disabled><span class="note">Server environment</span></div>
  <div class="settingrow"><div><b>Secret / Token</b></div><input type="password" placeholder="Set in the server environment" disabled><span class="note">Server environment</span></div>
</div>

<div class="settingrow">
  <div><b>Merchant Status</b><div class="note">Controls whether online payment is available at checkout</div></div>
  <select id="merchantStatus"><option>Live / Enabled</option><option>Test Mode</option><option>Disabled</option></select>
  <button class="btn light" type="button" onclick="saveSetting(this, 'merchant_status', 'merchantStatus')">Save</button>
</div>

<div class="settingrow">
  <div><b>Accepted Checkout Options</b><div class="note">Full payment or 30% deposit / balance onsite</div></div>
  <select id="checkoutOptions"><option>Full Payment + 30% Deposit</option><option>Full Payment Only</option><option>30% Deposit Only</option></select>
  <button class="btn light" type="button" onclick="saveSetting(this, 'checkout_options', 'checkoutOptions')">Save</button>
</div>

</div></section>
</main>
</div>

<script>
  /* Server data the console's own script reads instead of its mock arrays. */
  window.PTT = @json($bootstrap);
</script>
<script src="{{ asset('js/admin-design.js') }}"></script>
<script src="{{ asset('js/admin-crud.js') }}"></script>
</body>
</html>
