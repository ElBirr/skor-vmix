<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Masuk - {{ config('app.name') }}</title>
  <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body>
  <main class="auth-card">
    <h1>Masuk operator</h1>
    <p class="subtitle">Masuk untuk mengubah skor pertandingan.</p>
    @if ($errors->any())
      <p class="error">{{ $errors->first() }}</p>
    @endif
    <form method="POST" action="{{ route('login.store') }}">
      @csrf
      <label>Email <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus></label>
      <label>Kata sandi <input type="password" name="password" autocomplete="current-password" required></label>
      <button type="submit">Masuk</button>
    </form>
    @if ($needsSetup)
      <p class="auth-link"><a href="{{ route('register') }}">Buat operator pertama</a></p>
    @endif
  </main>
</body>
</html>
