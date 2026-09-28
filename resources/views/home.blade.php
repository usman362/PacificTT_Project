@extends('layouts.app')

@section('body')
<div class="wrap">
<header>
 <div class="brand"><span>⚡</span> CONTROLS ACADEMY</div>
 <div class="progress"><div class="meta"><span>ENROLLMENT</span><b id="pct">0%</b></div><div class="bar"><div class="fill" id="fill"></div></div></div>
</header>

<section class="hero">
 <div><div class="eyebrow">Industrial electrical + PLC training</div><h1>BUILD.<br>WIRE.<br>PROGRAM.</h1><p>Tell us about your experience and training goals. The form advances one step at a time and tracks your enrollment progress.</p><div style="margin:16px 0 18px;font-weight:800;letter-spacing:.04em">Questions? <a href="tel:18000000000" style="color:inherit;text-decoration:none;border-bottom:2px solid currentColor"><a href="tel:+18009974607">1(800)997-4607</a></div><a href="#enrollmentForm" class="start">START ENROLLMENT →</a></div>
 
<div class="hero-slider" id="heroSlider">
 <div class="slider-track" id="sliderTrack">
  <div class="slide"><img src="{{ asset('img/plc_slide_1.jpg') }}" alt="PLC fundamentals training"><div class="slide-caption"><b>01 · PLC Fundamentals</b><span>Hardware, I/O and controller fundamentals</span></div></div>
  <div class="slide"><img src="{{ asset('img/plc_slide_2.jpg') }}" alt="Industrial electrical controls"><div class="slide-caption"><b>02 · Electrical Controls</b><span>Industrial wiring, relays and contactors</span></div></div>
  <div class="slide"><img src="{{ asset('img/plc_slide_3.jpg') }}" alt="HMI design and programming"><div class="slide-caption"><b>03 · HMI Design & Programming</b><span>Operator interfaces and process visualization</span></div></div>
  <div class="slide"><img src="{{ asset('img/plc_slide_4.jpg') }}" alt="Industrial automation systems"><div class="slide-caption"><b>04 · Automation & Systems</b><span>Integrated industrial automation applications</span></div></div>
  <div class="slide"><img src="{{ asset('img/plc_slide_5.jpg') }}" alt="PLC programming project"><div class="slide-caption"><b>05 · Real-World Projects</b><span>Apply programming skills to industrial projects</span></div></div>
 </div>
 <button class="slider-btn slider-prev" id="sliderPrev" type="button" aria-label="Previous slide">‹</button>
 <button class="slider-btn slider-next" id="sliderNext" type="button" aria-label="Next slide">›</button>
 <div class="slider-dots" id="sliderDots"></div>
</div>

</section>


