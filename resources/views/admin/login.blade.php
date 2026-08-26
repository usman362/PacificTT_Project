<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin sign in — Pacific Trade Tech</title>
<style>
body{margin:0;min-height:100vh;display:grid;place-items:center;padding:20px;
  background:linear-gradient(135deg,#06090e,#0b121c);color:#eef2f7;
  font:14px/1.5 Inter,system-ui,-apple-system,"Segoe UI",sans-serif}
.box{width:100%;max-width:380px;padding:26px;border:1px solid #25364a;border-radius:18px;background:#111a25}
h1{font-size:19px;margin:0 0 4px}p.sub{margin:0 0 20px;color:#8ea0b5;font-size:13px}
label{display:block;font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:#8ea0b5;margin:14px 0 6px}
input{width:100%;min-height:48px;padding:11px;border:1px solid #25364a;border-radius:10px;background:#0a121c;color:#eef2f7;font:inherit}
button{width:100%;min-height:50px;margin-top:20px;border:0;border-radius:11px;
  background:linear-gradient(145deg,#ffd149,#ff8b00);color:#111;font-weight:900;cursor:pointer}
.err{margin-top:12px;color:#ff8b8b;font-size:12px;font-weight:700}
.check{display:flex;align-items:center;gap:8px;margin-top:14px;font-size:13px;color:#b9c6d4}
.check input{width:18px;height:18px;min-height:0}
</style>
</head>
<body>
<form class="box" method="POST" action="{{ route('admin.login') }}">
  @csrf
  <h1>Pacific Trade Tech</h1>
  <p class="sub">Enrollment admin</p>

  <label for="email">Email</label>
  <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username" autofocus>

  <label for="password">Password</label>
  <input id="password" name="password" type="password" required autocomplete="current-password">

  <label class="check"><input type="checkbox" name="remember" value="1"> Remember me</label>

  @error('email')<div class="err">{{ $message }}</div>@enderror

  <button type="submit">Sign in</button>
</form>
</body>
</html>
