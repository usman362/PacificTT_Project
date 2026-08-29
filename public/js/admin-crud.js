/* ══════════════════════════════════════════════════════════════════════
   Console CRUD — modals, edit, delete, export, settings.
   Sits on top of the delivered console script; adds behaviour only.
   ══════════════════════════════════════════════════════════════════════ */
(function () {
  'use strict';

  const R = () => window.PTT.routes;
  const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

  /* ── plumbing ────────────────────────────────────────────────────── */

  async function api(method, url, payload) {
    const res = await fetch(url, {
      method,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrf()
      },
      body: payload ? JSON.stringify(payload) : undefined
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      const err = new Error(data.message || 'That did not save.');
      err.fields = data.errors || {};
      throw err;
    }
    return data;
  }

  let toastTimer;
  function toast(message, kind) {
    let el = document.querySelector('.toast');
    if (!el) { el = document.createElement('div'); el.className = 'toast'; document.body.appendChild(el); }
    el.textContent = message;
    el.className = 'toast show' + (kind ? ' ' + kind : '');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => el.classList.remove('show'), 3200);
  }
  window.pttToast = toast;

  /* ── modal shell ─────────────────────────────────────────────────── */

  let backdrop, lastFocus;

  function ensureBackdrop() {
    if (backdrop) return backdrop;
    backdrop = document.createElement('div');
    backdrop.className = 'modal-backdrop';
    backdrop.addEventListener('mousedown', e => { if (e.target === backdrop) closeModal(); });
    document.body.appendChild(backdrop);
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape' && backdrop.classList.contains('show')) closeModal();
    });
    return backdrop;
  }

  function openModal({ title, ref, body, actions, wide }) {
    lastFocus = document.activeElement;
    const b = ensureBackdrop();
    b.innerHTML =
      `<div class="modal${wide ? ' wide' : ''}" role="dialog" aria-modal="true" aria-label="${title}">
         <header>
           <div><h2>${title}</h2>${ref ? `<div class="ref">${ref}</div>` : ''}</div>
           <button class="x" type="button" aria-label="Close">&times;</button>
         </header>
         <div class="body"><div class="modal-error"></div>${body}</div>
         <div class="foot">${actions}</div>
       </div>`;
    b.classList.add('show');
    b.querySelector('.x').onclick = closeModal;
    setTimeout(() => b.querySelector('input,select,textarea,button:not(.x)')?.focus(), 40);
    return b.querySelector('.modal');
  }

  function closeModal() {
    backdrop?.classList.remove('show');
    if (backdrop) backdrop.innerHTML = '';
    lastFocus?.focus();
  }
  window.pttCloseModal = closeModal;
  window.pttOpenModal = openModal;

  function modalError(message) {
    const el = backdrop?.querySelector('.modal-error');
    if (!el) return;
    el.textContent = message;
    el.classList.add('show');
    el.scrollIntoView({ block: 'nearest' });
  }

  function markFields(fields) {
    backdrop?.querySelectorAll('.field').forEach(f => f.classList.remove('invalid'));
    Object.entries(fields || {}).forEach(([name, msgs]) => {
      const input = backdrop?.querySelector(`[name="${name}"]`);
      const field = input?.closest('.field');
      if (!field) return;
      field.classList.add('invalid');
      const err = field.querySelector('.err');
      if (err) err.textContent = Array.isArray(msgs) ? msgs[0] : msgs;
    });
  }

  const esc = v => String(v ?? '').replace(/[&<>"']/g, c =>
    ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  function values(form) {
    const out = {};
    form.querySelectorAll('[name]').forEach(el => { out[el.name] = el.value; });
    return out;
  }

  /* ── lookups (programmes, sessions, instructors) ─────────────────── */

  let lookups = null;
  async function getLookups() {
    if (!lookups) lookups = await api('GET', R().lookups);
    return lookups;
  }

  const options = (list, val, label, selected) => list.map(o =>
    `<option value="${esc(o[val])}" ${String(o[val]) === String(selected) ? 'selected' : ''}>${esc(o[label])}</option>`
  ).join('');

  /* ── enrolment form ──────────────────────────────────────────────── */

  const STATUSES = [
    ['started', 'Started'], ['waiver_signed', 'Waiver signed'], ['deposit_paid', 'Deposit'],
    ['paid', 'Paid'], ['completed', 'Completed'], ['cancelled', 'Cancelled'], ['abandoned', 'Abandoned']
  ];

  function enrollmentForm(lk, e) {
    e = e || {};
    const field = (name, label, input) =>
      `<div class="field"><label for="f_${name}">${label}</label>${input}<div class="err"></div></div>`;

    return `<form id="enrollForm"><div class="fieldgrid">
      ${field('name',  'Full name *',  `<input id="f_name" name="name" value="${esc(e.name)}" autocomplete="name">`)}
      ${field('phone', 'Phone *',      `<input id="f_phone" name="phone" value="${esc(e.phone)}" autocomplete="tel">`)}
      <div class="full">${field('email', 'Email *', `<input id="f_email" name="email" type="email" value="${esc(e.email)}" autocomplete="email">`)}</div>
      ${field('electrical', 'Electrical experience',
        `<select id="f_electrical" name="electrical">${options(
          ['None — starting from zero','Basic — tools, wiring, meters','Intermediate — commercial / industrial','Advanced — electrician / technician']
            .map(v => ({ v })), 'v', 'v', e.electrical)}</select>`)}
      ${field('plc', 'PLC experience',
        `<select id="f_plc" name="plc">${options(
          ['None','Basic ladder logic','Some field experience','Experienced programmer / technician']
            .map(v => ({ v })), 'v', 'v', e.plc)}</select>`)}
      ${field('program_id', 'Program *',
        `<select id="f_program_id" name="program_id">${options(lk.programs, 'id', 'name', e.program_id)}</select>`)}
      ${field('session', 'Session *',
        `<select id="f_session" name="session">${options(lk.sessions.map(s => ({ s })), 's', 's', e.session)}</select>`)}
      ${field('date', 'Start date *', `<input id="f_date" name="date" type="date" value="${esc(e.date)}">`)}
      ${field('status', 'Status',
        `<select id="f_status" name="status">${options(STATUSES.map(([v, l]) => ({ v, l })), 'v', 'l', e.status || 'started')}</select>`)}
    </div></form>`;
  }

  async function newEnrollment() {
    const lk = await getLookups();
    openModal({
      title: 'New enrollment',
      body: enrollmentForm(lk, {}),
      actions: `<button class="btn light" type="button" onclick="pttCloseModal()">Cancel</button>
                <button class="btn gold" type="button" id="saveEnroll">Create enrollment</button>`
    });
    document.getElementById('saveEnroll').onclick = async (ev) => {
      const btn = ev.currentTarget; btn.disabled = true;
      try {
        const res = await api('POST', R().enrollStore, values(document.getElementById('enrollForm')));
        closeModal();
        toast(`Enrollment ${res.reference} created.`, 'good');
        setTimeout(() => location.reload(), 700);
      } catch (err) { btn.disabled = false; markFields(err.fields); modalError(err.message); }
    };
  }
  window.newEnrollment = newEnrollment;

  async function editEnrollment(id) {
    const [lk, e] = await Promise.all([getLookups(), api('GET', R().enrollShow.replace('__ID__', id))]);

    const money = n => '$' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2 });
    const paymentRows = e.payments.length
      ? e.payments.map(p => `<div class="readout"><span>${esc(p.type)} · ${esc(p.method)}</span><span>${money(p.amount)} — ${esc(p.card)} — ${esc(p.status)}${p.paid ? ' · ' + esc(p.paid) : ''}</span></div>`).join('')
      : '<p class="note">No payments recorded.</p>';

    const waiver = e.waiver
      ? `<div class="readout"><span>Legal name</span><span>${esc(e.waiver.legal_name)}</span></div>
         <div class="readout"><span>Address</span><span>${esc(e.waiver.address)}</span></div>
         <div class="readout"><span>Emergency</span><span>${esc(e.waiver.emergency)} — ${esc(e.waiver.emergency_ph)}</span></div>
         <div class="readout"><span>Signed</span><span>${esc(e.waiver.signed_at)}</span></div>
         <div class="readout"><span>Staff acceptance</span><span>${esc(e.waiver.staff || 'Pending')}</span></div>
         ${e.waiver.signature ? `<div class="sig-view"><img src="${esc(e.waiver.signature)}" alt="Student signature"></div>` : ''}`
      : '<p class="note">No waiver signed yet.</p>';

    openModal({
      title: e.name, ref: e.reference, wide: true,
      body: enrollmentForm(lk, e) +
        `<h2 style="font-size:15px;margin:22px 0 8px">Money</h2>
         <div class="readout"><span>Tuition</span><span>${money(e.tuition)}</span></div>
         <div class="readout"><span>Paid</span><span>${money(e.paid)}</span></div>
         <div class="readout"><span>Balance</span><span><b>${money(e.balance)}</b></span></div>
         ${paymentRows}
         <h2 style="font-size:15px;margin:22px 0 8px">Waiver</h2>${waiver}`,
      actions: `<button class="btn danger left" type="button" id="delEnroll">Delete</button>
                <button class="btn light" type="button" onclick="pttCloseModal()">Cancel</button>
                <button class="btn gold" type="button" id="saveEnroll">Save changes</button>`
    });

    document.getElementById('saveEnroll').onclick = async (ev) => {
      const btn = ev.currentTarget; btn.disabled = true;
      try {
        await api('PUT', R().enrollUpdate.replace('__ID__', id), values(document.getElementById('enrollForm')));
        closeModal(); toast('Enrollment updated.', 'good');
        setTimeout(() => location.reload(), 700);
      } catch (err) { btn.disabled = false; markFields(err.fields); modalError(err.message); }
    };

    document.getElementById('delEnroll').onclick = async () => {
      if (!confirm(`Delete ${e.reference}? This cannot be undone.`)) return;
      try {
        await api('DELETE', R().enrollDestroy.replace('__ID__', id));
        closeModal(); toast('Enrollment deleted.', 'good');
        setTimeout(() => location.reload(), 700);
      } catch (err) { modalError(err.message); }
    };
  }
  window.editEnrollment = editEnrollment;

  /* ── certificates ────────────────────────────────────────────────── */

  async function issueCertificate() {
    const lk = await getLookups();
    if (!lk.completable.length) {
      toast('No paid enrollment is waiting for a certificate.', 'bad');
      return;
    }
    openModal({
      title: 'Issue certificate',
      body: `<form id="certForm"><div class="fieldgrid">
        <div class="full field"><label for="c_e">Enrollment *</label>
          <select id="c_e" name="enrollment_id">${options(lk.completable, 'id', 'label')}</select><div class="err"></div></div>
        <div class="field"><label for="c_i">Instructor</label>
          <select id="c_i" name="instructor_id"><option value="">—</option>${options(lk.instructors, 'id', 'name')}</select><div class="err"></div></div>
        <div class="field"><label for="c_d">Completed on *</label>
          <input id="c_d" name="completed_on" type="date" value="${new Date().toISOString().slice(0, 10)}"><div class="err"></div></div>
      </div></form>
      <p class="note" style="margin-top:12px">The number is generated on issue, is unique, and is never reused.</p>`,
      actions: `<button class="btn light" type="button" onclick="pttCloseModal()">Cancel</button>
                <button class="btn gold" type="button" id="doIssue">Issue certificate</button>`
    });
    document.getElementById('doIssue').onclick = async (ev) => {
      const btn = ev.currentTarget; btn.disabled = true;
      try {
        const r = await api('POST', R().certIssue, values(document.getElementById('certForm')));
        closeModal(); toast(`Certificate ${r.number} issued.`, 'good');
        setTimeout(() => location.reload(), 800);
      } catch (err) { btn.disabled = false; markFields(err.fields); modalError(err.message); }
    };
  }
  window.issueCertificate = issueCertificate;

  async function revokeCertificate(id, number) {
    openModal({
      title: 'Revoke certificate', ref: number,
      body: `<p class="note">Revoking removes it from the public registry. The record and its number are kept.</p>
             <form id="revForm"><div class="field" style="margin-top:12px">
               <label for="r_reason">Reason *</label>
               <input id="r_reason" name="reason" placeholder="Why is this being revoked?"><div class="err"></div>
             </div></form>`,
      actions: `<button class="btn light" type="button" onclick="pttCloseModal()">Cancel</button>
                <button class="btn danger" type="button" id="doRevoke">Revoke</button>`
    });
    document.getElementById('doRevoke').onclick = async (ev) => {
      const btn = ev.currentTarget; btn.disabled = true;
      try {
        await api('POST', R().certRevoke.replace('__ID__', id), values(document.getElementById('revForm')));
        closeModal(); toast('Certificate revoked.', 'good');
        setTimeout(() => location.reload(), 700);
      } catch (err) { btn.disabled = false; markFields(err.fields); modalError(err.message); }
    };
  }
  window.revokeCertificate = revokeCertificate;

  async function reissueCertificate(id) {
    if (!confirm('Reissue this certificate? It keeps its original number.')) return;
    try {
      const r = await api('POST', R().certReissue.replace('__ID__', id));
      toast(`Certificate ${r.number} reissued.`, 'good');
      setTimeout(() => location.reload(), 800);
    } catch (err) { toast(err.message, 'bad'); }
  }
  window.reissueCertificate = reissueCertificate;

  /* ── settings ────────────────────────────────────────────────────── */

  async function saveSetting(button, key, input) {
    const el = typeof input === 'string' ? document.getElementById(input) : input;
    if (!el) return;
    button.disabled = true;
    const original = button.textContent;
    button.textContent = 'Saving…';
    try {
      await api('POST', R().settingsSave, { key, value: el.value });
      button.textContent = 'Saved';
      toast('Setting saved.', 'good');
      setTimeout(() => { button.textContent = original; button.disabled = false; }, 1200);
    } catch (err) {
      button.textContent = original; button.disabled = false;
      toast(err.message, 'bad');
    }
  }
  window.saveSetting = saveSetting;

  /* ── export ──────────────────────────────────────────────────────── */

  function exportEnrollments() {
    const q = document.getElementById('enrollSearch')?.value || '';
    const s = document.getElementById('enrollStatus')?.value || '';
    location.href = `${R().export}?q=${encodeURIComponent(q)}&status=${encodeURIComponent(s)}`;
  }
  window.exportEnrollments = exportEnrollments;

  /* ── enrollment search (server-side, debounced) ──────────────────── */

  let searchTimer;
  function filterEnrollments() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
      const q = (document.getElementById('enrollSearch')?.value || '').toLowerCase();
      const s = (document.getElementById('enrollStatus')?.value || '').toLowerCase();
      let shown = 0;
      document.querySelectorAll('#enrollRows tr[data-enrollment]').forEach(r => {
        const text = r.innerText.toLowerCase();
        const status = (r.dataset.status || '').toLowerCase();
        const hit = text.includes(q) && (!s || status === s);
        r.style.display = hit ? '' : 'none';
        if (hit) shown++;
      });
      const empty = document.getElementById('enrollEmpty');
      if (empty) empty.style.display = shown ? 'none' : '';
    }, 120);
  }
  window.filterEnrollments = filterEnrollments;

  document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('enrollSearch')?.addEventListener('input', filterEnrollments);
    document.getElementById('enrollStatus')?.addEventListener('change', filterEnrollments);
  });
})();

