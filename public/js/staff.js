/* General Staff Progress (S1) — read-only office display.
   The mockup read the Assistant's data from one browser's localStorage; here
   it polls the server's live channel, so any screen shows the same state. */
(function () {
  'use strict';

  const $ = id => document.getElementById(id);
  const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  let data = null;

  function score() {
    const t = data.tasks || [];
    const complete = t.filter(x => x.status === 'complete').length, noise = t.filter(x => x.status === 'noise').length;
    const decided = complete + noise, focusPct = decided ? Math.round(complete / decided * 100) : 0;
    return { complete, noise, focusPct, noisePct: decided ? 100 - focusPct : 0 };
  }

  function missionMarkup(task) {
    const due = new Date(task.deadline), urgent = task.status === 'open' && due - Date.now() < 2 * 3600000;
    const status = task.status === 'complete' ? 'Mission Focus' : task.status === 'noise' ? 'Noise' : 'Planned';
    return '<article class="mission ' + (urgent ? 'urgent' : '') + '"><div><strong>' + esc(task.title) + '</strong><small>' +
      esc(task.owner) + ' · ' + esc(task.department) + ' · ' + status + '</small></div><time class="' + (urgent ? 'urgent' : '') + '">' +
      esc(due.toLocaleString([], { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' })) + '</time></article>';
  }

  function renderTodaySchedule() {
    const instructors = (data.schedule && data.schedule.instructors) || [];
    $('scheduleCount').textContent = instructors.length + ' instructor' + (instructors.length === 1 ? '' : 's');
    if (!instructors.length) {
      $('instructorSchedule').innerHTML = '<div class="empty">No instructor sessions scheduled today.</div>';
      return;
    }
    $('instructorSchedule').innerHTML = instructors.map(inst => {
      const sessions = inst.sessions || [];
      const total = sessions.reduce((n, x) => n + Number(x.booked || 0), 0);
      return '<article class="sched-instructor"><div class="sched-instructor-head"><strong>' + esc(inst.name) + '</strong><span>' + total +
        ' students today</span></div><div class="sched-sessions">' + sessions.map(x =>
          '<div class="sched-session"><b>' + esc(x.time) + '</b><small>' + esc(x.program || 'Training') + '</small><em>' +
          Number(x.booked || 0) + ' <span>/ ' + Number(x.capacity || 8) + ' students</span></em></div>').join('') + '</div></article>';
    }).join('');
  }

  function render() {
    const s = score(), m = data.metrics || {};
    renderTodaySchedule();
    $('enrollments').textContent = Number(m.enrollments || 0);
    $('utilization').textContent = Number(m.utilization || 0) + '%';
    $('attendance').textContent = Number(m.attendance || 0) + '%';
    $('completion').textContent = Number(m.completion || 0) + '%';
    $('missionScore').textContent = s.focusPct + '%';
    $('missionDetail').textContent = (s.complete || s.noise) ? s.complete + ' completed · ' + s.noise + ' Noise' : 'No decided missions';
    $('focusLabel').textContent = 'MISSION FOCUS ' + s.focusPct + '%';
    $('noiseLabel').textContent = 'NOISE ' + s.noisePct + '%';
    $('focusFill').style.width = s.focusPct + '%';
    $('noiseFill').style.width = s.noisePct + '%';
    $('knot').style.left = (s.complete + s.noise ? s.focusPct : 50) + '%';
    $('tugText').textContent = !s.complete && !s.noise ? 'No decided missions'
      : s.focusPct >= s.noisePct ? 'Mission Focus leads by ' + (s.focusPct - s.noisePct) + ' points'
      : 'Noise leads by ' + (s.noisePct - s.focusPct) + ' points';
    $('syncRevision').textContent = 'Revision ' + data.revision;
    $('syncTime').textContent = 'Updated ' + new Date(data.updatedAt).toLocaleString([], { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit', second: '2-digit' });

    const updates = data.updates || [];
    const priority = updates.find(u => u.type === 'priority' || u.type === 'alert');
    const ann = $('announcement');
    if (priority) {
      ann.classList.add('show');
      ann.classList.toggle('priority', priority.type === 'priority');
      ann.classList.toggle('alert', priority.type === 'alert');
      $('announcementType').textContent = priority.type.toUpperCase();
      $('announcementMessage').textContent = priority.message;
      $('announcementTime').textContent = new Date(priority.createdAt).toLocaleString([], { hour: 'numeric', minute: '2-digit' });
    } else {
      ann.classList.remove('show', 'priority', 'alert');
    }

    const open = (data.tasks || []).filter(t => t.status === 'open').sort((a, b) => a.deadline.localeCompare(b.deadline));
    $('openMissionCount').textContent = open.length + ' open';
    $('currentMissions').innerHTML = open.length ? open.slice(0, 5).map(missionMarkup).join('') : '<div class="empty">No open Mission Focus tasks.</div>';

    const groups = {};
    (data.tasks || []).forEach(t => {
      groups[t.department] ??= { complete: 0, noise: 0, open: 0 };
      groups[t.department][t.status === 'complete' ? 'complete' : t.status === 'noise' ? 'noise' : 'open']++;
    });
    const departments = Object.entries(groups);
    $('departmentCount').textContent = departments.length + ' teams';
    $('departments').innerHTML = departments.length ? departments.slice(0, 5).map(([name, v]) => {
      const decided = v.complete + v.noise, pct = decided ? Math.round(v.complete / decided * 100) : 0;
      return '<div class="dept"><div><strong>' + esc(name) + '</strong><small>' + v.complete + ' Focus · ' + v.noise + ' Noise · ' + v.open +
        ' planned</small></div><div class="bar"><i style="width:' + pct + '%"></i></div><span class="pill ' + (pct >= 80 ? 'good' : pct >= 50 ? 'warn' : 'bad') + '">' + pct + '%</span></div>';
    }).join('') : '<div class="empty">Department scores appear after missions are assigned.</div>';

    $('updateCount').textContent = updates.length + ' updates';
    $('feed').innerHTML = updates.length ? updates.slice(0, 6).map(item =>
      '<article class="feeditem ' + esc(item.type) + '"><i class="feeddot"></i><div><b>' + esc(item.department) + ' · ' + esc(String(item.type).toUpperCase()) +
      '</b><p>' + esc(item.message) + '</p></div><time>' + esc(new Date(item.createdAt).toLocaleString([], { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' })) + '</time></article>'
    ).join('') : '<div class="empty">No company updates have been published.</div>';
  }

  async function refresh() {
    try {
      const res = await fetch(window.PTT.live, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
      if (!res.ok) { $('syncTime').textContent = res.status === 403 || res.status === 404 ? 'Display link is no longer valid' : 'Reconnecting…'; return; }
      const fresh = await res.json();
      if (!data || fresh.revision !== data.revision || fresh.updatedAt !== data.updatedAt) { data = fresh; render(); }
    } catch (e) { $('syncTime').textContent = 'Reconnecting…'; }
  }

  const tick = () => { $('clock').textContent = new Date().toLocaleString([], { weekday: 'short', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit', second: 'numeric' }); };
  tick(); setInterval(tick, 1000);
  refresh(); setInterval(refresh, 5000);
})();
