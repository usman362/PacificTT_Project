/* ══════════════════════════════════════════════════════════════════════
   Instructor onboarding — the delivered flow, talking to the server.
   Step logic and markup are the client's; only verification and submit
   are real now.
   ══════════════════════════════════════════════════════════════════════ */
(function () {
  'use strict';

  const el = id => document.getElementById(id);
  let signed = false;

  function show(n) {
    document.querySelectorAll('.step').forEach((x, i) => x.classList.toggle('active', i === n - 1));
    const bar = el('bar');
    if (bar) bar.style.width = (n * 25) + '%';
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function closedPanel() {
    const n = el('normal'), c = el('closed');
    if (n) n.style.display = 'none';
    if (c) c.style.display = 'block';
  }

  /* One place for messages, so nothing fails silently. */
  function notify(field, message) {
    let box = el('pttNotice');
    if (!box) {
      box = document.createElement('div');
      box.id = 'pttNotice';
      box.style.cssText =
        'background:#fff0f0;border-left:4px solid #b93434;color:#8f2626;padding:11px 13px;' +
        'border-radius:8px;margin:12px 0;font-size:14px;font-weight:600';
      const active = document.querySelector('.step.active');
      active?.insertBefore(box, active.querySelector('.actions') || null);
    }
    const active = document.querySelector('.step.active');
    if (active && box.parentElement !== active) {
      active.insertBefore(box, active.querySelector('.actions') || null);
    }
    box.textContent = message;
    box.style.display = 'block';
    if (field) el(field)?.focus();
  }

  function clearNotice() {
    const box = el('pttNotice');
    if (box) box.style.display = 'none';
  }

  async function post(url, payload, button) {
    const label = button?.textContent;
    if (button) { button.disabled = true; button.textContent = 'Please wait…'; }
    try {
      const res = await fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify(payload)
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok) {
        if (data.closed) { closedPanel(); return null; }
        const first = data.errors ? Object.values(data.errors).flat()[0] : null;
        throw new Error(first || data.message || 'That did not work. Please try again.');
      }
      return data;
    } finally {
      if (button) { button.disabled = false; button.textContent = label; }
    }
  }

  /* ── step 1: the emailed code ─────────────────────────────────────── */
  window.pttVerify = async function (button) {
    clearNotice();
    const code = (el('otp')?.value || '').trim();
    if (!/^\d{6}$/.test(code)) { notify('otp', 'Enter the 6-digit code from your email.'); return; }
    try {
      const r = await post(window.PTT_ONBOARD.verify, { code }, button);
      if (r) { clearNotice(); show(2); }
    } catch (e) { notify('otp', e.message); }
  };

  /* ── step 2: their details ────────────────────────────────────────── */
  window.pttProfileNext = function () {
    clearNotice();
    const required = ['name', 'phone', 'email', 'address', 'city'];
    const missing = required.find(id => !el(id)?.value.trim());
    if (missing) { notify(missing, 'Complete all instructor information before continuing.'); return; }
    show(3);
  };

  /* ── step 3: agreement + typed signature ──────────────────────────── */
  window.sign = function () {
    clearNotice();
    if (!el('agree')?.checked) { notify(null, 'Read and acknowledge the agreement before signing.'); return; }
    const name = el('name')?.value.trim();
    if (!name) { notify('name', 'Your legal name is required before signing.'); return; }
    const sig = el('sig');
    if (sig) { sig.textContent = name; sig.classList.add('signed'); }
    if (el('stamp')) el('stamp').value = new Date().toLocaleString();
    signed = true;
  };

  window.pttAgreementNext = function () {
    clearNotice();
    if (!el('agree')?.checked || !signed) {
      notify(null, 'Acknowledge the agreement and apply your signature to continue.');
      return;
    }
    const rn = el('reviewName');
    if (rn) rn.textContent = el('name').value.trim();
    show(4);
  };

  /* ── step 4: submit, once ─────────────────────────────────────────── */
  window.pttSubmit = async function (button) {
    clearNotice();
    if (!el('finalAck')?.checked) { notify(null, 'Confirm the final certification before submitting.'); return; }
    try {
      const r = await post(window.PTT_ONBOARD.submit, {
        name:      el('name').value.trim(),
        phone:     el('phone').value.trim(),
        email:     el('email').value.trim(),
        address:   el('address').value.trim(),
        city:      el('city').value.trim(),
        signature: el('sig')?.textContent.trim(),
        agreed:    el('agree').checked ? 1 : 0,
        certified: el('finalAck').checked ? 1 : 0
      }, button);
      if (r) closedPanel();          // the design's "Onboarding Submitted" panel
    } catch (e) { notify(null, e.message); }
  };
})();
