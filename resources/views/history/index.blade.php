<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Riwayat Pertandingan - {{ config('app.name') }}</title>
  <link rel="stylesheet" href="{{ asset('css/theme.css') }}" />
</head>
<body>
  <main class="container">
    <div class="top">
      <h1>Riwayat Pertandingan</h1>
      <div class="actions">
        <a href="{{ route('scoreboard') }}" class="btn btn-secondary">Layar Skor</a>
        <a href="{{ route('settings') }}" class="btn btn-secondary">Pengaturan</a>
      </div>
    </div>
    @forelse ($matches as $match)
      @php $result = $match->sport === 'volley' ? $match->setsWon() : ['home' => $match->home_score, 'away' => $match->away_score]; @endphp
      <a class="match" href="{{ route('history.show', $match) }}">
        <div class="teams">{{ $match->home_name }} <span class="score">{{ $result['home'] }} - {{ $result['away'] }}</span> {{ $match->away_name }}</div>
        <div class="meta">{{ strtoupper($match->sport) }} · Selesai {{ $match->updated_at->format('d M Y H:i') }}</div>
      </a>
    @empty
      <p class="empty">Belum ada pertandingan selesai.</p>
    @endforelse
    {{ $matches->links('vendor.pagination.gt') }}
  </main>
</body>
</html>
