<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>{{ $match->home_name }} vs {{ $match->away_name }} - {{ config('app.name') }}</title>
  <link rel="stylesheet" href="{{ asset('css/theme.css') }}" />
</head>
<body>
  <main class="container">
    <a href="{{ route('history') }}" class="btn btn-secondary back">&larr; Riwayat</a>
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
      {{ $events->links('vendor.pagination.gt') }}
    </section>
  </main>
</body>
</html>
