<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>{{ $match->home_name }} vs {{ $match->away_name }} - {{ config('app.name') }}</title>
  <style>
    * { box-sizing:border-box; } body { margin:0; padding:32px 16px; background:#0d1117; color:#e6edf3; font-family:"Segoe UI",Roboto,Arial,sans-serif; } .container { max-width:720px; margin:0 auto; }
    .btn { display:inline-block; margin-bottom:24px; padding:10px 14px; border:1px solid #30363d; border-radius:6px; color:#e6edf3; text-decoration:none; background:#21262d; font-size:14px; }
    .card { margin-bottom:16px; padding:20px; border:1px solid #30363d; border-radius:10px; background:#161b22; } h1 { margin:0 0 12px; font-size:24px; } .score { color:#3fb950; font-size:32px; font-weight:700; } .meta, .event { color:#8b949e; font-size:14px; } .event { padding:10px 0; border-top:1px solid #30363d; } .event:first-of-type { border-top:0; }
  </style>
</head>
<body>
  <main class="container">
    <a href="{{ route('history') }}" class="btn">&larr; Riwayat</a>
    <section class="card">
      <h1>{{ $match->home_name }} vs {{ $match->away_name }}</h1>
      @php $result = $match->sport === 'volley' ? $match->setsWon() : ['home' => $match->home_score, 'away' => $match->away_score]; @endphp
      <div class="score">{{ $result['home'] }} - {{ $result['away'] }}</div>
      <p class="meta">{{ strtoupper($match->sport) }} · {{ $match->status }} · {{ $match->updated_at->format('d M Y H:i') }}</p>
      @if ($match->set_scores)<p class="meta">Set: {{ collect($match->set_scores)->map(fn ($set) => $set['home'].'-'.$set['away'])->implode(', ') }}</p>@endif
    </section>
    <section class="card">
      <h2>Perubahan</h2>
      @forelse ($events as $event)
        <div class="event">{{ $event->created_at->format('H:i:s') }} — {{ $event->user?->name ?? 'Operator dihapus' }}: {{ str_replace('_', ' ', $event->type) }}@if ($event->reason) ({{ $event->reason }})@endif</div>
      @empty
        <p class="meta">Belum ada perubahan tercatat.</p>
      @endforelse
      {{ $events->links() }}
    </section>
  </main>
</body>
</html>
