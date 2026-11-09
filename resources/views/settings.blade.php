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
          <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">Admin</a>
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
            <label>Warna Home</label>
            <div class="color-field">
              <input type="color" value="{{ $match->home_color }}" data-color-for="home_color" aria-label="Pilih warna" />
              <input type="text" name="home_color" value="{{ $match->home_color }}" maxlength="7" pattern="#[0-9A-Fa-f]{6}" />
            </div>
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
            <label>Warna Away</label>
            <div class="color-field">
              <input type="color" value="{{ $match->away_color }}" data-color-for="away_color" aria-label="Pilih warna" />
              <input type="text" name="away_color" value="{{ $match->away_color }}" maxlength="7" pattern="#[0-9A-Fa-f]{6}" />
            </div>
          </div>
        </div>
      </div>

      <div class="card">
        <h2>Opsi Tampilan</h2>
        <label>Judul Headline Opening/Hasil (kosong = nama olahraga)</label>
        <input type="text" name="headline" value="{{ old('headline', $match->headline) }}" maxlength="40" placeholder="mis. PORPROV 2026 / FINAL VOLI PUTRA" />
        <label>Babak / Grup (tampil di Opening &amp; Hasil Akhir)</label>
        <input type="text" name="round" value="{{ old('round', $match->round) }}" maxlength="40" placeholder="mis. SEMIFINAL / GRUP A" />
        <div class="row">
          <div>
            <label>Venue / Lokasi (tampil di Opening)</label>
            <input type="text" name="venue" value="{{ old('venue', $match->venue) }}" maxlength="60" placeholder="GOR Satria" />
          </div>
          <div>
            <label>Tanggal Pertandingan</label>
            <input type="date" name="match_date" value="{{ old('match_date', $match->match_date?->toDateString()) }}" />
          </div>
        </div>
        <label>Logo Event / Turnamen (upload)</label>
        @if ($match->logo)
          <img src="{{ $match->logo }}" alt="Logo Event" style="display:block;height:48px;margin-bottom:6px;" />
        @endif
        <input type="file" name="logo" accept="image/*" />
        <label>Warna Utama Overlay</label>
        <div class="color-field">
              <input type="color" value="{{ $match->theme_primary ?? '#001c54' }}" data-color-for="theme_primary" aria-label="Pilih warna" />
              <input type="text" name="theme_primary" value="{{ $match->theme_primary ?? '#001c54' }}" maxlength="7" pattern="#[0-9A-Fa-f]{6}" placeholder="#001c54" />
            </div>
        <label>Warna Aksen Overlay</label>
        <div class="color-field">
              <input type="color" value="{{ $match->theme_accent ?? '#c00000' }}" data-color-for="theme_accent" aria-label="Pilih warna" />
              <input type="text" name="theme_accent" value="{{ $match->theme_accent ?? '#c00000' }}" maxlength="7" pattern="#[0-9A-Fa-f]{6}" placeholder="#c00000" />
            </div>
      </div>

      {{-- Menempel di bawah layar supaya Simpan selalu terjangkau di tablet --}}
      <div class="form-actions">
        <button type="submit" class="btn">{{ $isNewMatch ? 'Mulai Pertandingan' : 'Simpan Pengaturan' }}</button>
        @if (! $isNewMatch)<a href="{{ route('scoreboard') }}" class="btn btn-secondary">Selesai &amp; Kembali</a>@endif
      </div>
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
  <script src="{{ asset('js/image-resize.js') }}"></script>
  <script>
    // Color picker dan kotak hex saling sinkron.
    document.querySelectorAll('[data-color-for]').forEach(picker => {
      const text = document.querySelector('input[name="' + picker.dataset.colorFor + '"]');
      picker.addEventListener('input', () => { text.value = picker.value; });
      text.addEventListener('input', () => {
        const value = text.value.trim();
        const hex = value.startsWith('#') ? value : '#' + value;
        if (/^#[0-9A-Fa-f]{6}$/.test(hex)) picker.value = hex.toLowerCase();
      });
    });
  </script>
</body>
</html>
