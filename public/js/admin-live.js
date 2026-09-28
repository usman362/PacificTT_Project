/* Realtime Command — the Assistant's live business channel.
   The mockup kept this in one browser's localStorage; here every change goes
   to the server and all screens (Assistant, Owner, Staff) read the same state,
   refreshing every few seconds. */
(function () {
  'use strict';

  const R = window.PTT.routes;
  const $ = id => document.getElementById(id);
  if (!$('command')) return;

  let data = null;
  let lastRevision = null;

  const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const money = n => Number(n || 0).toLocaleString('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 0 });
  const toast = (m, k) => (window.pttToast ? window.pttToast(m, k) : alert(m));

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

  // <input type="datetime-local"> has no time zone; send the moment it means
  // in the browser's own zone so the server stores the right instant.
  const toIso = local => (local ? new Date(local).toISOString() : '');
  const toLocalInput = iso => {
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return '';
    const p = n => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())}T${p(d.getHours())}:${p(d.getMinutes())}`;
  };

  function score(tasks) {
    const complete = tasks.filter(t => t.status === 'complete').length;
    const noise = tasks.filter(t => t.status === 'noise').length;
    const decided = complete + noise;
    return {
      complete, noise,
      focusPct: decided ? Math.round(complete / decided * 100) : 0,
      noisePct: decided ? Math.round(noise / decided * 100) : 0
    };
  }

  function populateOwners(owners) {
    const select = $('cmdTaskOwner');
    const current = select.value;
    if (!owners.length) {
      select.innerHTML = '<option value="">No active registered users</option>';
      select.disabled = true;
      return;
    }
    select.disabled = false;
    select.innerHTML = '<option value="">Select registered owner</option>' +
      owners.map(u => `<option value="${esc(u.id)}">${esc(u.name)}${u.role ? ' — ' + esc(u.role) : ''}</option>`).join('');
    if (owners.some(u => u.id === current)) select.value = current;
  }

  function render(d, fillInputs) {
    data = d;
    const m = d.metrics || {};
    const s = score(d.tasks || []);

    $('cmdCash').textContent = money(m.cash);
    $('cmdEnroll').textContent = m.enrollments || 0;
    $('cmdUtil').textContent = (m.utilization || 0) + '%';
    $('cmdAttend').textContent = (m.attendance || 0) + '%';
    $('cmdFocus').textContent = s.focusPct + '%';
    $('cmdFocusNote').textContent = (s.complete + s.noise) ? `${s.complete} focus / ${s.noise} noise` : 'No decided missions';
    $('cmdRevision').textContent = 'Revision ' + (d.revision || 1);

    // Only refill the metric inputs on first load or after publishing, so a
    // background refresh never wipes numbers someone is typing.
    if (fillInputs) {
      $('cmdCashInput').value = m.cash || 0;
      $('cmdEnrollInput').value = m.enrollments || 0;
      $('cmdUtilInput').value = m.utilization || 0;
      $('cmdAttendInput').value = m.attendance || 0;
      $('cmdCompletionInput').value = m.completion || 0;
    }

    const tasks = d.tasks || [];
    $('cmdMissionCount').textContent = tasks.length + ' MISSIONS';
    $('cmdFocusFill').style.width = s.focusPct + '%';
    $('cmdFocusLabel').textContent = 'MISSION FOCUS ' + s.focusPct + '%';
    $('cmdNoiseLabel').textContent = 'NOISE ' + s.noisePct + '%';

    $('cmdTaskList').innerHTML = tasks.length ? tasks.map(t =>
      `<div class="cmd-item"><div><b>${esc(t.title)}</b><small>${esc(t.owner)} · ${esc(t.department)} · ${esc(new Date(t.deadline).toLocaleString())}` +
      `${t.status === 'noise' ? ' · <span class="pill missing">NOISE</span>' : t.status === 'complete' ? ' · <span class="pill paid">MISSION FOCUS</span>' : ''}</small></div>` +
      `<div class="cmd-item-actions">${t.status === 'open' ? `<button data-cmd="complete" data-id="${t.id}">Complete</button>` : ''}` +
      `<button data-cmd="reschedule" data-id="${t.id}">Reschedule</button><button data-cmd="delete" data-id="${t.id}">Delete</button></div></div>`
    ).join('') : '<div class="note">No Mission Focus tasks assigned.</div>';

    const feed = d.updates || [];
    $('cmdFeedCount').textContent = feed.length + ' UPDATES';
    $('cmdFeed').innerHTML = feed.length ? feed.map(u =>
      `<div class="cmd-feeditem ${esc(u.type)}"><div><b>${esc(u.department)} · ${esc(String(u.type).toUpperCase())}</b>` +
      `<small>${esc(u.message)}</small></div><small>${esc(new Date(u.createdAt).toLocaleString())}</small></div>`
    ).join('') : '<div class="note">No realtime updates published.</div>';

    populateOwners(d.owners || []);
    lastRevision = d.revision;
  }

  async function refresh(fillInputs) {
    try {
      const d = await call('GET', R.live);
      if (fillInputs || d.revision !== lastRevision) render(d, fillInputs);
    } catch (e) { /* keep the last good view; the next poll retries */ }
  }

  /* ── actions ─────────────────────────────────────────────────────── */
  $('cmdPublishMetrics').addEventListener('click', async ev => {
    const btn = ev.currentTarget; btn.disabled = true;
    try {
      render(await call('POST', R.liveMetrics, {
        cash: +$('cmdCashInput').value || 0,
        enrollments: +$('cmdEnrollInput').value || 0,
        utilization: Math.max(0, Math.min(100, +$('cmdUtilInput').value || 0)),
        attendance: Math.max(0, Math.min(100, +$('cmdAttendInput').value || 0)),
        completion: Math.max(0, Math.min(100, +$('cmdCompletionInput').value || 0))
      }), true);
      toast('Metrics published.', 'good');
    } catch (e) { toast(e.message, 'bad'); } finally { btn.disabled = false; }
  });

  $('cmdPushUpdate').addEventListener('click', async ev => {
    const message = $('cmdUpdateMessage').value.trim();
    if (!message) { toast('Enter an update first.', 'bad'); return; }
    const btn = ev.currentTarget; btn.disabled = true;
    try {
      render(await call('POST', R.liveUpdates, {
        message, type: $('cmdUpdateType').value, department: $('cmdUpdateDepartment').value
      }));
      $('cmdUpdateMessage').value = '';
      toast('Update pushed.', 'good');
    } catch (e) { toast(e.message, 'bad'); } finally { btn.disabled = false; }
  });

  $('cmdAssignTask').addEventListener('click', async ev => {
    const title = $('cmdTaskTitle').value.trim(), owner = $('cmdTaskOwner').value, deadline = $('cmdTaskDeadline').value;
    if (!title || !owner || !deadline) { toast('Task, registered owner and deadline are required.', 'bad'); return; }
    const btn = ev.currentTarget; btn.disabled = true;
    try {
      render(await call('POST', R.liveMissions, {
        title, owner, department: $('cmdTaskDepartment').value, deadline: toIso(deadline)
      }));
      $('cmdTaskTitle').value = ''; $('cmdTaskOwner').value = ''; $('cmdTaskDeadline').value = '';
      toast('Mission assigned.', 'good');
    } catch (e) { toast(e.message, 'bad'); } finally { btn.disabled = false; }
  });

  $('cmdTaskList').addEventListener('click', async ev => {
    const b = ev.target.closest('button[data-cmd]');
    if (!b) return;
    const id = Number(b.dataset.id);
    const task = (data?.tasks || []).find(t => t.id === id);
    if (!task) return;
    const url = R.liveMission.replace('__ID__', id);
    try {
      if (b.dataset.cmd === 'complete') {
        render(await call('PATCH', url, { action: 'complete' }));
      } else if (b.dataset.cmd === 'delete') {
        if (!confirm('Delete this mission?')) return;
        render(await call('DELETE', url));
      } else if (b.dataset.cmd === 'reschedule') {
        openRescheduleModal(task);
      }
    } catch (e) { toast(e.message, 'bad'); refresh(); }
  });

  /* ── reschedule dialog (the design's own modal) ──────────────────── */
  let rescheduleId = null;
  window.openRescheduleModal = function (task) {
    rescheduleId = task.id;
    $('rescheduleTaskName').textContent = task.title || 'Mission Focus Task';
    $('rescheduleCurrent').value = toLocalInput(task.deadline);
    $('rescheduleNew').value = toLocalInput(task.deadline);
    $('rescheduleModal').classList.add('open');
    $('rescheduleModal').setAttribute('aria-hidden', 'false');
    setTimeout(() => $('rescheduleNew').focus(), 80);
  };
  window.closeRescheduleModal = function () {
    $('rescheduleModal').classList.remove('open');
    $('rescheduleModal').setAttribute('aria-hidden', 'true');
    rescheduleId = null;
  };
  window.saveReschedule = async function () {
    const v = $('rescheduleNew').value;
    if (!v) { $('rescheduleNew').focus(); return; }
    try {
      render(await call('PATCH', R.liveMission.replace('__ID__', rescheduleId), { action: 'reschedule', deadline: toIso(v) }));
      window.closeRescheduleModal();
      toast('Mission rescheduled.', 'good');
    } catch (e) { toast(e.message, 'bad'); }
  };
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && $('rescheduleModal').classList.contains('open')) window.closeRescheduleModal();
  });
  $('rescheduleModal').addEventListener('click', e => { if (e.target.id === 'rescheduleModal') window.closeRescheduleModal(); });

  /* ── "Open Realtime Command" and the workspace shortcuts ─────────── */
  document.querySelectorAll('[data-jump]').forEach(b => b.addEventListener('click', () => {
    document.querySelector(`#nav button[data-page="${b.dataset.jump}"]`)?.click();
  }));

  refresh(true);
  // Poll while the tab is visible; catch up as soon as it is shown again.
  setInterval(() => { if (!document.hidden) refresh(); }, 5000);
  document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
})();
