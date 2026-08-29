<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Industrial Controls Academy — Enrollment')</title>
<link rel="stylesheet" href="{{ \App\Support\Asset::url('css/app.css') }}">
@stack('head')
</head>
<body>
@yield('body')
@stack('scripts')
</body>
</html>