<section class="compare" id="compare">
 <div class="compare-head">
  <div class="eyebrow">Choose your training path</div>
  <h2>Compare Programs</h2>
  <p>See exactly what is included before starting enrollment.</p>
 </div>
 <div class="compare-grid">
  <article class="program-card" data-program="{{ $programs->firstWhere('slug','plc-electrical-controls')?->id }}" data-title="PLC + Electrical Controls">
   <div class="pc-head"><div class="eyebrow">Core Program</div><h3>PLC + Electrical Controls</h3><div class="program-duration">4-HOUR COURSE</div><div class="price">{{ $programs->firstWhere('slug','plc-electrical-controls')?->price_label ?? '$1,495' }} <small>/ STUDENT</small></div></div>
   <div class="features">
    <div class="feature"><span class="yes">✓</span><span>Industrial electrical fundamentals</span></div>
    <div class="feature"><span class="yes">✓</span><span>Control wiring & schematics</span></div>
    <div class="feature"><span class="yes">✓</span><span>PLC hardware & I/O fundamentals</span></div>
    <div class="feature"><span class="yes">✓</span><span>Ladder logic programming</span></div>
    <div class="feature"><span class="yes">✓</span><span>Timers, counters & interlocks</span></div>
    <div class="feature"><span class="yes">✓</span><span>Hands-on troubleshooting exercises</span></div>
    <div class="feature"><span class="no">✕</span><span>Advanced analog / process control</span></div>
    <div class="feature"><span class="no">✕</span><span>Advanced automation sequencing</span></div>
    <div class="feature"><span class="no">✕</span><span>HMI design & programming</span></div>
   </div>
  </article>

  <article class="program-card featured" data-program="{{ $programs->firstWhere('slug','advanced-plc-automation')?->id }}" data-title="Advanced PLC / Automation">
   <div class="badge">ADVANCED</div>
   <div class="pc-head"><div class="eyebrow">Advanced Program</div><h3>Advanced PLC / Automation</h3><div class="program-duration">4-HOUR COURSE</div><div class="price">{{ $programs->firstWhere('slug','advanced-plc-automation')?->price_label ?? '$2,500' }} <small>/ STUDENT</small></div></div>
   <div class="features">
    <div class="feature"><span class="yes">✓</span><span>Industrial electrical fundamentals</span></div>
    <div class="feature"><span class="yes">✓</span><span>Control wiring & schematics</span></div>
    <div class="feature"><span class="yes">✓</span><span>PLC hardware & I/O fundamentals</span></div>
    <div class="feature"><span class="yes">✓</span><span>Ladder logic programming</span></div>
    <div class="feature"><span class="yes">✓</span><span>Timers, counters & interlocks</span></div>
    <div class="feature"><span class="yes">✓</span><span>Hands-on troubleshooting exercises</span></div>
    <div class="feature"><span class="yes">✓</span><span>Advanced analog / process control</span></div>
    <div class="feature"><span class="yes">✓</span><span>Advanced automation sequencing</span></div>
    <div class="feature"><span class="yes">✓</span><span>HMI design & programming</span></div>
   </div>
  </article>

  <article class="program-card coming-soon hvacr-coming-soon" data-title="HVAC/R Service & Systems Training">
   <div class="badge">COMING SOON</div>
   <div class="pc-head"><div class="eyebrow">HVAC/R + Industrial Systems</div><h3>HVAC/R Service &amp; Systems Training</h3><div class="program-duration">4-HOUR COURSE</div><div class="price">COMING SOON <small>/ HANDS-ON TRAINING</small></div></div>
   <div class="hvac-offer-slider" id="hvacOfferSlider">
    <button class="hvac-arrow hvac-prev" id="hvacPrev" type="button" aria-label="Previous HVAC/R course">‹</button>
    <div class="hvac-offer-viewport">
     <div class="hvac-offer-track" id="hvacOfferTrack">
      <div class="hvac-offer"><h4>Mini-Split Systems</h4><p>Hands-on training focused on ductless mini-split installation, commissioning, electrical controls and service troubleshooting.</p><ul class="hvac-offer-list"><li>Installation &amp; startup procedures</li><li>Refrigeration cycle &amp; system operation</li><li>Electrical controls &amp; communication wiring</li><li>Diagnostics, charging &amp; troubleshooting</li></ul></div>
      <div class="hvac-offer"><h4>Commercial Chillers</h4><p>Develop practical skills for understanding, operating and troubleshooting commercial chilled-water equipment and controls.</p><ul class="hvac-offer-list"><li>Chilled-water system fundamentals</li><li>Compressors, pumps &amp; heat exchangers</li><li>Controls, sensors &amp; safety circuits</li><li>Operational diagnostics &amp; troubleshooting</li></ul></div>
      <div class="hvac-offer"><h4>Packaged Air Units</h4><p>Service-focused training for commercial packaged and rooftop HVAC equipment from sequence of operation through diagnostics.</p><ul class="hvac-offer-list"><li>Heating &amp; cooling sequence of operation</li><li>Airflow, motors, blowers &amp; components</li><li>Electrical controls &amp; safety circuits</li><li>Startup, maintenance &amp; fault diagnosis</li></ul></div>
      <div class="hvac-offer"><h4>Compressed Air Dryers</h4><p>Industrial training covering moisture removal, air treatment and troubleshooting of compressed-air drying systems.</p><ul class="hvac-offer-list"><li>Refrigerated &amp; desiccant dryer operation</li><li>Filtration &amp; moisture separation</li><li>Dew point &amp; air-quality fundamentals</li><li>Preventive maintenance &amp; troubleshooting</li></ul></div>
     </div>
    </div>
    <button class="hvac-arrow hvac-next" id="hvacNext" type="button" aria-label="Next HVAC/R course">›</button>
    <div class="hvac-counter" id="hvacCounter">1 / 4</div>
   </div>
   <div class="hvac-dots" id="hvacDots"></div>
   <div class="coming-note">UPCOMING PROGRAM · Browse each hands-on HVAC/R and industrial systems course.</div>
  </article>

  <article class="program-card coming-soon hvacr-coming-soon" data-title="Industrial Rectifier Training">
   <div class="badge">COMING SOON</div>
   <div class="pc-head"><div class="eyebrow">Metal Finishing + Power Systems</div><h3>Industrial Rectifier Training</h3><div class="program-duration">4-HOUR COURSE</div><div class="price">COMING SOON <small>/ HANDS-ON TRAINING</small></div></div>
   <div class="hvac-offer-slider" id="rectifierOfferSlider">
    <button class="hvac-arrow hvac-prev" id="rectifierPrev" type="button" aria-label="Previous rectifier course">‹</button>
    <div class="hvac-offer-viewport">
     <div class="hvac-offer-track" id="rectifierOfferTrack">
      <div class="hvac-offer"><h4>Rectifier Fundamentals</h4><p>Hands-on training focused on industrial DC rectifiers used in electroplating, anodizing and metal-finishing processes.</p><ul class="hvac-offer-list"><li>AC input &amp; DC output fundamentals</li><li>Voltage, current &amp; polarity</li><li>Transformer and rectification principles</li><li>Safe startup, shutdown &amp; operation</li></ul></div>
      <div class="hvac-offer"><h4>Controls &amp; Automation</h4><p>Understand how rectifiers interface with PLCs, remote controls and automated metal-finishing process equipment.</p><ul class="hvac-offer-list"><li>PLC start/stop &amp; permissive circuits</li><li>Analog voltage &amp; current commands</li><li>4–20 mA / 0–10 V signals</li><li>Interlocks, alarms &amp; remote operation</li></ul></div>
      <div class="hvac-offer"><h4>DC Output &amp; Ripple</h4><p>Learn to evaluate rectifier output quality and understand how DC voltage, current and ripple affect finishing processes.</p><ul class="hvac-offer-list"><li>DC voltage &amp; amperage measurements</li><li>AC ripple measurement &amp; calculation</li><li>Load testing &amp; output verification</li><li>Process-related power quality diagnostics</li></ul></div>
      <div class="hvac-offer"><h4>Troubleshooting &amp; Repair</h4><p>Develop a systematic approach to diagnosing industrial rectifier faults from incoming power through the DC output.</p><ul class="hvac-offer-list"><li>Fuses, breakers &amp; power components</li><li>SCR / diode troubleshooting concepts</li><li>Cooling, thermal &amp; overcurrent faults</li><li>Electrical testing &amp; fault isolation</li></ul></div>
     </div>
    </div>
    <button class="hvac-arrow hvac-next" id="rectifierNext" type="button" aria-label="Next rectifier course">›</button>
    <div class="hvac-counter" id="rectifierCounter">1 / 4</div>
   </div>
   <div class="hvac-dots" id="rectifierDots"></div>
   <div class="coming-note">UPCOMING PROGRAM · Browse hands-on industrial rectifier training for metal-finishing applications.</div>
  </article>
 </div>
