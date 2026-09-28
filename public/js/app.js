/* Pacific Trade Tech — enrollment funnel
   Talks to the Laravel API. Card details are handled by Stripe Elements and
   never pass through this application. */
(function () {
  'use strict';

  var cfg = window.PTT || {};
  var state = { enrollmentId: null, reference: null, holdExpiresAt: null, paymentType: null };

  /* ── helpers ─────────────────────────────────────────────────────── */
  function $(id) { return document.getElementById(id); }

  function api(url, options) {
    options = options || {};
    return fetch(url, {
      method: options.method || 'GET',
      headers: Object.assign({
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': cfg.csrf
      }, options.body ? { 'Content-Type': 'application/json' } : {}),
      body: options.body ? JSON.stringify(options.body) : undefined,
      credentials: 'same-origin'
    }).then(function (res) {
      return res.json().catch(function () { return {}; }).then(function (json) {
        return { ok: res.ok, status: res.status, data: json };
      });
    });
  }

  function money(cents) {
    return (cents / 100).toLocaleString('en-US', { style: 'currency', currency: 'USD' });
  }

  function firstError(data, fallback) {
    if (data && data.errors) {
      for (var k in data.errors) { if (data.errors[k] && data.errors[k][0]) return data.errors[k][0]; }
    }
    return (data && data.message) || fallback;
  }

  /* ── step machine ────────────────────────────────────────────────── */
  var fields = [].slice.call(document.querySelectorAll('[data-field]'));
  var steps  = [].slice.call(document.querySelectorAll('.step'));
  var done   = $('complete');
  var fill   = $('fill');
  var pct    = $('pct');
  var dateField = $('date');
  var availability = $('availability');
  var current = 0;

  function updateProgress() {
    var completed = fields.filter(function (f) { return f.value.trim() !== ''; }).length;
    var p = Math.round((completed / fields.length) * 100);
    fill.style.width = p + '%';
    pct.textContent = p + '%';
  }

  function showStep(index) {
    steps.forEach(function (s, i) { s.classList.toggle('active', i === index); });
    done.classList.remove('active');
    current = index;
    var anchor = document.querySelector('.enroll-title');
    window.scrollTo({ top: anchor.offsetTop - 70, behavior: 'smooth' });
    setTimeout(function () { fields[index].focus({ preventScroll: true }); }, 350);
  }

  function flagInvalid(field) {
    field.focus();
    field.classList.add('is-invalid');
    setTimeout(function () { field.classList.remove('is-invalid'); }, 900);
  }

  function validCurrent() {
    var field = fields[current];
    if (field === dateField && field.value) {
      var d = new Date(field.value + 'T12:00:00');
      if (d.getDay() === 0) return false;
    }
    return field.value.trim() !== '';
  }

  function next() {
    var field = fields[current];
    if (!validCurrent()) { flagInvalid(field); return; }
    updateProgress();

    if (current < steps.length - 1) {
      showStep(current + 1);
      return;
    }
    submitEnrollment();
  }

  /* ── availability (live, from the server) ────────────────────────── */
  var availabilityTimer = null;

  function checkAvailability() {
    var programId = $('program').value;
    var slot      = $('session').value;
    var date      = dateField.value;

    if (!programId || !slot || !date) {
      availability.className = 'availability';
      availability.textContent = 'Select a date to view availability.';
      return;
    }

    availability.className = 'availability';
    availability.textContent = 'Checking availability…';

    var url = cfg.routes.availability
      + '?program_id=' + encodeURIComponent(programId)
      + '&session_slot=' + encodeURIComponent(slot)
      + '&date=' + encodeURIComponent(date);

    clearTimeout(availabilityTimer);
    availabilityTimer = setTimeout(function () {
      api(url).then(function (r) {
        if (!r.ok) {
          availability.className = 'availability closed';
          availability.textContent = firstError(r.data, 'Could not check availability.');
          return;
        }
        if (!r.data.open) {
          availability.className = 'availability closed';
          availability.textContent = r.data.reason || 'That class is unavailable.';
          if (/Sunday/.test(r.data.reason || '')) { dateField.value = ''; }
          updateProgress();
          return;
        }
        availability.className = 'availability open';
        availability.textContent = 'AVAILABLE — ' + r.data.seats_remaining + ' seat'
          + (r.data.seats_remaining === 1 ? '' : 's') + ' remaining';
        updateProgress();
      });
    }, 180);
  }

  /* ── create the enrolment ────────────────────────────────────────── */
  var completeMsg = $('completeMsg');

  function submitEnrollment() {
    var btn = steps[steps.length - 1].querySelector('.next');
    var original = btn ? btn.textContent : '';
    if (btn) { btn.disabled = true; btn.textContent = 'SAVING…'; }

    api(cfg.routes.enrollments, {
      method: 'POST',
      body: {
        name:                  $('name').value.trim(),
        phone:                 $('phone').value.trim(),
        email:                 $('email').value.trim(),
        electrical_experience: $('electrical').value,
        plc_experience:        $('plcexp').value,
        program_id:            $('program').value,
        session_slot:          $('session').value,
        preferred_date:        dateField.value
      }
    }).then(function (r) {
      if (btn) { btn.disabled = false; btn.textContent = original; }

      if (!r.ok) {
        availability.className = 'availability closed';
        availability.textContent = firstError(r.data, 'We could not save your enrollment. Please try again.');
        return;
      }

      state.enrollmentId  = r.data.enrollment.id;
      state.reference     = r.data.reference;
      state.holdExpiresAt = new Date(r.data.enrollment.hold_expires);

      if (completeMsg) {
        completeMsg.innerHTML = 'Your reference is <strong>' + r.data.reference + '</strong>. '
          + 'Your seat is held while you complete the waiver.';
      }

      steps[current].classList.remove('active');
      done.classList.add('active');
      fill.style.width = '100%';
      pct.textContent = '100%';
    });
  }

  /* ── wiring ──────────────────────────────────────────────────────── */
  document.querySelectorAll('.next').forEach(function (b) { b.addEventListener('click', next); });

  fields.forEach(function (field) {
    field.addEventListener('input', updateProgress);
    field.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') { e.preventDefault(); next(); }
    });
    if (field.tagName === 'SELECT') {
      field.addEventListener('change', function () {
        updateProgress();
        if (field === $('program') || field === $('session')) { checkAvailability(); }
        if (field.value && field !== $('session')) { setTimeout(next, 180); }
        else if (field.value) { setTimeout(next, 180); }
      });
    }
  });

  /* Chrome only opens the date picker when the small calendar icon is hit —
     clicking the text does nothing, which reads as a dead field. Open it from
     anywhere on the input (and from the keyboard). */
  function openPicker() {
    if (typeof dateField.showPicker !== 'function') return;
    try { dateField.showPicker(); } catch (e) { /* needs a user gesture; ignore */ }
  }
  dateField.addEventListener('click', openPicker);
  dateField.addEventListener('focus', openPicker);
  dateField.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openPicker(); }
  });

  /* A native date input fires `change` while the user is still typing, so
     mid-entry the value can be something like 0001-08-08. Don't send those to
     the server — wait until the date is actually plausible. */
  function dateLooksComplete(v) {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(v)) return false;
    var year = parseInt(v.slice(0, 4), 10);
    var thisYear = new Date().getFullYear();
    return year >= thisYear && year <= thisYear + 5;
  }

  dateField.addEventListener('change', function () {
    if (!dateField.value) {
      availability.className = 'availability';
      availability.textContent = 'Select a date to view availability.';
      updateProgress();
      return;
    }
    if (!dateLooksComplete(dateField.value)) {
      // still being typed — say nothing rather than flashing an error
      availability.className = 'availability';
      availability.textContent = 'Select a date to view availability.';
      return;
    }
    checkAvailability();
  });

  /* ── program cards open the enrollment form in a modal ─────────────── */
  var enrollModal      = $('enrollModal');
  var enrollModalBody  = $('enrollModalBody');
  var enrollModalTitle = $('enrollModalTitle');
  var enrollmentForm   = $('enrollmentForm');
  var enrollmentHome   = document.createComment('enrollment-form-home');
  enrollmentForm.parentNode.insertBefore(enrollmentHome, enrollmentForm);

  function openEnrollmentModal(card) {
    // Restore the form if the modal is opened again after a completed enrollment.
    enrollmentForm.style.display = '';
    var selected = card.dataset.program || '';
    enrollModalTitle.textContent = (card.dataset.title || 'Program Enrollment') + ' · Enrollment';
    enrollModalBody.appendChild(enrollmentForm);
    var programField = $('program');
    if (selected && [].some.call(programField.options, function (o) { return o.value === selected; })) {
      programField.value = selected;
      updateProgress();
      checkAvailability();
    }
    showStep(0);
    enrollModal.classList.add('open');
    enrollModal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('modal-open');
  }

  function closeEnrollmentModal() {
    enrollModal.classList.remove('open');
    enrollModal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('modal-open');
    enrollmentHome.parentNode.insertBefore(enrollmentForm, enrollmentHome.nextSibling);
  }

  document.querySelectorAll('.program-card').forEach(function (card) {
    card.setAttribute('tabindex', '0');
    card.setAttribute('role', 'button');
    card.setAttribute('aria-label', 'Open enrollment for ' + (card.dataset.title || 'this program'));
    card.addEventListener('click', function (e) {
      if (e.target.closest('.hvac-arrow,.hvac-dot')) return;
      openEnrollmentModal(card);
    });
    card.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openEnrollmentModal(card); }
    });
  });
  $('enrollModalClose').addEventListener('click', closeEnrollmentModal);
  enrollModal.addEventListener('click', function (e) { if (e.target === enrollModal) closeEnrollmentModal(); });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && enrollModal.classList.contains('open')) closeEnrollmentModal();
  });

  updateProgress();
  window.PTTState = state;
  window.PTTApi = api;
  window.PTTMoney = money;
  window.PTTFirstError = firstError;
  window.PTTCloseEnrollmentModal = closeEnrollmentModal;
})();

