<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>Pengaturan Tim &amp; Pertandingan - {{ config('app.name') }}</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: "Segoe UI", Roboto, Arial, sans-serif;
      background: #0d1117;
      color: #e6edf3;
      min-height: 100vh;
      padding: 32px 16px;
    }
    .container { max-width: 720px; margin: 0 auto; }
    h1 { font-size: 22px; margin-bottom: 4px; }
    .subtitle { color: #8b949e; font-size: 14px; margin-bottom: 24px; }
    .subtitle a { color: #58a6ff; text-decoration: none; }
    .card {
      background: #161b22;
      border: 1px solid #30363d;
      border-radius: 10px;
      padding: 20px;
      margin-bottom: 20px;
    }
    .card h2 { font-size: 15px; margin-bottom: 16px; color: #8b949e; text-transform: uppercase; letter-spacing: 1px; }
    label { display: block; font-size: 13px; color: #8b949e; margin-bottom: 4px; }
    input, select {
      width: 100%;
      padding: 9px 12px;
      background: #0d1117;
      border: 1px solid #30363d;
      border-radius: 6px;
      color: #e6edf3;
      font-size: 14px;
      margin-bottom: 14px;
    }
    input:focus, select:focus { outline: none; border-color: #58a6ff; }
    .row { display: grid; grid-template-columns: 1fr 1fr; gap: 0 16px; }
    .row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0 16px; }
    .btn {
      display: inline-block;
      padding: 11px 22px;
      background: #238636;
      color: #fff;
      border: none;
      border-radius: 6px;
      font-size: 15px;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
    }
    .btn:hover { background: #2ea043; }
    .btn-secondary { background: #21262d; border: 1px solid #30363d; color: #e6edf3; }
    .btn-secondary:hover { background: #30363d; }
    .status-msg { font-size: 14px; color: #3fb950; margin-left: 12px; }
    @media (max-width: 560px) {
      .row, .row-3 { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>
  <div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
      <h1>{{ $isNewMatch ? 'Siapkan Pertandingan Baru' : 'Pengaturan Pertandingan & Tim' }}</h1>
      <div style="display:flex; gap:8px;">
        <a href="{{ route('history') }}" class="btn btn-secondary">Riwayat</a>
        @if (! $isNewMatch)<a href="{{ route('scoreboard') }}" class="btn btn-secondary">&larr; Layar Skor</a>@endif
      </div>
    </div>
    <p class="subtitle">
      Overlay vMix: <a href="{{ route('overlay') }}" target="_blank">{{ route('overlay') }}</a> &mdash;
      Gunakan URL ini di Browser Input vMix (1920&times;1080).
    </p>

    @if (session('status'))
      <div class="card" style="border-color:#238636; color:#3fb950;">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
      <div class="card" style="border-color:#f85149; color:#f85149;">
        <ul style="padding-left: 20px;">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form method="POST" action="{{ route('match.update') }}">
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
          <div>
            <label>Posisi Overlay</label>
            <select name="pos">
              <option value="bottom" @selected($match->pos === 'bottom')>Bawah</option>
              <option value="top" @selected($match->pos === 'top')>Atas</option>
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
            <label>Warna Home (hex)</label>
            <input type="text" name="home_color" value="{{ $match->home_color }}" maxlength="7" />
          </div>
          <div>
            <label>Nama Tim Away</label>
            <input type="text" name="away_name" value="{{ $match->away_name }}" maxlength="50" required />
            <label>Pelatih / Sub (Away)</label>
            <input type="text" name="away_sub" value="{{ $match->away_sub }}" maxlength="50" />
            <label>Warna Away (hex)</label>
            <input type="text" name="away_color" value="{{ $match->away_color }}" maxlength="7" />
          </div>
        </div>
      </div>

      <div class="card">
        <h2>Opsi Tampilan</h2>
        <div style="margin-top: 14px;">
          <label>Warna Aksen (hex)</label>
          <input type="text" name="accent" value="{{ $match->accent }}" maxlength="7" />
        </div>
      </div>

      <button type="submit" class="btn">{{ $isNewMatch ? 'Mulai Pertandingan' : 'Simpan Pengaturan' }}</button>
      @if (! $isNewMatch)<a href="{{ route('scoreboard') }}" class="btn btn-secondary" style="margin-left: 8px;">Selesai &amp; Kembali</a>@endif
    </form>
    @if (! $isNewMatch)
    <div class="card">
      <h2>10 Perubahan Terakhir</h2>
      @forelse ($events as $event)
        <p style="margin-bottom:8px; font-size:13px; color:#8b949e;">
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