</section>


<div class="enroll-modal" id="enrollModal" aria-hidden="true">
 <div class="enroll-modal-box" role="dialog" aria-modal="true" aria-label="Program enrollment">
  <div class="enroll-modal-title" id="enrollModalTitle">Program Enrollment</div>
  <button class="enroll-modal-close" id="enrollModalClose" type="button" aria-label="Close enrollment">×</button>
  <div id="enrollModalBody"></div>
 </div>
</div>

<section class="enroll-title"><div class="eyebrow">Quick enrollment</div><h2>Let's get you scheduled.</h2><p>Complete each requirement to move forward.</p></section>

<form class="form" id="enrollmentForm" onsubmit="return false">
 <section class="step active" id="s1"><div class="num">01 / 08</div><label for="name">What's your name?</label><input id="name" data-field placeholder="First and last name" autocomplete="name"><div class="actions"><button class="next" type="button">CONTINUE →</button></div></section>
 <section class="step" id="s2"><div class="num">02 / 08</div><label for="phone">Best phone number?</label><input id="phone" data-field type="tel" placeholder="(555) 555-5555" autocomplete="tel"><div class="actions"><button class="next" type="button">CONTINUE →</button></div></section>
 <section class="step" id="s3"><div class="num">03 / 08</div><label for="email">What's your email?</label><input id="email" data-field type="email" placeholder="you@example.com" autocomplete="email"><div class="actions"><button class="next" type="button">CONTINUE →</button></div></section>
 <section class="step" id="s4"><div class="num">04 / 08</div><label for="electrical">Electrical experience</label><select id="electrical" data-field><option value="">Select level</option><option>None — starting from zero</option><option>Basic — tools, wiring, meters</option><option>Intermediate — commercial / industrial</option><option>Advanced — electrician / technician</option></select><div class="note">Selecting an option automatically advances.</div></section>
 <section class="step" id="s5"><div class="num">05 / 08</div><label for="plcexp">PLC experience</label><select id="plcexp" data-field><option value="">Select level</option><option>None</option><option>Basic ladder logic</option><option>Some field experience</option><option>Experienced programmer / technician</option></select></section>
 <section class="step" id="s6"><div class="num">06 / 08</div><label for="program">Desired program</label><select id="program" data-field>
