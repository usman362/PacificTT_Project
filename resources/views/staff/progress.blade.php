<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>PACIFIC TRADE TECH™ — Staff Progress</title>
  <meta name="robots" content="noindex">
<link rel="stylesheet" href="{{ \App\Support\Asset::url('css/staff.css') }}">
</head>
<body>
<header><div class="head"><div class="brand">PACIFIC <span>TRADE TECH™</span></div><div class="tv-title"><small>GENERAL STAFF PROGRESS</small><h1>Know the Mission. See the Progress.</h1></div><div class="head-right"><div class="sync-mini"><strong id="syncRevision">Revision —</strong><span id="syncTime">Waiting for live data</span></div><div class="live"><i class="dot"></i> LIVE COMPANY DATA</div><span class="clock" id="clock">—</span></div></div></header>
<main class="tv-dashboard">
  <div class="announcement" id="announcement"><b id="announcementType">PRIORITY</b><span id="announcementMessage"></span><time id="announcementTime"></time></div>
  <section class="cards">
    <article class="card gold"><div class="k">Monthly Enrollments</div><div class="v" id="enrollments">0</div><div class="d">Company target: {{ (int) $targets['target_enrollments'] }}</div></article>
    <article class="card"><div class="k">Seat Utilization</div><div class="v" id="utilization">0%</div><div class="d">Healthy threshold: {{ (int) $targets['target_utilization'] }}%</div></article>
    <article class="card"><div class="k">Attendance</div><div class="v" id="attendance">0%</div><div class="d">Active training sessions</div></article>
    <article class="card"><div class="k">Completion Forecast</div><div class="v" id="completion">0%</div><div class="d">Student outcomes</div></article>
    <article class="card"><div class="k">Mission Focus</div><div class="v" id="missionScore">0%</div><div class="d" id="missionDetail">No decided missions</div></article>
  </section>

  <section class="tv-grid">
    <div class="tv-col">
      <article class="panel"><div class="panelhead"><div><h2>Mission Focus vs. Noise</h2><p>Completed missions earn Focus; expired unfinished missions become Noise</p></div><span class="pill good" id="scorePill">Live score</span></div><div class="tug"><div class="tuglabels"><span class="focuslabel" id="focusLabel">MISSION FOCUS 0%</span><span class="noiselabel" id="noiseLabel">NOISE 0%</span></div><div class="tugbar"><i class="focusfill" id="focusFill"></i><i class="noisefill" id="noiseFill"></i><b class="knot" id="knot"></b></div><div class="tugtext" id="tugText">No decided missions</div></div></article>
      <article class="panel missions-panel"><div class="panelhead"><div><h2>Current Mission Priorities</h2><p>Open tasks approaching their completion time</p></div><span class="pill warn" id="openMissionCount">0 open</span></div><div class="missions" id="currentMissions"></div></article>
    </div>

    <div class="tv-col">
      <article class="panel"><div class="panelhead"><div><h2>Department Progress</h2><p>Mission outcomes by operating team</p></div><span class="pill" id="departmentCount">0 teams</span></div><div class="departments" id="departments"></div></article>
      <article class="panel"><div class="panelhead"><div><h2>Staff Expectations</h2><p>Daily operating standards</p></div><span class="pill good">Company standard</span></div>
        <div class="expectations tv-expectations">
          <article class="expectation"><span class="num">01</span><h3>Protect the Mission</h3><p>Complete Mission Focus tasks before the scheduled deadline.</p><i>✓</i></article>
          <article class="expectation"><span class="num">02</span><h3>Communicate Early</h3><p>Report staffing, student, equipment, payment, and safety risks early.</p><i>!</i></article>
          <article class="expectation"><span class="num">03</span><h3>Own the Result</h3><p>Close the loop and leave a clear operating status.</p><i>→</i></article>
          <article class="expectation"><span class="num">04</span><h3>Protect the Student</h3><p>Deliver safe, consistent, hands-on training.</p><i>★</i></article>
        </div>
      </article>
    </div>

    <div class="tv-col">
      <article class="panel schedule-panel">
        <div class="panelhead"><div><h2>Today — Instructor Schedule</h2><p>Realtime sessions and scheduled students per instructor</p></div><span class="pill good" id="scheduleCount">0 instructors</span></div>
        <div class="instructor-schedule" id="instructorSchedule"></div>
      </article>
      <article class="panel updates-panel"><div class="panelhead"><div><h2>Realtime Company Updates</h2><p>Latest information from the Assistant command screen</p></div><span class="pill good" id="updateCount">0 updates</span></div><div class="feed" id="feed"></div></article>
    </div>
  </section>
</main>

<script>window.PTT = @json($bootstrap);</script>
<script src="{{ \App\Support\Asset::url('js/staff.js') }}"></script>
</body>
</html>