/* ── instructor onboarding invites ─────────────────────────────────── */
(function () {
  'use strict';

  window.inviteInstructor = async function () {
    const R = window.PTT.routes;
    const body = `<form id="inviteForm"><div class="fieldgrid">
        <div class="field"><label for="i_name">Name</label>
          <input id="i_name" name="name" placeholder="Optional"><div class="err"></div></div>
        <div class="field"><label for="i_email">Email *</label>
          <input id="i_email" name="email" type="email" placeholder="instructor@example.com"><div class="err"></div></div>
      </div></form>
      <p class="note" style="margin-top:12px">
        They get a single-use link and a 6-digit code by email. The link stops
        working once they submit, and expires after 14 days.</p>
      <div id="inviteResult" style="display:none;margin-top:14px">
        <div class="verifybox"><b>Invitation ready</b><br>
          <span id="inviteExpiry"></span>
          <div style="margin-top:8px"><input id="inviteLink" readonly style="width:100%;border:1px solid var(--line);border-radius:8px;padding:8px;font-size:12px"></div>
          <button class="btn light" type="button" style="margin-top:8px" onclick="
            document.getElementById('inviteLink').select();
            document.execCommand('copy');
            window.pttToast('Link copied.','good');">Copy link</button>
        </div>
      </div>`;

    window.pttOpenModal({
      title: 'Send onboarding invite',
      body,
      actions: `<button class="btn light" type="button" onclick="pttCloseModal()">Close</button>
                <button class="btn gold" type="button" id="doInvite">Send invite</button>`
    });

    document.getElementById('doInvite').onclick = async (ev) => {
      const btn = ev.currentTarget; btn.disabled = true;
      const payload = {
        email: document.getElementById('i_email').value.trim(),
        name:  document.getElementById('i_name').value.trim()
      };
      try {
        const res = await fetch(R.inviteInstructor, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json', 'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
          },
          body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.message || Object.values(data.errors || {}).flat()[0] || 'Could not send it.');
        document.getElementById('inviteResult').style.display = 'block';
        document.getElementById('inviteLink').value = data.link;
        document.getElementById('inviteExpiry').textContent =
          (data.reused ? 'An unused invitation already existed, so that one is being reused. ' : '') +
          'Expires ' + data.expires + '.';
        btn.disabled = false;
        window.pttToast(data.reused ? 'Existing invitation reused.' : 'Invitation sent.', 'good');
      } catch (e) { btn.disabled = false; window.pttToast(e.message, 'bad'); }
    };
  };
})();
