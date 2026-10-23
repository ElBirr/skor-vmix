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
      <h1>Pengaturan Pertandingan &amp; Tim</h1>
      <a href="{{ route('scoreboard') }}" class="btn btn-secondary">&larr; Kembali ke Layar Skor</a>
    </div>
    <p class="subtitle">
      Overlay vMix: <a href="/overlay" target="_blank">/overlay</a> &mdash;
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

    @if (!$match)
      <div class="card" style="text-align: center; padding: 40px 20px;">
        <h2 style="margin-bottom: 8px; color: #e6edf3;">Belum Ada Pertandingan Aktif</h2>
        <p class="subtitle" style="margin-bottom: 24px;">Klik tombol di bawah untuk membuat dan memulai pertandingan baru secara instan.</p>
        <form method="POST" action="{{ route('match.update') }}">
          @csrf
          <input type="hidden" name="sport" value="volley">
          <input type="hidden" name="pos" value="bottom">
          <input type="hidden" name="period" value="SET 1">
          <input type="hidden" name="clock" value="">
          <input type="hidden" name="status" value="LIVE">
          <input type="hidden" name="home_name" value="TIM A">
          <input type="hidden" name="away_name" value="TIM B">
          <input type="hidden" name="home_sub" value="">
          <input type="hidden" name="away_sub" value="">
          <input type="hidden" name="home_color" value="#e11d48">
          <input type="hidden" name="away_color" value="#2563eb">
          <input type="hidden" name="accent" value="#f59e0b">
          <input type="hidden" name="home_score" value="0">
          <input type="hidden" name="away_score" value="0">
          <input type="hidden" name="current_set" value="1">
          <input type="hidden" name="serving" value="">
          <input type="hidden" name="home_timeouts" value="0">
          <input type="hidden" name="away_timeouts" value="0">
          <button type="submit" class="btn">Mulai Pertandingan Baru</button>
        </form>
      </div>
    @else
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
        <h2>Opsi Tambahan (Voli)</h2>
        <div class="row">
          <div>
            <label>Set Aktif (current_set)</label>
            <input type="number" name="current_set" value="{{ $match->current_set }}" min="1" max="9" />
          </div>
          <div>
            <label>Skor Set Sebelumnya (format: 25-23,25-20)</label>
            <input type="text" name="set_scores_text"
              value="@php
                echo collect($match->set_scores ?? [])->map(fn ($s) => $s['home'].'-'.$s['away'])->implode(',');
              @endphp"
              placeholder="25-23,25-20" />
          </div>
        </div>
        <div style="margin-top: 14px;">
          <label>Warna Aksen (hex)</label>
          <input type="text" name="accent" value="{{ $match->accent }}" maxlength="7" />
        </div>
      </div>

      <button type="submit" class="btn">Simpan Pengaturan</button>
      <a href="{{ route('scoreboard') }}" class="btn btn-secondary" style="margin-left: 8px;">Selesai &amp; Kembali</a>
    </form>
    @endif
  </div>
</body>
</html>
