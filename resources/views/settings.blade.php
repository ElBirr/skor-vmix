<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>Pengaturan Tim &amp; Pertandingan - {{ config('app.name') }}</title>
  <link rel="stylesheet" href="{{ asset('css/theme.css') }}" />
</head>
<body>
  <div class="container">
    <div class="top">
      <div>
        <h1>{{ $isNewMatch ? 'Siapkan Pertandingan Baru' : 'Pengaturan Pertandingan & Tim' }}</h1>
        <p class="subtitle">
          Overlay vMix (1920&times;1080):
          <a href="{{ route('overlay') }}" target="_blank">{{ route('overlay') }}</a> live &middot;
          <a href="{{ route('overlay') }}?scene=start" target="_blank">?scene=start</a> mulai &middot;
          <a href="{{ route('overlay') }}?scene=end" target="_blank">?scene=end</a> selesai.
        </p>
      </div>
      <div class="actions">
        <a href="{{ route('history') }}" class="btn btn-secondary">Riwayat</a>
        <a href="{{ route('account.edit') }}" class="btn btn-secondary">Akun</a>
        @if (auth()->user()->isAdmin())
          <a href="{{ route('admin.data') }}" class="btn btn-secondary">Data</a>
        @endif
        @if (! $isNewMatch)<a href="{{ route('scoreboard') }}" class="btn btn-secondary">&larr; Layar Skor</a>@endif
      </div>
    </div>

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

    <form method="POST" action="{{ route('match.update') }}" enctype="multipart/form-data">
      @csrf

      <div class="card">
        <h2>Pertandingan &amp; Format</h2>
        <div class="row">
          <div>
            <label>Cabang Olahraga</label>
            <select name="sport">
              <option value="volley" @selected($match->sport === 'volley')>Voli Beregu</option>
              <option value="futsal" @selected($match->sport === 'futsal')>Futsal</option>
            </select>
          </div>
        </div>
        <div class="row-3">
          <div>
            <label>Period / Set / Babak</label>
            <input type="text" name="period" value="{{ $match->period }}" maxlength="20" />
          </div>
          <div>
            <label>Clock (mis. 12:45)</label>
            <input type="text" name="clock" value="{{ $match->clock }}" maxlength="10" placeholder="untuk futsal" />
          </div>
          <div>
            <label>Status (mis. LIVE)</label>
            <input type="text" name="status" value="{{ $match->status }}" maxlength="20" />
          </div>
        </div>
      </div>

      <div class="card">
        <h2>Identitas Tim, Pelatih &amp; Warna</h2>
        <div class="row">
          <div>
            <label>Nama Tim Home</label>
            <input type="text" name="home_name" value="{{ $match->home_name }}" maxlength="50" required />
            <label>Pelatih / Sub (Home)</label>
            <input type="text" name="home_sub" value="{{ $match->home_sub }}" maxlength="50" />
            <label>Logo Home (upload)</label>
            @if ($match->home_logo)
              <img src="{{ $match->home_logo }}" alt="Logo Home" style="display:block;height:48px;margin-bottom:6px;" />
            @endif
            <input type="file" name="home_logo" accept="image/*" />
            <label>Warna Home (hex)</label>
            <input type="text" name="home_color" value="{{ $match->home_color }}" maxlength="7" />
          </div>
          <div>
            <label>Nama Tim Away</label>
            <input type="text" name="away_name" value="{{ $match->away_name }}" maxlength="50" required />
            <label>Pelatih / Sub (Away)</label>
            <input type="text" name="away_sub" value="{{ $match->away_sub }}" maxlength="50" />
            <label>Logo Away (upload)</label>
            @if ($match->away_logo)
              <img src="{{ $match->away_logo }}" alt="Logo Away" style="display:block;height:48px;margin-bottom:6px;" />
            @endif
            <input type="file" name="away_logo" accept="image/*" />
            <label>Warna Away (hex)</label>
            <input type="text" name="away_color" value="{{ $match->away_color }}" maxlength="7" />
          </div>
        </div>
      </div>

      <div class="card">
        <h2>Opsi Tampilan</h2>
        <label>Logo Event / Turnamen (upload)</label>
        @if ($match->logo)
          <img src="{{ $match->logo }}" alt="Logo Event" style="display:block;height:48px;margin-bottom:6px;" />
        @endif
        <input type="file" name="logo" accept="image/*" />
      </div>

      <button type="submit" class="btn">{{ $isNewMatch ? 'Mulai Pertandingan' : 'Simpan Pengaturan' }}</button>
      @if (! $isNewMatch)<a href="{{ route('scoreboard') }}" class="btn btn-secondary" style="margin-left: 8px;">Selesai &amp; Kembali</a>@endif
    </form>

    @if (! $isNewMatch)
    <div class="card">
      <h2>10 Perubahan Terakhir</h2>
      @forelse ($events as $event)
        <p class="event">
          {{ $event->created_at->format('H:i:s') }} — {{ $event->user?->name ?? 'Operator dihapus' }}: {{ str_replace('_', ' ', $event->type) }}@if ($event->reason) ({{ $event->reason }})@endif
        </p>
      @empty
        <p class="subtitle" style="margin-bottom:0;">Belum ada perubahan tercatat.</p>
      @endforelse
    </div>
    @endif
  </div>
</body>
</html>