<option value="">Choose program</option>
@foreach ($programs as $p)
<option value="{{ $p->id }}" data-price-cents="{{ $p->price_cents }}">{{ $p->name }} — {{ $p->price_label }}</option>
@endforeach
</select>
<div class="note">Program pricing shown for enrollment selection.</div></section>
 <section class="step" id="s7"><div class="num">07 / 08</div><label for="session">Preferred session</label><select id="session" data-field>
<option value="">Choose session</option>
@foreach ($sessionSlots as $slot)
<option value="{{ $slot }}">{{ $slot }}</option>
@endforeach
</select></section>
 <section class="step" id="s8"><div class="num">08 / 08</div><label for="date">Preferred start date</label><input id="date" data-field type="date" min="{{ $minDate }}" max="{{ $maxDate }}">
<div id="availability" class="availability">Select a date to view availability.</div>
<div class="week"><span>MON–SAT OPEN</span><span class="closed">SUN CLOSED</span></div>
<div class="actions"><button class="next" type="button">COMPLETE →</button></div></section>
 <section class="done" id="complete"><div class="check">✓</div><h2>Enrollment request ready.</h2><p id="completeMsg">Review your details, then continue to the waiver.</p><button class="submit" id="checkoutBtn" type="button">CONTINUE TO CHECKOUT →</button></section>
</form>


