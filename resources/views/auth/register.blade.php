<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Operator pertama - {{ config('app.name') }}</title>
  <link rel="stylesheet" href="{{ asset('css/theme.css') }}">
</head>
<body class="auth-body">
  <main class="auth-card">
    <h1>Buat operator pertama</h1>
    <p class="subtitle">Halaman ini hanya tersedia sebelum operator dibuat.</p>
    @if ($errors->any())
      <div class="error"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    <form method="POST" action="{{ route('register.store') }}">
      @csrf
      <label>Nama <input name="name" value="{{ old('name') }}" autocomplete="name" required autofocus></label>
      <label>Email <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required></label>
      <label>Kata sandi <input type="password" name="password" minlength="12" autocomplete="new-password" required></label>
      <label>Ulangi kata sandi <input type="password" name="password_confirmation" minlength="12" autocomplete="new-password" required></label>
      <button type="submit" class="btn">Buat operator</button>
    </form>
  </main>
</body>
</html>
