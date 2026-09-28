/* Pete's Operations Dashboard (A1).
   The mockup saved objectives, targets and owner controls in the browser and
   read the Assistant's live data from localStorage. Here everything is on the
   server: objectives and settings are saved through the API and the live
   channel is polled, so every device and screen agrees. */
(function () {
  'use strict';

  const R = window.PTT.routes;
  const $ = id => document.getElementById(id);
  const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const money = v => Number(v || 0).toLocaleString('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 0 });

  async function call(method, url, body) {
    const res = await fetch(url, {
      method,
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        ...(body ? { 'Content-Type': 'application/json' } : {})
      },
      body: body ? JSON.stringify(body) : undefined,
      credentials: 'same-origin'
    });
    const json = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(json.message || Object.values(json.errors || {}).flat()[0] || 'Something went wrong.');
    return json;
  }

  /* ── navigation (design's own behaviour) ─────────────────────────── */
  const titles = {
    overview: 'Command Center', enrollment: 'Enrollment', training: 'Training Operations',
    finance: 'Financial Control', people: 'People & Quality', growth: 'Growth Strategy',
    weekly: 'Weekly Review', progress: 'Progress Tracker', sops: 'SOP Library', targets: 'Owner Settings'
  };
  const sidebar = $('sidebar');
  $('menuButton').addEventListener('click', () => sidebar.classList.toggle('open'));
  document.querySelectorAll('nav button[data-view]').forEach(button => {
    button.addEventListener('click', () => {
      document.querySelectorAll('nav button[data-view]').forEach(item => item.classList.remove('active'));
      document.querySelectorAll('.view').forEach(view => view.classList.remove('active'));
      button.classList.add('active');
      $(button.dataset.view).classList.add('active');
      $('pageTitle').textContent = titles[button.dataset.view];
      sidebar.classList.remove('open');
    });
  });
  document.querySelectorAll('nav button[data-href]').forEach(b => b.addEventListener('click', () => { location.href = b.dataset.href; }));

  /* ── Monthly Seat Target model ───────────────────────────────────── */
  let enrollments = Number(window.PTT.enrolledThisMonth || 0);
  const blended = Number(window.PTT.blendedTuition || 0);
  function updateEnrollment() {
    const tuition = money(enrollments * blended);
    $('enrollmentCount').textContent = enrollments;
    $('enrollmentKpi').textContent = enrollments;
    $('projectedTuition').textContent = tuition;
    $('tuitionKpi').textContent = tuition;
  }
  $('minusEnrollment').addEventListener('click', () => { enrollments = Math.max(0, enrollments - 1); updateEnrollment(); });
  $('plusEnrollment').addEventListener('click', () => { enrollments++; updateEnrollment(); });

  /* ── Readiness Simulator (design's own model) ────────────────────── */
  const utilSlider = $('utilSlider'), managerCheck = $('managerCheck');
  function updateReadiness() {
    const utilization = Number(utilSlider.value);
    const utilPass = utilization >= Number(window.PTT.targetUtil || 80), managerPass = managerCheck.checked;
    const passed = Number(window.PTT.gatesBase || 0) + (utilPass ? 1 : 0) + (managerPass ? 1 : 0);
    $('utilValue').textContent = utilization + '%';
    $('utilDetail').textContent = utilization + '% current';
    $('gateScore').textContent = passed + ' / 6 passed';
    $('readinessValue').textContent = Math.round((passed / 6) * 100) + '%';
    const utilGate = $('utilGate');
    utilGate.classList.toggle('pass', utilPass);
    utilGate.querySelector('b').textContent = utilPass ? '✓' : '✕';
    const managerGate = $('managerGate');
    managerGate.classList.toggle('pass', managerPass);
    managerGate.querySelector('b').textContent = managerPass ? '✓' : '✕';
    managerGate.querySelector('small').textContent = managerPass ? 'Manager verified' : 'Not yet verified';
  }
  utilSlider?.addEventListener('input', updateReadiness);
  managerCheck?.addEventListener('change', updateReadiness);
  if (utilSlider) updateReadiness();

  /* ── live channel: Assistant metrics, alerts, today's missions ───── */
  let liveRevision = null;
  function renderLive(d) {
    liveRevision = d.revision;
    const m = d.metrics || {};
    $('ownerCashMetric').textContent = money(m.cash);
    $('ownerUtilMetric').textContent = Number(m.utilization || 0) + '%';
    $('ownerEnrollmentMetric').textContent = Number(m.enrollments || 0);
    $('ownerCompletionMetric').textContent = Number(m.completion || 0) + '%';
    $('ownerLiveRevision').textContent = 'Revision ' + d.revision;

    const important = (d.updates || []).filter(u => u.type === 'priority' || u.type === 'alert').slice(0, 3);
    $('ownerLiveUpdates').innerHTML = important.length
      ? important.map(u => '<div class="owner-live alert critical ' + esc(u.type) + '"><div class="alert-icon">!</div><div><strong>' + esc(u.message) + '</strong><small>' + esc(u.department) + ' · ' + esc(new Date(u.createdAt).toLocaleString([], { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' })) + '</small></div></div>').join('')
      : '<div class="alert"><div class="alert-icon">✓</div><div><strong>No active Priority or Alert updates</strong><small>Assistant operations are currently clear.</small></div></div>';

    // Daily Execution: the Mission Focus tasks due today.
    const todayKey = new Date().toDateString();
    const due = (d.tasks || []).filter(t => new Date(t.deadline).toDateString() === todayKey);
    const done = due.filter(t => t.status === 'complete').length;
    $('taskScore').textContent = done + ' / ' + due.length + ' done';
    $('dailyTasks').innerHTML = due.length ? due.map(t =>
      '<label class="task' + (t.status === 'complete' ? ' done' : '') + '"><input type="checkbox" data-id="' + t.id + '"' +
      (t.status === 'complete' ? ' checked disabled' : t.status === 'noise' ? ' disabled' : '') + '><span>' + esc(t.title) +
      (t.status === 'noise' ? ' — Noise' : '') + '</span></label>'
    ).join('') : '<p class="deadline-note">No Mission Focus tasks are due today.</p>';
  }
  async function refreshLive() {
    try {
      const d = await call('GET', R.live);
      if (d.revision !== liveRevision) renderLive(d);
    } catch (e) { /* keep the last view; retry on the next poll */ }
  }
  $('dailyTasks').addEventListener('change', async ev => {
    const box = ev.target.closest('input[data-id]');
    if (!box || !box.checked) return;
    box.disabled = true;
    try { renderLive(await call('PATCH', R.liveMission.replace('__ID__', box.dataset.id), { action: 'complete' })); }
    catch (e) { alert(e.message); box.checked = false; box.disabled = false; }
  });

  /* ── Progress Tracker ────────────────────────────────────────────── */
  let objectives = [];
  let selectedPeriod = 'day';
  const today = () => { const t = new Date(); t.setHours(0, 0, 0, 0); return t; };
  const dateKey = d => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
  const localDateTimeValue = d => dateKey(d) + 'T' + String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
  const toIso = local => new Date(local).toISOString();

  function startOfWeek(date) { const r = new Date(date); const day = r.getDay() || 7; r.setDate(r.getDate() - day + 1); r.setHours(0, 0, 0, 0); return r; }
  function addDays(date, days) { const r = new Date(date); r.setDate(r.getDate() + days); return r; }
  function rangeFor(period, previous) {
    const t = today();
    let start, end, label, comparison;
    if (period === 'day') {
      start = addDays(t, previous ? -1 : 0); end = addDays(start, 1);
      label = previous ? 'Yesterday' : 'Today'; comparison = 'Today compared with yesterday';
    } else if (period === 'week') {
      start = addDays(startOfWeek(t), previous ? -7 : 0); end = addDays(start, 7);
      label = previous ? 'Previous week' : 'Current week'; comparison = 'Current week compared with previous week';
    } else if (period === 'month') {
      start = new Date(t.getFullYear(), t.getMonth() + (previous ? -1 : 0), 1); end = new Date(start.getFullYear(), start.getMonth() + 1, 1);
      label = start.toLocaleDateString('en-US', { month: 'long', year: 'numeric' }); comparison = 'Current month compared with previous month';
    } else if (period.startsWith('q')) {
      const quarter = Number(period.slice(1)), year = t.getFullYear() - (previous ? 1 : 0);
      start = new Date(year, (quarter - 1) * 3, 1); end = new Date(year, quarter * 3, 1);
      label = 'Q' + quarter + ' ' + year; comparison = 'Q' + quarter + ' ' + t.getFullYear() + ' compared with Q' + quarter + ' ' + (t.getFullYear() - 1);
    } else {
      const year = t.getFullYear() - (previous ? 1 : 0);
      start = new Date(year, 0, 1); end = new Date(year + 1, 0, 1);
      label = String(year); comparison = 'Current year compared with previous year';
    }
    return { start, end, label, comparison };
  }
  const inRange = r => objectives.filter(o => { const d = new Date(o.deadline); return d >= r.start && d < r.end; });
  function summarize(items) {
    const complete = items.filter(i => i.status === 'complete').length;
    const missed = items.filter(i => i.status === 'missed').length;
    const open = items.filter(i => i.status === 'open').length;
    const decided = complete + missed;
    const score = decided ? Math.round((complete / decided) * 100) : 0;
    const noiseScore = decided ? 100 - score : 0;
    return { complete, missed, open, total: items.length, decided, score, noiseScore, advantage: score - noiseScore, net: complete - missed };
  }
  function comparisonText(current, previous, type) {
    const diff = current - previous, better = type === 'noise' ? diff < 0 : diff > 0, neutral = diff === 0;
    return { text: neutral ? 'No change vs. prior' : (diff > 0 ? '▲ +' : '▼ ') + diff + ' vs. prior', className: neutral ? 'comparison' : 'comparison ' + (better ? 'better' : 'worse') };
  }
  function flashSaved(text) {
    const s = $('savedStatus'); s.textContent = text || 'Saved';
    setTimeout(() => { s.textContent = 'Saved'; }, 1200);
  }
  // An open objective whose deadline has passed is Noise — shown immediately,
  // and the server settles it the same way on its next read.
  function expireLocally() {
    const now = new Date(); let changed = false;
    objectives = objectives.map(o => (o.status === 'open' && new Date(o.deadline) <= now) ? (changed = true, { ...o, status: 'missed' }) : o);
    return changed;
  }

  function renderObjectiveList(items) {
    const list = $('objectiveList');
    if (!items.length) { list.innerHTML = '<div class="empty-state">No objectives recorded for this period.</div>'; return; }
    const sorted = [...items].sort((a, b) => b.deadline.localeCompare(a.deadline) || b.id - a.id);
    list.innerHTML = sorted.map(item => {
      const deadline = new Date(item.deadline).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
      const completeButton = item.status === 'open' ? '<button class="focus" data-action="complete" data-id="' + item.id + '">Completed</button>' : '';
      const reopenLabel = item.status === 'open' ? 'Reschedule' : 'Reopen';
      return '<div class="objective-row ' + item.status + '">' +
        '<div><strong>' + esc(item.title) + '</strong><small>Due ' + deadline + ' · ' + esc(item.category) + '</small></div>' +
        '<span class="status-pill ' + item.status + '">' + (item.status === 'complete' ? 'Mission Focus' : item.status === 'missed' ? 'Noise' : 'Planned') + '</span>' +
        '<div class="objective-actions">' + completeButton + '<button data-action="reopen" data-id="' + item.id + '">' + reopenLabel + '</button><button data-action="delete" data-id="' + item.id + '">Delete</button></div>' +
      '</div>';
    }).join('');
  }

  function renderTrend() {
    const days = [];
    for (let offset = -6; offset <= 0; offset++) {
      const date = addDays(today(), offset);
      days.push({ date, ...summarize(objectives.filter(o => dateKey(new Date(o.deadline)) === dateKey(date))) });
    }
    const maxValue = Math.max(1, ...days.flatMap(d => [d.complete, d.missed]));
    $('trendChart').innerHTML = days.map(d => '<div class="trend-day"><div class="trend-bars"><i style="height:' + Math.max(3, (d.complete / maxValue) * 100) + '%"></i><i class="noise" style="height:' + Math.max(3, (d.missed / maxValue) * 100) + '%"></i></div><small>' + d.date.toLocaleDateString('en-US', { weekday: 'short' }).slice(0, 2) + '</small></div>').join('');
  }

  function renderProgress() {
    expireLocally();
    const cr = rangeFor(selectedPeriod, false), pr = rangeFor(selectedPeriod, true);
    const items = inRange(cr), current = summarize(items), previous = summarize(inRange(pr));
    $('periodLabel').textContent = cr.label;
    $('comparisonLabel').textContent = cr.comparison;
    $('focusCount').textContent = current.score + '%';
    $('noiseCount').textContent = current.noiseScore + '%';
    $('focusRaw').textContent = current.complete + ' Mission task' + (current.complete === 1 ? '' : 's') + ' completed';
    $('noiseRaw').textContent = current.missed + ' Mission task' + (current.missed === 1 ? '' : 's') + ' missed';
    $('executionScore').textContent = (current.advantage > 0 ? '+' : '') + current.advantage + ' pts';
    $('openCount').textContent = current.open;
    $('totalObjectives').textContent = current.total + ' mission tasks in period';
    [['focusCompare', comparisonText(current.score, previous.score, 'focus')],
     ['noiseCompare', comparisonText(current.noiseScore, previous.noiseScore, 'noise')],
     ['scoreCompare', comparisonText(current.advantage, previous.advantage, 'score')]].forEach(([id, c]) => {
      $(id).textContent = c.text.replace(' vs. prior', ' pts vs. prior'); $(id).className = c.className;
    });
    $('currentFocus').textContent = current.score + '% (' + current.complete + ')';
    $('previousFocus').textContent = previous.score + '% (' + previous.complete + ') prior';
    $('currentNoise').textContent = current.noiseScore + '% (' + current.missed + ')';
    $('previousNoise').textContent = previous.noiseScore + '% (' + previous.missed + ') prior';
    $('currentScore').textContent = current.score + '%';
    $('previousScore').textContent = previous.score + '% prior';
    $('currentNet').textContent = current.net;
    $('previousNet').textContent = previous.net + ' prior';
    $('focusPercentLabel').textContent = current.score + '%';
    $('noisePercentLabel').textContent = current.noiseScore + '%';
    $('focusFill').style.width = current.decided ? current.score + '%' : '0%';
    $('noiseFill').style.width = current.decided ? current.noiseScore + '%' : '0%';
    $('tugKnot').style.left = current.decided ? current.score + '%' : '50%';
    const tug = $('tugResult');
    if (!current.decided) tug.textContent = 'No decided objectives';
    else if (current.advantage > 0) tug.textContent = 'Mission Focus leads by ' + current.advantage + ' points';
    else if (current.advantage < 0) tug.textContent = 'Noise leads by ' + Math.abs(current.advantage) + ' points';
    else tug.textContent = 'Even contest — 50% / 50%';
    const signal = $('performanceSignal');
    if (!current.total) signal.textContent = 'No Mission Focus tasks recorded in this period';
    else if (current.score >= 85 && current.missed <= previous.missed) signal.textContent = 'Focused execution — maintain the system';
    else if (current.score >= previous.score) signal.textContent = 'Improving — protect the actions creating progress';
    else signal.textContent = 'Noise is increasing — reduce commitments or remove blockers';
    renderObjectiveList(items);
    renderTrend();
  }

  async function loadObjectives() {
    try { objectives = await call('GET', R.objectives); renderProgress(); } catch (e) { /* retry later */ }
  }

  $('periodTabs').querySelectorAll('button').forEach(button => button.addEventListener('click', () => {
    $('periodTabs').querySelectorAll('button').forEach(i => i.classList.remove('active'));
    button.classList.add('active');
    selectedPeriod = button.dataset.period;
    renderProgress();
  }));

  function suggestedFutureDeadline() { const d = new Date(); d.setSeconds(0, 0); d.setMinutes(0); d.setHours(d.getHours() + 2); return d; }
  const deadlineInput = $('objectiveDeadline');
  const minNow = () => { const d = new Date(); d.setMinutes(d.getMinutes() + 1); return localDateTimeValue(d); };
  deadlineInput.min = minNow();
  deadlineInput.value = localDateTimeValue(suggestedFutureDeadline());
  deadlineInput.addEventListener('input', () => deadlineInput.setCustomValidity(''));

  $('objectiveForm').addEventListener('submit', async event => {
    event.preventDefault();
    const title = $('objectiveTitle').value.trim();
    if (!title) return;
    const deadline = deadlineInput.value;
    if (!deadline || new Date(deadline) <= new Date()) {
      deadlineInput.setCustomValidity('Choose a completion date and time in the future.');
      deadlineInput.reportValidity();
      return;
    }
    try {
      objectives.push(await call('POST', R.objectives, { title, deadline: toIso(deadline), category: $('objectiveCategory').value }));
      $('objectiveTitle').value = '';
      deadlineInput.value = localDateTimeValue(suggestedFutureDeadline());
      flashSaved('Saved');
      renderProgress();
    } catch (e) { alert(e.message); }
  });

  $('objectiveList').addEventListener('click', async ev => {
    const b = ev.target.closest('button[data-action]');
    if (!b) return;
    const id = Number(b.dataset.id), url = R.objective.replace('__ID__', id);
    try {
      if (b.dataset.action === 'delete') {
        if (!confirm('Delete this objective?')) return;
        await call('DELETE', url);
        objectives = objectives.filter(o => o.id !== id);
      } else if (b.dataset.action === 'reopen') {
        openReopenDialog(id); return;
      } else {
        const updated = await call('PATCH', url, { action: 'complete' });
        objectives = objectives.map(o => o.id === id ? updated : o);
      }
      flashSaved('Saved');
      renderProgress();
    } catch (e) { alert(e.message); loadObjectives(); }
  });

  let reopenId = null;
  function openReopenDialog(id) {
    reopenId = id;
    const input = $('reopenDeadline');
    input.min = minNow();
    input.value = localDateTimeValue(suggestedFutureDeadline());
    $('reopenError').textContent = '';
    $('reopenModal').hidden = false;
    input.focus();
  }
  function closeReopenDialog() { reopenId = null; $('reopenModal').hidden = true; $('reopenError').textContent = ''; }
  $('cancelReopen').addEventListener('click', closeReopenDialog);
  $('reopenForm').addEventListener('submit', async event => {
    event.preventDefault();
    const deadline = $('reopenDeadline').value;
    if (!deadline || new Date(deadline) <= new Date()) { $('reopenError').textContent = 'Choose a completion date and time in the future.'; return; }
    try {
      const updated = await call('PATCH', R.objective.replace('__ID__', reopenId), { action: 'reopen', deadline: toIso(deadline) });
      objectives = objectives.map(o => o.id === updated.id ? updated : o);
      closeReopenDialog(); flashSaved('Saved'); renderProgress();
    } catch (e) { $('reopenError').textContent = e.message; }
  });

  /* ── Owner Settings ──────────────────────────────────────────────── */
  function flashTarget(text) {
    const b = $('targetSaved'); b.textContent = text;
    setTimeout(() => { b.textContent = 'Owner only'; }, 1600);
  }
  $('targetForm').addEventListener('submit', async event => {
    event.preventDefault();
    try {
      await call('POST', R.targets, {
        target_enrollments: +$('targetEnrollments').value, target_utilization: +$('targetUtilization').value,
        target_margin: +$('targetMargin').value, target_reserve: +$('targetReserve').value,
        target_leads: +$('targetLeads').value, target_noise: +$('targetNoise').value
      });
      flashTarget('Targets saved');
    } catch (e) { alert(e.message); }
  });
  $('ownerControlForm').addEventListener('submit', async event => {
    event.preventDefault();
    const keys = { stripe_key: $('ownerStripeKey').value.trim(), stripe_secret: $('ownerStripeSecret').value.trim(), stripe_webhook_secret: $('ownerStripeWebhook').value.trim() };
    try {
      const res = await call('POST', R.controls, {
        seats_per_instructor: +$('ownerSeatsPerInstructor').value, deposit_percent: +$('ownerDepositPercent').value,
        seat_hold_minutes: +$('ownerSeatHold').value, payment_merchant: $('ownerMerchant').value,
        payment_mode: $('ownerPaymentMode').value,
        ...Object.fromEntries(Object.entries(keys).filter(([, v]) => v))
      });
      // Clear the typed keys; show only their masked form from now on.
      [['ownerStripeKey', 'stripe_key'], ['ownerStripeSecret', 'stripe_secret'], ['ownerStripeWebhook', 'stripe_webhook_secret']].forEach(([id, k]) => {
        const input = $(id); input.value = '';
        if (res.controls[k]) { input.placeholder = res.controls[k]; input.nextElementSibling.textContent = k === 'stripe_key' ? 'Saved · leave blank to keep' : 'Saved encrypted · leave blank to keep'; }
      });
      flashTarget('Owner controls saved');
    } catch (e) { alert(e.message); }
  });

  /* ── start ───────────────────────────────────────────────────────── */
  refreshLive();
  loadObjectives();
  setInterval(() => { if (!document.hidden) refreshLive(); }, 5000);
  setInterval(() => { if (expireLocally()) renderProgress(); }, 15000);
  document.addEventListener('visibilitychange', () => { if (!document.hidden) { refreshLive(); loadObjectives(); } });
})();

/* ── Owner-entered figures (A1 part 2) ─────────────────────────────── */
(function () {
  'use strict';

  const $ = id => document.getElementById(id);
  const R = window.PTT.routes;
  const F = window.PTT.figures || {};
  const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const monthKey = (() => { const d = new Date(); return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0'); })();

  // Each form: plain fields, or a list of rows the owner can add to and remove.
  const forms = {
    equipment: { title: 'Equipment Status', help: 'Training stations and how many are online today.',
      list: 'equipment', cols: [['name', 'Station', 'text', '2fr'], ['online', 'Online', 'number', '1fr'], ['total', 'Total', 'number', '1fr']],
      blank: { name: '', online: 0, total: 1 } },
    quality: { title: 'Quality Controls', help: 'Delivery measures for the current period.',
      fields: [['modules_done', 'Modules completed', 'number'], ['modules_total', 'Modules planned', 'number'],
               ['assessments', 'Skills assessments passed (%)', 'number'], ['safety', 'Safety compliance (%)', 'number']],
      values: () => F.quality || {} },
    costs: { title: 'Operating Costs', help: 'This month\'s costs. Gross margin, cash reserve and cost per lead are calculated from these (name a line "Advertising" for cost per lead).',
      list: 'costs', cols: [['name', 'Cost', 'text', '2fr'], ['amount', 'Amount ($)', 'number', '1fr']],
      rows: () => (F.costs || {})[monthKey] || [], blank: { name: '', amount: 0 } },
    finance: { title: 'Finance Inputs', help: 'Cash reserve and revenue the system does not collect itself.',
      fields: [['reserve_cash', 'Unrestricted cash reserve ($)', 'number'], ['profitable_months', 'Profitable months in a row', 'number'],
               ['specialty', 'Specialty training revenue this month ($)', 'number'], ['b2b', 'B2B training revenue this month ($)', 'number']],
      values: () => ({ reserve_cash: F.reserve_cash, profitable_months: F.profitable_months, specialty: (F.engines || {}).specialty, b2b: (F.engines || {}).b2b }) },
    satisfaction: { title: 'Student Satisfaction', help: 'Latest survey result.',
      fields: [['score', 'Average score (out of 5)', 'number'], ['responses', 'Responses', 'number']], values: () => F.satisfaction || {} },
    safety: { title: 'Safety Streak', help: 'The streak counts days since the last incident.',
      fields: [['last_incident', 'Last incident date', 'date'], ['incidents', 'Incidents this year', 'number']], values: () => F.safety || {} },
    dependency: { title: 'Owner Dependency', help: 'Functions only the owner handles today, and which have been handed off.',
      list: 'dependency', cols: [['name', 'Function', 'text', '2fr'], ['delegated', 'Status', 'delegated', '1fr']],
      blank: { name: '', delegated: false } },
    growth: { title: 'Growth Phase', help: 'Where the business is on the five-year plan.',
      fields: [['phase', 'Current phase', 'phase'], ['pct', 'Phase complete (%)', 'number'],
               ['manager_verified', 'A location manager can run five days without the owner', 'check']],
      values: () => F.growth || {} },
    weekly: { title: 'Weekly Review', help: 'One decision per area, then this week\'s wins and next week\'s commitments (one per line).',
      fields: [['sales', 'Sales & Enrollment decision', 'text'], ['ops', 'Operations & Quality decision', 'text'], ['finance', 'Finance & Capacity decision', 'text'],
               ['wins', 'Wins to repeat', 'lines'], ['next', 'Next seven days', 'lines']],
      values: () => F.weekly || {} },
    sops: { title: 'SOP Library', help: 'Add, edit or remove procedures.',
      list: 'sops', cols: [['title', 'Procedure', 'text', '2.2fr'], ['code', 'Code', 'text', '.9fr'], ['area', 'Area', 'text', '1fr'], ['pct', '%', 'number', '.6fr'], ['status', 'Status', 'sopstatus', '1.2fr']],
      blank: { title: '', code: '', area: '', pct: 0, status: 'Draft' } }
  };

  function input(type, name, value, label) {
    if (type === 'delegated') return `<select name="${name}"><option value="0"${value ? '' : ' selected'}>Founder only</option><option value="1"${value ? ' selected' : ''}>Delegated</option></select>`;
    if (type === 'sopstatus') return `<select name="${name}">${(window.PTT.sopStatuses || []).map(s => `<option${s === value ? ' selected' : ''}>${esc(s)}</option>`).join('')}</select>`;
    if (type === 'phase') return `<select name="${name}">${[1, 2, 3, 4, 5].map(n => `<option value="${n}"${Number(value) === n ? ' selected' : ''}>Year ${n}</option>`).join('')}</select>`;
    if (type === 'lines') return `<textarea name="${name}">${esc((value || []).join('\n'))}</textarea>`;
    return `<input name="${name}" type="${type}" ${type === 'number' ? 'step="any" min="0"' : ''} value="${esc(value ?? '')}"${label ? ` placeholder="${esc(label)}" aria-label="${esc(label)}"` : ''}>`;
  }

  let current = null;
  function rowHtml(def, row) {
    const tpl = def.cols.map(c => c[3]).join(' ') + ' auto';
    return `<div class="fig-row" style="grid-template-columns:${tpl}">` +
      def.cols.map(c => input(c[2], c[0], row[c[0]], c[1])).join('') + '<button type="button" data-remove>Remove</button></div>';
  }

  function open(group) {
    const def = forms[group]; if (!def) return;
    current = group;
    $('figTitle').textContent = def.title;
    $('figHelp').textContent = def.help;
    $('figError').textContent = '';
    if (def.list) {
      const rows = def.rows ? def.rows() : (F[def.list] || []);
      const tpl = def.cols.map(c => c[3]).join(' ') + ' auto';
      $('figFields').innerHTML = `<div class="fig-head" style="grid-template-columns:${tpl}">${def.cols.map(c => `<span>${esc(c[1])}</span>`).join('')}<span></span></div>` +
        `<div class="fig-rows" id="figRows">${rows.map(r => rowHtml(def, r)).join('')}</div><button type="button" class="fig-add" id="figAdd">+ Add</button>`;
      $('figAdd').onclick = () => { $('figRows').insertAdjacentHTML('beforeend', rowHtml(def, def.blank)); };
      if (group === 'sops' && !rows.length) $('figAdd').click();
    } else {
      const v = def.values();
      $('figFields').innerHTML = def.fields.map(([k, label, type]) => type === 'check'
        ? `<label class="fig-check fig-field"><input type="checkbox" name="${k}"${v[k] ? ' checked' : ''}> ${esc(label)}</label>`
        : `<div class="fig-field"><label>${esc(label)}</label>${input(type, k, v[k])}</div>`).join('');
    }
    $('figModal').hidden = false;
  }

  function collect() {
    const def = forms[current];
    const num = (t, v) => (t === 'number' ? (v === '' ? null : Number(v)) : v);
    if (def.list) {
      return { [def.list]: [...document.querySelectorAll('#figRows .fig-row')].map(row => {
        const o = {};
        def.cols.forEach(([k, , t]) => {
          const el = row.querySelector(`[name="${k}"]`);
          o[k] = t === 'delegated' ? el.value === '1' : num(t, el.value.trim());
        });
        return o;
      }) };
    }
    const o = {};
    def.fields.forEach(([k, , t]) => {
      const el = $('figFields').querySelector(`[name="${k}"]`);
      o[k] = t === 'check' ? el.checked : t === 'lines' ? el.value.split('\n').map(x => x.trim()).filter(Boolean)
           : t === 'phase' ? Number(el.value) : num(t, el.value.trim());
    });
    return o;
  }

  document.addEventListener('click', e => {
    const b = e.target.closest('[data-edit]');
    if (b) { e.preventDefault(); open(b.dataset.edit); return; }
    const rm = e.target.closest('[data-remove]');
    if (rm) rm.closest('.fig-row').remove();
  });
  $('figCancel').addEventListener('click', () => { $('figModal').hidden = true; });
  $('figForm').addEventListener('submit', async ev => {
    ev.preventDefault();
    $('figError').textContent = '';
    try {
      const res = await fetch(R.figures.replace('__GROUP__', current), {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json',
                   'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
        body: JSON.stringify(collect()), credentials: 'same-origin'
      });
      const json = await res.json().catch(() => ({}));
      if (!res.ok) throw new Error(json.message || 'Could not save.');
      // Figures feed several cards; reload onto the same view to show them all.
      sessionStorage.setItem('pttOwnerView', document.querySelector('nav button[data-view].active')?.dataset.view || 'overview');
      location.reload();
    } catch (err) { $('figError').textContent = err.message; }
  });

  // Return to the view that was being edited.
  try {
    const v = sessionStorage.getItem('pttOwnerView');
    if (v) { sessionStorage.removeItem('pttOwnerView'); document.querySelector(`nav button[data-view="${v}"]`)?.click(); }
  } catch (e) { /* storage unavailable: stay on the Command Center */ }
})();
