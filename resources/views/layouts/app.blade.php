<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'NagarBondhu') – NagarBondhu</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;800&family=Hind+Siliguri:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
<header class="site-header">
  <div class="wrap bar">
    <a class="brand" href="{{ route('home') }}">Nagar<span>Bondhu</span></a>
    <nav>
      @auth
        <a href="{{ route('reports.index') }}">My reports</a>
        <a href="{{ route('community.index') }}">Community</a>
        <a href="{{ route('track') }}">Track</a>
        @if (auth()->user()->isAdmin())<a href="{{ route('admin.reports.index') }}">Admin</a>@endif
        <a href="{{ route('reports.create') }}" class="nav-cta">Report a problem</a>
        <form method="POST" action="{{ route('logout') }}" class="inline">
          @csrf
          <button class="link-btn" type="submit">Log out ({{ auth()->user()->name }})</button>
        </form>
      @else
        <a href="{{ route('track') }}">Track a report</a>
        <a href="{{ route('login') }}">Log in</a>
        <a href="{{ route('register') }}" class="nav-cta">Create account</a>
      @endauth
    </nav>
  </div>
</header>
<main class="wrap">
  @if (session('success'))<div class="notice ok" role="status">{{ session('success') }}</div>@endif
  @if (session('error'))<div class="notice bad" role="alert">{{ session('error') }}</div>@endif
  @yield('content')
</main>
<footer class="wrap site-footer">NagarBondhu – a civic problem reporting platform for Bangladesh · CSE 470 project</footer>
<script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