<section class="waiver" id="waiver">
 <div class="waiver-head"><div class="eyebrow">Required before checkout</div><h2>Student Training Waiver & Acknowledgment</h2><p>Review, complete, and sign below. Checkout remains locked until this acknowledgment is completed.</p></div>
 <div class="waiver-scroll">
  <h3>PACIFIC TRADE TECH™ — Training Disclosure</h3>
  <strong>Private, non-degree vocational training.</strong> Each enrollment is one standalone four (4) hour course. Instruction may include PLC, electrical controls, automation, and HMI topics. Core total charges are <strong>{{ $programs->firstWhere('slug','plc-electrical-controls')?->price_label ?? '$1,495' }}</strong>; Advanced total charges are <strong>{{ $programs->firstWhere('slug','advanced-plc-automation')?->price_label ?? '$2,500' }}</strong>. No state/federal student financial aid is offered or accepted for these programs. Successful completion may result in a <strong>Certificate of Completion only</strong>. Public enrollment is permitted. Repeat visits/training are separate paid enrollments.
  <h3>Nature of Training & Assumption of Risk</h3>
  I understand that hands-on industrial training may involve PLCs, HMIs, control panels, relays, contactors, power supplies, motors, VFDs, sensors, actuators, pneumatic devices, electrical conductors, test instruments, tools, machinery, and energized or de-energized training equipment. Risks may include electrical shock or burns, cuts, pinch/crush hazards, moving equipment, stored pneumatic/mechanical energy, slips/falls, tool injuries, equipment malfunction, property damage, serious bodily injury, and in rare circumstances death. I voluntarily participate and assume inherent and reasonably foreseeable risks to the extent permitted by California law.
  <h3>Safety Responsibilities</h3>
  I will follow instructor directions, posted rules, PPE requirements, and applicable lockout/tagout procedures. I will not energize, operate, modify, connect, disconnect, troubleshoot, bypass safeguards, or repair equipment unless specifically authorized. I will immediately report unsafe conditions, injuries, damaged equipment, and near misses. I will not participate while impaired or engage in reckless conduct or horseplay.
  <h3>Release / Limitation of Liability</h3>
  To the fullest extent permitted by California law, I release PACIFIC TRADE TECH™, its operating entity, affiliated entities, owners, members, managers, officers, employees, instructors, agents, landlords, and authorized representatives from claims arising from ordinary inherent risks of my voluntary participation that may lawfully be released. Nothing here waives liability or student rights that cannot legally be waived, including rights arising from fraud, willful injury, violations of law, or other non-waivable statutory rights.
  <h3>Damage, Medical Response & Personal Property</h3>
  I may be held financially responsible, to the extent permitted by law, for physical damage caused by intentional misconduct, vandalism, unauthorized use, or reckless misuse. Normal wear or ordinary equipment failure is not my responsibility. I authorize staff to contact emergency medical services when reasonably necessary and understand I remain responsible for my medical expenses except where another party is legally responsible. I am responsible for safeguarding personal property, subject to applicable law.
  <h3>Certificate / No Employment or License Guarantee</h3>
  A PACIFIC TRADE TECH™ Certificate of Completion is not an academic degree, California contractor license, electrician certification, professional license, occupational license, or governmental credential. Training does not guarantee employment, wages, promotion, licensing, third-party certification, or employer acceptance.
  <h3>Attendance, Payment, Cancellation & Refund Rights</h3>
  Enrollment is for the selected four-hour session. A Certificate of Completion may require attendance, participation, required exercises, and safety compliance. Any cancellation, withdrawal, refund, or other student rights required by applicable California law control over conflicting terms. Nothing in this acknowledgment waives non-waivable statutory rights.
  <h3>Training Materials & Records</h3>
  PACIFIC TRADE TECH™ training materials may be protected intellectual property and may not be commercially reproduced, sold, published, distributed, uploaded, sublicensed, or represented as the student's own without authorization, except where law provides otherwise. PACIFIC TRADE TECH™ may retain records needed to document enrollment and authenticate Certificates of Completion, subject to applicable privacy law and authorizations.
 </div>
 <div class="waiver-grid">
  <div><label>Student full legal name *</label><input id="waiverName" autocomplete="name"></div>
  <div><label>Phone *</label><input id="waiverPhone" type="tel" autocomplete="tel"></div>
  <div class="full"><label>Address *</label><input id="waiverAddress" autocomplete="street-address" placeholder="Street address, city, state, ZIP"></div>
  <div><label>Emergency contact *</label><input id="waiverEmergency" placeholder="Full name"></div>
  <div><label>Emergency phone *</label><input id="waiverEmergencyPhone" type="tel"></div>
  <div><label>Selected program</label><input id="waiverProgram" readonly></div>
  <div><label>Scheduled date / session</label><input id="waiverSchedule" readonly></div>
 </div>
 <label class="waiver-check"><input id="waiverAgree" type="checkbox"><span>I have read and understand this entire Student Training Waiver & Acknowledgment, had an opportunity to ask questions, understand the risks and program limitations, and voluntarily agree to its terms. <strong>* Required</strong></span></label>
 <label class="waiver-check"><input id="photoConsent" type="checkbox"><span><strong>Optional photo/publicity consent:</strong> I authorize PACIFIC TRADE TECH™ to photograph or record me during training and use my image for educational or promotional purposes. Leaving this unchecked does not affect enrollment.</span></label>
 <div class="sig-wrap"><div class="sig-label">STUDENT SIGNATURE * — sign inside the box</div><canvas id="signaturePad" class="signature-pad"></canvas><div class="sig-actions"><button id="clearSignature" type="button">CLEAR SIGNATURE</button><span class="stamp">Signed: <b id="studentStamp">Not signed</b></span></div></div>
 <div class="inhouse"><strong>PACIFIC TRADE TECH™ ACCEPTANCE — IN-HOUSE USE ONLY</strong><br>Representative: ______________________________ &nbsp;&nbsp; Signature: ______________________________<br>Date/Time: ______________________________</div>
 <div class="waiver-error" id="waiverError">Complete all required fields, accept the acknowledgment, and provide your signature before continuing.</div>
 <button class="waiver-action" id="waiverContinue" type="button">ACCEPT & CONTINUE TO CHECKOUT →</button>
</section>

