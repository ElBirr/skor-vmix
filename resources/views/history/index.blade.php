<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Riwayat Pertandingan - {{ config('app.name') }}</title>
  <style>
    * { box-sizing: border-box; } body { margin:0; padding:32px 16px; background:#0d1117; color:#e6edf3; font-family:"Segoe UI",Roboto,Arial,sans-serif; }
    .container { max-width:720px; margin:0 auto; } .top { display:flex; justify-content:space-between; align-items:center; gap:16px; margin-bottom:24px; } h1 { margin:0; font-size:24px; }
    .btn { display:inline-block; padding:10px 14px; border:1px solid #30363d; border-radius:6px; color:#e6edf3; text-decoration:none; background:#21262d; font-size:14px; }
    .match { display:block; margin-bottom:12px; padding:16px; border:1px solid #30363d; border-radius:10px; background:#161b22; color:inherit; text-decoration:none; } .match:hover { border-color:#58a6ff; }
    .teams { font-size:18px; font-weight:700; } .score { color:#3fb950; } .meta, .empty { color:#8b949e; font-size:14px; margin-top:6px; }
  </style>
</head>
<body>
  <main class="container">
    <div class="top"><h1>Riwayat Pertandingan</h1><a href="{{ route('settings') }}" class="btn">Pengaturan</a></div>
    @forelse ($matches as $match)
      <a class="match" href="{{ route('history.show', $match) }}">
        <div class="teams">{{ $match->home_name }} <span class="score">{{ $match->home_score }} - {{ $match->away_score }}</span> {{ $match->away_name }}</div>
        <div class="meta">{{ strtoupper($match->sport) }} · Selesai {{ $match->updated_at->format('d M Y H:i') }}</div>
      </a>
    @empty
      <p class="empty">Belum ada pertandingan selesai.</p>
    @endforelse
  </main>
</body>
</html>
