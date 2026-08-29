@php($closed = $closed ?? false)<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>PACIFIC TRADE TECH&trade; — Secure Instructor Onboarding</title>
<link rel="stylesheet" href="{{ \App\Support\Asset::url('css/onboarding.css') }}">
</head>
<body>
<header><div class="wrap"><div class="brand">PACIFIC <span>TRADE TECH™</span></div><div class="secure">SECURE INSTRUCTOR ONBOARDING • SINGLE-USE INVITATION</div></div></header>

<main>
<div class="card" id="shell">
 <div id="normal" style="display:@if($closed)none @else block @endif">
  <h1>Instructor Onboarding</h1>
  <p class="note">This private page is for the invited instructor only. Complete all required steps in one session. After successful submission, this invitation becomes inactive and cannot be reused.</p>
  <div class="progress"><div id="bar"></div></div>
  <div class="steps"><span>Verify</span><span>Profile</span><span>Agreement</span><span>Submit</span></div>

  <section class="step active" id="s1">
   <div style="text-align:center;padding:25px 0">
    <div style="font-size:38px">✉</div><h2>Verify Your Email</h2>
    <p class="note">Enter the one-time 6-digit code included in your PACIFIC TRADE TECH™ onboarding email.</p>
    <input id="otp" class="otp" maxlength="6" inputmode="numeric" placeholder="000000">
    <button class="btn gold" onclick="pttVerify(this)">VERIFY & CONTINUE</button>
    <p class="note">Sent to <b>{{ $invite?->email }}</b>. It expires in 15 minutes.<br>Didn't get it? <a href="#" id="resendCode" style="color:inherit;text-decoration:underline;font-weight:700">Send a new code</a></p>
   </div>
  </section>

  <section class="step" id="s2">
   <h2>Instructor Information</h2>
   <div class="two"><div class="field"><label>Legal Name *</label><input id="name" placeholder="Full legal name" value="{{ $invite?->name }}"></div><div class="field"><label>Phone *</label><input id="phone" placeholder="(000) 000-0000"></div></div>
   <div class="field"><label>Email *</label><input id="email" type="email" placeholder="Email address" value="{{ $invite?->email }}" readonly></div>
   <div class="two"><div class="field"><label>Address *</label><input id="address" placeholder="Street address"></div><div class="field"><label>City / State / ZIP *</label><input id="city" placeholder="City, CA 00000"></div></div>
   <div class="two"><div class="field"><label>Authorized Courses</label><select><option>Core + Advanced</option><option>Core Only</option><option>Advanced Only</option></select></div><div class="field"><label>Daily Rate</label><input value="$1,200 / scheduled instructional day" readonly></div></div>
   <button class="btn gold" onclick="pttProfileNext(this)">CONTINUE</button>
  </section>

  <section class="step" id="s3">
   <h2>Independent Contractor Instructor Agreement</h2>
   <div class="agreement">
    <h2>PACIFIC TRADE TECH™<br>INDEPENDENT CONTRACTOR INSTRUCTOR AGREEMENT</h2>
    <p>This Agreement is entered into between PACIFIC TRADE TECH™ and the undersigned Instructor (“Contractor”) for authorized vocational training services.</p>
    <h3>1. SERVICES</h3><p>Contractor may provide authorized Core and/or Advanced industrial electrical, PLC, automation and HMI instruction and shall follow approved curriculum and safety requirements.</p>
    <h3>2. SESSIONS AND CAPACITY</h3><p>A scheduled instructional day may include up to three four-hour sessions. Maximum classroom capacity is eight students per assigned instructor per session unless a lower limit is established for safety or operational reasons.</p>
    <h3>3. COMPENSATION</h3><p>Compensation is $1,200 per scheduled instructional day unless otherwise approved in writing. No minimum number of training days is guaranteed.</p>
    <h3>4. W-9 / TAX REPORTING</h3><p>Contractor shall provide a completed IRS Form W-9 before first payment and is responsible for applicable tax obligations, subject to law.</p>
    <h3>5. CLASSIFICATION</h3><p>The parties intend an independent contractor relationship only to the extent permitted by applicable law. This Agreement does not override California or federal worker-classification requirements.</p>
    <h3>6. AVAILABILITY</h3><p>Contractor shall provide accurate availability. Training assignments depend on enrollment, qualifications, facility availability and operational needs.</p>
    <h3>7. SAFETY</h3><p>Contractor shall enforce required safety procedures and PPE, stop unsafe activities, and promptly report injuries, near misses, equipment damage and hazardous conditions.</p>
    <h3>8. CONFIDENTIALITY</h3><p>Student identities, contact information, payment information, waivers, attendance and training records are confidential.</p>
    <h3>9. CURRICULUM / INTELLECTUAL PROPERTY</h3><p>PACIFIC TRADE TECH™ curriculum, diagrams, exercises, presentations, branding and proprietary training resources remain the property of their respective owner(s) and may not be commercially reproduced or distributed without authorization.</p>
    <h3>10. STUDENT RELATIONSHIPS</h3><p>Contractor shall not misrepresent programs or divert enrolled students, payments or business opportunities for personal benefit. Restrictions apply only to the extent enforceable under California law.</p>
    <h3>11. CERTIFICATES</h3><p>Contractor may verify attendance and successful completion when authorized but may not independently issue or alter an official PACIFIC TRADE TECH™ Certificate of Completion.</p>
    <h3>12. EQUIPMENT</h3><p>Training equipment shall be used only for authorized purposes. Damage, malfunction and unsafe conditions must be reported promptly.</p>
    <h3>13. QUALIFICATIONS / INSURANCE</h3><p>PACIFIC TRADE TECH™ may require evidence of relevant experience, qualifications, licenses or insurance when appropriate or legally required.</p>
    <h3>14. CANCELLATION</h3><p>Contractor shall promptly notify administration when unable to perform an accepted assignment. Repeated late cancellations or failures to appear may result in removal from scheduling.</p>
    <h3>15. TERMINATION</h3><p>Either party may terminate this Agreement subject to applicable law and any agreed written notice requirements.</p>
    <h3>16. NO AUTHORITY TO BIND</h3><p>Contractor may not bind PACIFIC TRADE TECH™, alter tuition, promise refunds, incur obligations or issue credentials without written authorization.</p>
    <h3>17. GOVERNING LAW</h3><p>This Agreement is governed by applicable California law.</p>
    <h3>18. ELECTRONIC SIGNATURES</h3><p>Electronic signatures and electronic records may be used to the extent permitted by law.</p>
   </div>
   <div class="check"><input id="agree" type="checkbox"><span>I have read the complete agreement above and agree to its terms.</span></div>
   <div class="field"><label>Electronic Signature *</label><div class="sig" id="sig" onclick="sign()">Click to sign using your legal name</div></div>
   <div class="field"><label>Signature Date / Time</label><input id="stamp" readonly></div>
   <button class="btn gold" onclick="pttAgreementNext(this)">ACCEPT & CONTINUE</button>
  </section>

  

  <section class="step" id="s4">
   <h2>Review & Submit</h2>
   <div class="status"><b>✓ Email Verified</b><br>✓ Instructor information completed<br>✓ Contractor agreement signed<br>✓ W-9 completed and electronically signed</div>
   <div class="card" style="box-shadow:none;margin-top:14px"><div class="note">Instructor</div><b id="reviewName">—</b><br><br><div class="note">Agreement</div><b>PTT-ICA-2026.1</b><br><br><div class="note">Compensation</div><b>$1,200 / scheduled instructional day</b></div>
   <div class="check"><input id="finalAck" type="checkbox"><span>I confirm the information submitted is accurate and understand that submission permanently closes this single-use onboarding invitation.</span></div>
   <button class="btn gold" onclick="pttSubmit(this)">SIGN & SUBMIT ONBOARDING</button>
  </section>
 </div>

 <div class="closed" id="closed" style="display:@if($closed)block @else none @endif">
  <div class="icon">✓</div><h1>Onboarding Submitted</h1>
  <p>Your instructor onboarding has been securely submitted to PACIFIC TRADE TECH™.</p>
  <div class="status">This single-use invitation has been consumed. The link and 6-digit verification code are no longer valid.</div>
  <p class="note" style="margin-top:18px">PACIFIC TRADE TECH™ staff must review and complete internal acceptance before instructor activation or scheduling.</p>
 </div>
</div>
</main>
<footer>PACIFIC TRADE TECH™ · PacificTT.com · © 2026</footer>

@if(! $closed)
<script>
  window.PTT_ONBOARD = {
    verify: @js(route('instructor.onboarding.verify', ['token' => $invite->token])),
    submit: @js(route('instructor.onboarding.submit', ['token' => $invite->token])),
    resend: @js(route('instructor.onboarding.resend', ['token' => $invite->token]))
  };
</script>
<script src="{{ \App\Support\Asset::url('js/onboarding.js') }}"></script>
@endif
</body>
</html>
