<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>@yield('title', 'Admin') — Pacific Trade Tech</title>
<style>
:root{--bg:#0a0f16;--card:#111a25;--line:#25364a;--txt:#eef2f7;--muted:#8ea0b5;--a:#ffad00}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--txt);font:14px/1.5 Inter,system-ui,-apple-system,"Segoe UI",sans-serif}
a{color:var(--a);text-decoration:none}
.topbar{display:flex;align-items:center;gap:16px;flex-wrap:wrap;padding:14px 18px;border-bottom:1px solid var(--line);background:#080d14}
.topbar .brand{font-weight:900;letter-spacing:.08em;text-transform:uppercase;font-size:13px}
.topbar nav{display:flex;gap:14px;flex-wrap:wrap}
.topbar nav a{color:#b9c6d4;font-weight:700;font-size:13px;padding:6px 2px}
.topbar nav a.on{color:var(--a);border-bottom:2px solid var(--a)}
.topbar form{margin-left:auto}
.topbar button{background:transparent;border:1px solid var(--line);color:#b9c6d4;border-radius:8px;padding:8px 12px;cursor:pointer;min-height:40px}
.wrap{max-width:1180px;margin:0 auto;padding:20px 16px 60px}
h1{font-size:22px;margin:0 0 18px}
.cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:24px}
.card{padding:16px;border:1px solid var(--line);border-radius:14px;background:var(--card)}
.card .k{font-size:11px;letter-spacing:.1em;text-transform:uppercase;color:var(--muted)}
.card .v{font-size:26px;font-weight:900;margin-top:6px}
.panel{border:1px solid var(--line);border-radius:14px;background:var(--card);overflow:hidden}
table{width:100%;border-collapse:collapse;font-size:13px}
th,td{padding:11px 12px;text-align:left;border-bottom:1px solid #1c2a3a;vertical-align:top}
th{font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);background:#0d1521}
tbody tr:hover{background:#0d1723}
.badge{display:inline-block;padding:3px 8px;border-radius:20px;font-size:10px;font-weight:900;letter-spacing:.06em;text-transform:uppercase}
.b-paid{background:#0f2a1c;color:#6fdc9b}.b-deposit{background:#2a2410;color:#e5c25a}
.b-waiver{background:#132436;color:#7fb4e6}.b-started{background:#1d1f24;color:#98a4b3}
.b-cancelled,.b-abandoned{background:#2a1414;color:#ef8b8b}
.filters{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;margin-bottom:14px}
input,select{width:100%;min-height:42px;padding:9px 11px;border:1px solid var(--line);border-radius:10px;background:#0a121c;color:var(--txt);font:inherit}
.btn{display:inline-flex;align-items:center;justify-content:center;min-height:42px;padding:10px 16px;border:0;border-radius:10px;background:linear-gradient(145deg,#ffd149,#ff8b00);color:#111;font-weight:900;cursor:pointer}
.btn--ghost{background:transparent;border:1px solid var(--line);color:#b9c6d4}
.flash{margin-bottom:16px;padding:12px 14px;border:1px solid #1f5137;border-radius:10px;background:#0e2018;color:#7fe0a8;font-weight:700}
.err{color:#ff8b8b;font-size:12px;margin-top:6px}
.muted{color:var(--muted)}
.pager{padding:12px}
.row2{display:grid;grid-template-columns:1fr 1fr;gap:18px}
dl{margin:0}dt{font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);margin-top:12px}
dd{margin:3px 0 0;font-weight:600}
.sig{max-width:100%;border-radius:10px;background:#fff;padding:6px}
@media(max-width:820px){
  .row2{grid-template-columns:1fr}
  /* The data table keeps its width and scrolls inside its own box, so the
     page itself never scrolls sideways. */
  .scroll-x{overflow-x:auto;-webkit-overflow-scrolling:touch}
  table{min-width:720px}
  /* Comfortable touch targets on a phone; desktop keeps the compact nav. */
  .topbar nav a{display:inline-flex;align-items:center;min-height:44px;padding:0 2px}
  td a{display:inline-flex;align-items:center;min-height:44px}
  .btn,.topbar button{min-height:44px}
}
</style>
</head>
<body>
<div class="topbar">
  <span class="brand">PTT Admin</span>
  <nav>
    <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'on' : '' }}">Dashboard</a>
    <a href="{{ route('admin.enrollments') }}" class="{{ request()->routeIs('admin.enrollments*') ? 'on' : '' }}">Enrollments</a>
    <a href="{{ route('admin.certificates') }}" class="{{ request()->routeIs('admin.certificates*') ? 'on' : '' }}">Certificates</a>
    <a href="{{ url('/') }}" target="_blank" rel="noopener">View site ↗</a>
  </nav>
  <form method="POST" action="{{ route('admin.logout') }}">@csrf<button type="submit">Log out</button></form>
</div>

<div class="wrap">
  @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
  @yield('content')
</div>
</body>
</html>
