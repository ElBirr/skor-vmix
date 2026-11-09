<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Upload Terlalu Besar - {{ config('app.name') }}</title>
  <link rel="stylesheet" href="{{ asset('css/theme.css') }}">
</head>
<body class="auth-body">
  <main class="auth-card">
    <h1>Upload terlalu besar</h1>
    <p class="subtitle">
      Total file yang dikirim melebihi batas server ({{ $limit }}), jadi perubahan belum tersimpan.
      Gunakan logo berukuran lebih kecil (maksimal 2 MB per file), atau upload logo satu per satu.
    </p>
    <a href="javascript:history.back()" class="btn">&larr; Kembali</a>
  </main>
</body>
</html>