<section class="checkout" id="checkout">
 <div class="seat-timer" id="seatTimer">
  <div class="seat-timer-copy"><strong>Your seat is temporarily reserved</strong>Complete checkout before the reservation expires.</div>
  <div class="seat-clock" id="seatClock">10:00</div>
 </div>
 <div class="checkout-head">
  <div class="eyebrow">Step 2 — Checkout</div>
  <h2>Reserve your seat.</h2>
  <p>Choose how you would like to pay.</p>
 </div>

 <div class="order">
  <div class="order-row"><span>Program</span><strong id="coProgram">—</strong></div>
  <div class="order-row"><span>Session</span><strong id="coSession">—</strong></div>
  <div class="order-row"><span>Start date</span><strong id="coDate">—</strong></div>
  <div class="order-row total"><span>Program total</span><strong id="coTotal">$0.00</strong></div>
 </div>

 <div class="pay-options">
  <label class="pay-card" id="fullCard">
   <input type="radio" name="payment" value="full">
   <div class="pay-title">💳 Pay by Credit Card</div>
   <div class="pay-desc">Pay the full program tuition today and secure your enrollment.</div>
  </label>

  <label class="pay-card" id="onsiteCard">
   <input type="radio" name="payment" value="onsite">
   <div class="pay-title">🏫 Pay Balance Onsite</div>
   <div class="pay-desc">Pay a 30% deposit today. The remaining 70% is due onsite.</div>
  </label>
 </div>

 <div class="deposit" id="depositNotice">
  <strong>30% DEPOSIT REQUIRED TODAY</strong><br>
  The deposit is non-refundable for a no-show or cancellation. The remaining 70% balance is due onsite before training.
  <div class="order-row" style="padding-bottom:0"><span>Due today</span><strong id="dueToday">$0.00</strong></div>
  <div class="order-row" style="padding-bottom:0"><span>Due onsite</span><strong id="dueOnsite">$0.00</strong></div>
 </div>

 <div class="card-fields" id="cardFields">
  <div id="stripe-card-element" class="stripe-mount"></div>
  <div id="stripe-card-error" class="pay-error" role="alert"></div>
 </div>

 <button class="checkout-action" id="payBtn" type="button">SELECT PAYMENT METHOD</button>
 <div class="secure">🔒 Card details are entered directly with Stripe and never reach our servers.</div>
</section>

</div>

<section class="verify-section" id="graduateVerification">
 <div class="verify-head">
  <div class="eyebrow">PACIFIC TRADE TECH Graduate Registry</div>
  <h2>Verify a Course Completion</h2>
  <p>Confirm whether a student has successfully completed recognized training through PACIFIC TRADE TECH. Search the public graduate registry by student name or Certificate of Completion number.</p>
 </div>
 <div class="verify-box">
  <label class="verify-label" for="graduateSearch">Student Name or Certificate Number</label>
  <div class="verify-search">
   <input id="graduateSearch" type="search" placeholder="Example: John Smith or PTT-2026-00124">
   <button id="graduateSearchBtn" type="button">VERIFY RECORD →</button>
  </div>
  <div class="verify-help">Public results may display the student's photo, completion date, recognized course(s), and certificate number. Mockup registry only.</div>
  <div class="verify-result" id="graduateResult" aria-live="polite"></div>
 </div>
</section>

<footer class="site-footer"><strong>PACIFIC TRADE TECH<span class="tm">™</span></strong> &nbsp; © 2026<div class="contact">Enrollment &amp; Course Information &nbsp;•&nbsp; <a href="tel:+18009974607">1(800)997-4607</a></div></footer>


<div class="expired-overlay" id="expiredOverlay">
 <div class="expired-box">
  <div class="expire-icon">⌛</div>
  <h2>Your seat reservation expired.</h2>
  <p>The selected seat is no longer being held. Please redo the enrollment form and verify current date and seat availability before returning to checkout.</p>
  <button id="restartEnrollment" type="button">CHECK AVAILABILITY AGAIN →</button>
 </div>
</div>


@endsection

@push('head')
<script src="https://js.stripe.com/v3/"></script>
@endpush

@push('scripts')
<script>
window.PTT = {
  csrf:        document.querySelector('meta[name="csrf-token"]').content,
  stripeKey:   @json($stripeKey),
  holdSeconds: @json($holdSeconds),
  routes: {
    availability: @json(route('api.availability')),
    enrollments:  @json(route('api.enrollments.store')),
    verify:       @json(route('api.verify')),
  }
};
</script>
<script src="{{ \App\Support\Asset::url('js/app.js') }}" defer></script>
@endpush