/* ── waiver, checkout and verification ─────────────────────────────── */
(function () {
  'use strict';

  var cfg   = window.PTT || {};
  var state = window.PTTState;
  var api   = window.PTTApi;
  var money = window.PTTMoney;
  var firstError = window.PTTFirstError;

  function $(id) { return document.getElementById(id); }

  var form         = document.querySelector('.form');
  var waiver       = $('waiver');
  var checkout     = $('checkout');
  var checkoutBtn  = $('checkoutBtn');
  var programField = $('program');
  var sessionField = $('session');
  var dateField    = $('date');

  /* ── signature pad ───────────────────────────────────────────────── */
  var sigCanvas = $('signaturePad');
  var sigCtx    = sigCanvas.getContext('2d');
  var studentStamp = $('studentStamp');
  var signing = false, hasSignature = false, signatureTimestamp = '';

  function sizeSignaturePad() {
    var ratio = Math.max(window.devicePixelRatio || 1, 1);
    var r = sigCanvas.getBoundingClientRect();
    if (!r.width) return;
    // Preserve an existing trace across resizes (e.g. phone rotation).
    var prev = hasSignature ? sigCanvas.toDataURL('image/png') : null;
    sigCanvas.width  = Math.round(r.width * ratio);
    sigCanvas.height = Math.round(r.height * ratio);
    sigCtx.setTransform(ratio, 0, 0, ratio, 0, 0);
    sigCtx.lineWidth = 2.2;
    sigCtx.lineCap = 'round';
    sigCtx.strokeStyle = '#101820';
    if (prev) {
      var img = new Image();
      img.onload = function () { sigCtx.drawImage(img, 0, 0, r.width, r.height); };
      img.src = prev;
    }
  }

  function sigPoint(e) {
    var r = sigCanvas.getBoundingClientRect();
    var t = e.touches ? e.touches[0] : e;
    return [t.clientX - r.left, t.clientY - r.top];
  }
  function sigStart(e) { e.preventDefault(); signing = true; var p = sigPoint(e); sigCtx.beginPath(); sigCtx.moveTo(p[0], p[1]); }
  function sigMove(e)  { if (!signing) return; e.preventDefault(); var p = sigPoint(e); sigCtx.lineTo(p[0], p[1]); sigCtx.stroke(); hasSignature = true; }
  function sigEnd()    {
    if (!signing) return;
    signing = false;
    if (hasSignature) {
      signatureTimestamp = new Date().toLocaleString('en-US', { dateStyle: 'medium', timeStyle: 'short' });
      studentStamp.textContent = signatureTimestamp;
    }
  }
  ['mousedown', 'touchstart'].forEach(function (ev) { sigCanvas.addEventListener(ev, sigStart, { passive: false }); });
  ['mousemove', 'touchmove'].forEach(function (ev) { sigCanvas.addEventListener(ev, sigMove, { passive: false }); });
  ['mouseup', 'mouseleave', 'touchend', 'touchcancel'].forEach(function (ev) { sigCanvas.addEventListener(ev, sigEnd); });

  $('clearSignature').addEventListener('click', function () {
    hasSignature = false; signatureTimestamp = '';
    sizeSignaturePad();
    sigCtx.clearRect(0, 0, sigCanvas.width, sigCanvas.height);
    studentStamp.textContent = 'Not signed';
  });
  window.addEventListener('resize', function () { if (waiver.classList.contains('active')) sizeSignaturePad(); });

  /* ── enrolment → waiver ──────────────────────────────────────────── */
  checkoutBtn.addEventListener('click', function () {
    $('waiverName').value  = $('name').value;
    $('waiverPhone').value = $('phone').value;
    $('waiverProgram').value = programField.options[programField.selectedIndex]
      ? programField.options[programField.selectedIndex].text : '';
    var nice = dateField.value
      ? new Date(dateField.value + 'T12:00:00').toLocaleDateString('en-US',
          { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' })
      : '—';
    $('waiverSchedule').value = nice + ' · ' + sessionField.value;

    form.style.display = 'none';
    // Enrollment is complete: dismiss the program modal before showing the waiver.
    if (window.PTTCloseEnrollmentModal) window.PTTCloseEnrollmentModal();
    waiver.classList.add('active');
    setTimeout(sizeSignaturePad, 80);
    setTimeout(function () { window.scrollTo({ top: waiver.offsetTop - 85, behavior: 'smooth' }); }, 60);
  });

  /* ── waiver → server ─────────────────────────────────────────────── */
  var waiverError = $('waiverError');

  $('waiverContinue').addEventListener('click', function () {
    var required = ['waiverName', 'waiverPhone', 'waiverAddress', 'waiverEmergency', 'waiverEmergencyPhone'];
    var filled = required.every(function (id) { return $(id).value.trim(); });
    var ok = filled && $('waiverAgree').checked && hasSignature;

    if (!ok) {
      waiverError.style.display = 'block';
      waiverError.textContent = !filled
        ? 'Please complete every required field.'
        : (!$('waiverAgree').checked ? 'You must agree to the waiver to continue.' : 'A signature is required.');
      return;
    }
    if (!state.enrollmentId) {
      waiverError.style.display = 'block';
      waiverError.textContent = 'Your enrollment was not saved. Please complete the form again.';
      return;
    }
    waiverError.style.display = 'none';

    var btn = this;
    var label = btn.textContent;
    btn.disabled = true; btn.textContent = 'SAVING…';

    api('/api/enrollments/' + state.enrollmentId + '/waiver', {
      method: 'POST',
      body: {
        legal_name:        $('waiverName').value.trim(),
        phone:             $('waiverPhone').value.trim(),
        address:           $('waiverAddress').value.trim(),
        emergency_contact: $('waiverEmergency').value.trim(),
        emergency_phone:   $('waiverEmergencyPhone').value.trim(),
        agreed:            true,
        photo_consent:     $('photoConsent').checked,
        signature:         sigCanvas.toDataURL('image/png')
      }
    }).then(function (r) {
      btn.disabled = false; btn.textContent = label;

      if (r.status === 409) { showExpired(); return; }
      if (!r.ok) {
        waiverError.style.display = 'block';
        waiverError.textContent = firstError(r.data, 'The waiver could not be saved. Please try again.');
        return;
      }
      state.holdExpiresAt = new Date(r.data.hold_expires);
      openCheckout();
    });
  });

  /* ── checkout ────────────────────────────────────────────────────── */
  var coProgram = $('coProgram'), coSession = $('coSession'), coDate = $('coDate'), coTotal = $('coTotal');
  var depositNotice = $('depositNotice'), cardFields = $('cardFields');
  var dueToday = $('dueToday'), dueOnsite = $('dueOnsite'), payBtn = $('payBtn');
  var stripe = null, elements = null, cardElement = null, clientSecret = null;
  var summary = null;

  function openCheckout() {
    waiver.classList.remove('active');
    checkout.classList.add('active');
    window.scrollTo({ top: checkout.offsetTop - 85, behavior: 'smooth' });

    api('/api/enrollments/' + state.enrollmentId + '/checkout').then(function (r) {
      if (!r.ok) return;
      summary = r.data;

      coProgram.textContent = summary.program || '—';
      coSession.textContent = summary.session_slot || '—';
      coDate.textContent = summary.preferred_date
        ? new Date(summary.preferred_date + 'T12:00:00').toLocaleDateString('en-US',
            { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' })
        : '—';
      coTotal.textContent  = money(summary.tuition_cents);
      dueToday.textContent = money(summary.deposit_cents);
      dueOnsite.textContent = money(summary.balance_cents);

      if (summary.expired) { showExpired(); return; }
      startSeatTimer(summary.hold_expires);
    });
  }

  function mountStripe() {
    if (cardElement || !cfg.stripeKey || !window.Stripe) return;
    stripe = window.Stripe(cfg.stripeKey);
    elements = stripe.elements();
    cardElement = elements.create('card', {
      style: { base: { color: '#17202a', fontSize: '16px', '::placeholder': { color: '#8b98a5' } } }
    });
    cardElement.mount('#stripe-card-element');
    cardElement.on('change', function (e) {
      $('stripe-card-error').textContent = e.error ? e.error.message : '';
    });
  }

  document.querySelectorAll('input[name="payment"]').forEach(function (radio) {
    radio.addEventListener('change', function () {
      state.paymentType = radio.value === 'onsite' ? 'deposit' : 'full';
      document.querySelectorAll('.pay-card').forEach(function (c) { c.classList.remove('selected'); });
      radio.closest('.pay-card').classList.add('selected');
      cardFields.classList.add('show');
      depositNotice.classList.toggle('show', state.paymentType === 'deposit');

      var amount = state.paymentType === 'deposit' ? summary.deposit_cents : summary.tuition_cents;
      payBtn.textContent = (state.paymentType === 'deposit' ? 'PAY 30% DEPOSIT — ' : 'PAY FULL TUITION — ') + money(amount);

      if (!cfg.stripeKey) {
        $('stripe-card-error').textContent = 'Payments are not configured yet. Please contact the office to complete enrollment.';
        payBtn.disabled = true;
        return;
      }
      payBtn.disabled = false;
      mountStripe();
      requestIntent();
    });
  });

  function requestIntent() {
    api('/api/enrollments/' + state.enrollmentId + '/intent', {
      method: 'POST', body: { type: state.paymentType }
    }).then(function (r) {
      if (r.status === 409) { showExpired(); return; }
      if (!r.ok) {
        $('stripe-card-error').textContent = firstError(r.data, 'Could not start the payment.');
        payBtn.disabled = true;
        return;
      }
      clientSecret = r.data.client_secret;
    });
  }

  payBtn.addEventListener('click', function () {
    if (!state.paymentType) { return; }
    if (!clientSecret || !stripe || !cardElement) {
      $('stripe-card-error').textContent = 'Payment is still preparing — please try again in a moment.';
      return;
    }
    var label = payBtn.textContent;
    payBtn.disabled = true;
    payBtn.textContent = 'PROCESSING…';
    $('stripe-card-error').textContent = '';

    stripe.confirmCardPayment(clientSecret, {
      payment_method: {
        card: cardElement,
        billing_details: { name: $('waiverName').value, email: $('email').value }
      }
    }).then(function (result) {
      if (result.error) {
        $('stripe-card-error').textContent = result.error.message;
        payBtn.disabled = false;
        payBtn.textContent = label;
        return;
      }
      api('/api/enrollments/' + state.enrollmentId + '/confirm', {
        method: 'POST', body: { payment_intent_id: result.paymentIntent.id }
      }).then(function () { showPaid(); });
    });
  });

  function showPaid() {
    stopSeatTimer();
    checkout.innerHTML =
      '<div class="done active" style="display:block">' +
      '<div class="check">✓</div>' +
      '<h2>Payment received.</h2>' +
      '<p>Your seat is confirmed. Reference <strong>' + (state.reference || '') + '</strong>.<br>' +
      'A confirmation email is on its way.</p>' +
      '</div>';
    window.scrollTo({ top: checkout.offsetTop - 85, behavior: 'smooth' });
  }

  /* ── seat hold timer (driven by the server's expiry) ─────────────── */
  var seatClock = $('seatClock');
  var expiredOverlay = $('expiredOverlay');
  var seatInterval = null;

  function startSeatTimer(iso) {
    stopSeatTimer();
    var expires = iso ? new Date(iso) : state.holdExpiresAt;
    if (!expires) return;

    function tick() {
      var left = Math.max(0, Math.floor((expires - new Date()) / 1000));
      var m = Math.floor(left / 60), s = left % 60;
      seatClock.textContent = String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
      if (left <= 0) { stopSeatTimer(); showExpired(); }
    }
    tick();
    seatInterval = setInterval(tick, 1000);
  }
  function stopSeatTimer() { clearInterval(seatInterval); seatInterval = null; }
  function showExpired() { stopSeatTimer(); expiredOverlay.classList.add('show'); }

  $('restartEnrollment').addEventListener('click', function () { window.location.reload(); });

  /* ── graduate verification ───────────────────────────────────────── */
  var graduateSearch = $('graduateSearch');
  var graduateResult = $('graduateResult');

  function verifyGraduate() {
    var q = graduateSearch.value.trim();
    graduateResult.classList.add('show');

    if (q.length < 3) {
      graduateResult.innerHTML = '<div class="not-found">Enter a student name or Certificate of Completion number to search the registry.</div>';
      return;
    }
    graduateResult.innerHTML = '<div class="not-found">Searching…</div>';

    api(cfg.routes.verify + '?q=' + encodeURIComponent(q)).then(function (r) {
      if (!r.ok || !r.data.found) {
        graduateResult.innerHTML = '<div class="not-found"><strong>No public completion record found.</strong><br>'
          + 'Check the spelling or certificate number and try again.</div>';
        return;
      }
      var c = r.data.certificate;
      var photo = c.photo_url
        ? '<div class="student-photo"><img src="' + c.photo_url + '" alt=""></div>'
        : '<div class="student-photo">👤</div>';

      graduateResult.innerHTML =
        '<div class="student-result">' + photo +
        '<div class="student-info">' +
        '<h3></h3>' +
        '<span class="verified-badge">✓ COMPLETION VERIFIED</span>' +
        '<div class="student-meta"><strong>Certificate:</strong> <span class="v-num"></span><br>' +
        '<strong>Completed:</strong> <span class="v-date"></span><br>' +
        '<strong>Course:</strong> <span class="v-course"></span></div>' +
        '</div></div>';

      // Set as text, never HTML — registry values are user-supplied.
      graduateResult.querySelector('h3').textContent = c.student_name;
      graduateResult.querySelector('.v-num').textContent = c.number;
      graduateResult.querySelector('.v-date').textContent = c.completed_on;
      graduateResult.querySelector('.v-course').textContent = c.course;
    });
  }

  $('graduateSearchBtn').addEventListener('click', verifyGraduate);
  graduateSearch.addEventListener('keydown', function (e) { if (e.key === 'Enter') verifyGraduate(); });
})();

/* ── Hero carousel ─────────────────────────────────────────────────── */
(function () {
  'use strict';

  var track = document.getElementById('sliderTrack');
  var dots  = document.getElementById('sliderDots');
  var prev  = document.getElementById('sliderPrev');
  var next  = document.getElementById('sliderNext');
  if (!track || !dots) return;

  var slides = [].slice.call(document.querySelectorAll('#heroSlider .slide'));
  if (!slides.length) return;

  var index = 0;
  var timer = null;
  // Don't auto-advance for someone who has asked for less motion.
  var autoplay = !window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  slides.forEach(function (_, i) {
    var d = document.createElement('button');
    d.type = 'button';
    d.className = 'slider-dot' + (i === 0 ? ' active' : '');
    d.setAttribute('aria-label', 'Go to slide ' + (i + 1));
    d.addEventListener('click', function () { setSlide(i, true); });
    dots.appendChild(d);
  });

  function setSlide(i, restart) {
    index = (i + slides.length) % slides.length;
    track.style.transform = 'translateX(-' + (index * 100) + '%)';
    [].slice.call(dots.children).forEach(function (d, n) {
      d.classList.toggle('active', n === index);
    });
    if (restart) start();
  }

  function start() {
    clearInterval(timer);
    if (!autoplay) return;
    timer = setInterval(function () { setSlide(index + 1); }, 4200);
  }

  if (prev) prev.addEventListener('click', function () { setSlide(index - 1, true); });
  if (next) next.addEventListener('click', function () { setSlide(index + 1, true); });

  // Pause while the tab is hidden so it isn't spinning in the background.
  document.addEventListener('visibilitychange', function () {
    if (document.hidden) { clearInterval(timer); } else { start(); }
  });

  start();
})();

/* ── Coming-soon course sliders (HVAC/R and rectifier cards) ───────── */
(function () {
  'use strict';

  var autoplay = !window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function courseSlider(sliderId, trackId, dotsId, counterId, prevId, nextId, label) {
    var track   = document.getElementById(trackId);
    var dots    = document.getElementById(dotsId);
    var counter = document.getElementById(counterId);
    var offers  = [].slice.call(document.querySelectorAll('#' + sliderId + ' .hvac-offer'));
    if (!track || !dots || !offers.length) return;

    var index = 0, timer = null;

    offers.forEach(function (_, i) {
      var dot = document.createElement('button');
      dot.type = 'button';
      dot.className = 'hvac-dot' + (i === 0 ? ' active' : '');
      dot.setAttribute('aria-label', 'Show ' + label + ' course ' + (i + 1));
      dot.addEventListener('click', function () { set(i, true); });
      dots.appendChild(dot);
    });

    function set(i, restart) {
      index = (i + offers.length) % offers.length;
      track.style.transform = 'translateX(-' + (index * 100) + '%)';
      [].slice.call(dots.children).forEach(function (d, n) { d.classList.toggle('active', n === index); });
      if (counter) counter.textContent = (index + 1) + ' / ' + offers.length;
      if (restart) start();
    }

    function start() {
      clearInterval(timer);
      if (!autoplay) return;
      timer = setInterval(function () { set(index + 1); }, 5000);
    }

    document.getElementById(prevId).addEventListener('click', function () { set(index - 1, true); });
    document.getElementById(nextId).addEventListener('click', function () { set(index + 1, true); });
    document.addEventListener('visibilitychange', function () {
      if (document.hidden) { clearInterval(timer); } else { start(); }
    });
    start();
  }

  courseSlider('hvacOfferSlider', 'hvacOfferTrack', 'hvacDots', 'hvacCounter', 'hvacPrev', 'hvacNext', 'HVAC/R');
  courseSlider('rectifierOfferSlider', 'rectifierOfferTrack', 'rectifierDots', 'rectifierCounter', 'rectifierPrev', 'rectifierNext', 'rectifier');
})();
