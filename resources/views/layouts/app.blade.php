<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>@yield('title', config('app.name')) - {{ config('app.name') }}</title>
  <link rel="stylesheet" href="{{ asset('css/theme.css') }}" />
</head>
<body>
  <main class="container">
    <nav class="nav">
      <span class="nav-links">
        <a href="{{ route('scoreboard') }}" @class(['active' => request()->routeIs('scoreboard')])>Skor</a>
        <a href="{{ route('settings') }}" @class(['active' => request()->routeIs('settings')])>Pengaturan</a>
        <a href="{{ route('history') }}" @class(['active' => request()->routeIs('history*')])>Riwayat</a>
        @if (auth()->user()->isAdmin())
          <a href="{{ route('admin.dashboard') }}" @class(['active' => request()->routeIs('admin*')])>Admin</a>
        @endif
        <a href="{{ route('account.edit') }}" @class(['active' => request()->routeIs('account*')])>Akun</a>
      </span>
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn btn-secondary">Keluar</button>
      </form>
    </nav>

    @if (session('status'))
      <div class="alert alert-ok">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
      <div class="alert alert-error">
        <ul>
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    @yield('content')
  </main>
</body>
</html>
