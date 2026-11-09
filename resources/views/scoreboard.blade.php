<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>Skor Lapangan - {{ config('app.name') }}</title>
  <link rel="stylesheet" href="{{ asset('css/theme.css') }}" />
  <style>
    :root {
      --gt-red: #c00000;
      --gt-navy: #001c54;
      --gt-deep: #000c22;
      --gt-silver: #d9d9d9;
      --gt-line: rgba(255, 255, 255, 0.55);
      --team-color: #c00000;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; user-select: none; -webkit-tap-highlight-color: transparent; }
    body {
      font-family: var(--gt-font);
      background: var(--gt-deep);
      background-image: radial-gradient(circle at 50% 0, rgba(0, 28, 84, 0.95), transparent 65%);
      color: #fff;
      height: 100vh;
      display: flex;
      flex-direction: column;
      overflow: hidden;
      letter-spacing: 0.02em;
    }

    /* Top Bar */
    .topbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 12px;
      background: var(--gt-navy);
      border-bottom: 2px solid #fff;
      padding: 10px 16px;
      min-height: 58px;
      overflow-x: auto;
    }
    .topbar-left, .topbar-right { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
    .badge {
      background: transparent;
      color: var(--gt-silver);
      padding: 6px 12px;
      border: 2px solid var(--gt-line);
      font-size: 13px;
      font-weight: 700;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      white-space: nowrap;
    }
    .badge.live { background: var(--gt-red); border-color: #fff; color: #fff; }
    .btn-link {
      background: transparent;
      color: #fff;
      text-decoration: none;
      padding: 7px 12px;
      border: 2px solid var(--gt-line);
      font-family: inherit;
      font-size: 13px;
      font-weight: 700;
      letter-spacing: 0.04em;
      text-transform: uppercase;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      cursor: pointer;
      white-space: nowrap;
    }
    .btn-link:hover, .btn-link:active { background: rgba(255, 255, 255, 0.14); border-color: #fff; }

    /* Main Arena */
    .arena {
      flex: 1;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
      padding: 16px;
      min-height: 0;
    }
    .team-panel {
      background: var(--gt-panel);
      border: 2px solid #fff;
      display: flex;
      flex-direction: column;
      min-height: 0;
      overflow: hidden;
    }
    .team-strip { height: 8px; background: var(--team-color); flex-shrink: 0; }
    .team-header {
      text-align: center;
      padding: 12px 16px;
      background: var(--gt-red);
      border-bottom: 2px solid #fff;
      flex-shrink: 0;
    }
    .team-name {
      color: #fff;
      font-size: 24px;
      font-weight: 900;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      margin-bottom: 2px;
    }
    .team-sub {
      font-size: 12px;
      color: rgba(255, 255, 255, 0.75);
      text-transform: uppercase;
      letter-spacing: 0.06em;
      min-height: 14px;
    }
    .set-won { margin-top: 4px; font-size: 12px; color: rgba(255, 255, 255, 0.75); letter-spacing: 0.08em; }
    .set-won b { font-size: 20px; color: #fff; }
    .serve-indicator {
      display: inline-block;
      width: 10px;
      height: 10px;
      background: rgba(255, 255, 255, 0.3);
      border: 2px solid #fff;
      margin-right: 6px;
      vertical-align: baseline;
    }
    .serving .serve-indicator { background: #fff; }

    /* Score Display & Big Buttons */
    .score-display {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 0;
      background: rgba(0, 12, 34, 0.35);
    }
    .score-value {
      font-size: min(26vw, 170px);
      font-weight: 900;
      line-height: 0.85;
      font-variant-numeric: tabular-nums;
      color: #fff;
    }
    .score-buttons {
      display: grid;
      grid-template-columns: 1fr 1fr;
      border-top: 2px solid #fff;
      flex-shrink: 0;
    }
    .btn-score {
      background: var(--gt-navy);
      color: #fff;
      border: 0;
      border-radius: 0;
      font-family: inherit;
      font-size: 36px;
      font-weight: 800;
      padding: 20px 0;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .btn-score:active { filter: brightness(1.15); }
    .btn-minus { background: var(--gt-navy); color: var(--gt-silver); border-right: 2px solid #fff; }
    .btn-plus { background: var(--gt-red); }

    /* Bottom Control Bar */
    .bottombar {
      background: var(--gt-navy);
      border-top: 2px solid #fff;
      padding: 10px 16px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 12px;
      min-height: 74px;
      flex-wrap: wrap;
    }
    .controls-group { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
    .ctrl-btn {
      background: transparent;
      color: #fff;
      border: 2px solid var(--gt-line);
      padding: 9px 14px;
      font-family: inherit;
      font-size: 13px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      cursor: pointer;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 2px;
    }
    .ctrl-btn span { font-size: 10px; color: var(--gt-silver); font-weight: 600; letter-spacing: 0.04em; }
    .ctrl-btn:active { background: rgba(255, 255, 255, 0.14); }
    .overlaybar { justify-content: flex-start; min-height: 0; border-top-color: var(--gt-line); }
    .overlaybar-label { font-size: 12px; color: var(--gt-silver); text-transform: uppercase; letter-spacing: 0.04em; }
    .ctrl-btn.is-on { background: #fff; color: var(--gt-navy); border-color: #fff; }
    .ctrl-btn.is-on span { color: var(--gt-navy); }
    .timeout-box {
      display: flex;
      align-items: center;
      gap: 8px;
      border: 2px solid var(--gt-line);
      padding: 6px 10px;
    }
    .timeout-label { font-size: 12px; color: var(--gt-silver); text-transform: uppercase; letter-spacing: 0.04em; white-space: nowrap; }
    .timeout-label b { color: #fff; }
    .mini {
      width: 38px; height: 38px;
      border: 2px solid var(--gt-line);
      background: transparent;
      color: var(--gt-silver);
      font-family: inherit;
      font-size: 20px;
      font-weight: 800;
      cursor: pointer;
      line-height: 1;
    }
    .mini.plus { background: var(--gt-red); border-color: #fff; color: #fff; }
    .mini:active { opacity: 0.75; }
    #toast {
      position: fixed;
      bottom: 90px;
      left: 50%;
      transform: translateX(-50%);
      background: var(--gt-red);
      color: #fff;
      border: 2px solid #fff;
      padding: 10px 20px;
      font-size: 15px;
      font-weight: 700;
      display: none;
      z-index: 10;
    }

    /* Empty state */
    .empty-screen {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      padding: 24px;
    }
    .empty-screen p { color: var(--gt-silver); margin: 8px 0 20px; }
    .btn-primary {
      background: var(--gt-red);
      color: #fff;
      padding: 12px 24px;
      border: 2px solid #fff;
      font-family: inherit;
      font-size: 15px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      text-decoration: none;
      cursor: pointer;
      margin-top: 16px;
    }

    @media (max-width: 900px) {
      .arena { gap: 10px; padding: 10px; }
      .team-name { font-size: 18px; }
      .score-value { font-size: min(24vw, 120px); }
      .btn-score { font-size: 28px; padding: 16px 0; }
      .bottombar { min-height: auto; }
    }
    @media (max-width: 560px) {
      .btn-score { font-size: 24px; padding: 12px 0; }
      .timeout-box { padding: 4px 6px; gap: 5px; }
      .mini { width: 32px; height: 32px; font-size: 17px; }
      .ctrl-btn { padding: 7px 10px; }
    }
  </style>
</head>
<body>
  @if (!$match)
    <div class="empty-screen">
      <h2 style="font-size: 24px; margin-bottom: 8px;">Belum Ada Pertandingan Aktif</h2>
      <p>Silakan buat atau aktifkan pertandingan melalui menu pengaturan.</p>
      <a href="{{ route('settings') }}" class="btn-primary">Buka Pengaturan &amp; Buat Pertandingan</a>
    </div>
  @else
    @php $sw = $match->setsWon(); @endphp
    <!-- Top Bar -->
    <div class="topbar">
      <div class="topbar-left">
        <span class="badge live" id="statusBadge">{{ $match->status ?: 'LIVE' }}</span>
        <span class="badge" id="periodBadge">{{ $match->period }}</span>
        @if($match->sport === 'volley')
          <span class="badge" id="setBadge">Set {{ $match->current_set }} &middot; {{ $sw['home'] }}-{{ $sw['away'] }}</span>
        @endif
        @if($match->clock)
          <span class="badge" id="clockBadge" style="font-variant-numeric: tabular-nums;">{{ $match->clock }}</span>
        @endif
      </div>
      <div class="topbar-right">
        <a href="{{ route('overlay') }}" target="_blank" class="btn-link">vMix Overlay</a>
        <a href="{{ route('history') }}" class="btn-link">Riwayat</a>
        <a href="{{ route('account.edit') }}" class="btn-link">Akun</a>
        @if (auth()->user()->isAdmin())
          <a href="{{ route('admin.dashboard') }}" class="btn-link">Admin</a>
        @endif
        <a href="{{ route('settings') }}" class="btn-link">Pengaturan Tim</a>
        <form method="POST" action="{{ route('match.finish') }}" onsubmit="return confirm('Selesaikan pertandingan? Data akan masuk ke riwayat.')">@csrf<button type="submit" class="btn-link">Selesai Pertandingan</button></form>
        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="btn-link">Keluar</button></form>
      </div>
    </div>

    <!-- Arena / Skor Utama -->
    <div class="arena">
      <!-- HOME TEAM -->
      <div class="team-panel @if($match->serving === 'home') serving @endif" id="homePanel" style="--team-color: {{ $match->home_color ?: '#c00000' }}">
        <div class="team-strip"></div>
        <div class="team-header">
          <div class="team-name">
            <span class="serve-indicator"></span><span id="homeNameOut">{{ $match->home_name }}</span>
          </div>
          <div class="team-sub" id="homeSubOut">{{ $match->home_sub }}</div>
          @if($match->sport === 'volley')
            <div class="set-won"><b id="homeSetWon">{{ $sw['home'] }}</b> SET</div>
          @endif
        </div>

        <div class="score-display">
          <div class="score-value" id="homeScoreOut">{{ $match->home_score }}</div>
        </div>

        <div class="score-buttons">
          <button type="button" class="btn-score btn-minus" data-side="home" data-delta="-1">&minus;</button>
          <button type="button" class="btn-score btn-plus" data-side="home" data-delta="1">+</button>
        </div>
      </div>

      <!-- AWAY TEAM -->
      <div class="team-panel @if($match->serving === 'away') serving @endif" id="awayPanel" style="--team-color: {{ $match->away_color ?: '#c00000' }}">
        <div class="team-strip"></div>
        <div class="team-header">
          <div class="team-name">
            <span class="serve-indicator"></span><span id="awayNameOut">{{ $match->away_name }}</span>
          </div>
          <div class="team-sub" id="awaySubOut">{{ $match->away_sub }}</div>
          @if($match->sport === 'volley')
            <div class="set-won"><b id="awaySetWon">{{ $sw['away'] }}</b> SET</div>
          @endif
        </div>

        <div class="score-display">
          <div class="score-value" id="awayScoreOut">{{ $match->away_score }}</div>
        </div>

        <div class="score-buttons">
          <button type="button" class="btn-score btn-minus" data-side="away" data-delta="-1">&minus;</button>
          <button type="button" class="btn-score btn-plus" data-side="away" data-delta="1">+</button>
        </div>
      </div>
    </div>

    <!-- Bottom Bar (Timeouts & Serve) -->
    <div class="bottombar">
      <div class="controls-group">
        @if($match->sport === 'volley')
          <button type="button" class="ctrl-btn" id="toggleServeBtn">
            Pindah Serve
            <span id="servingOut">Serve: {{ strtoupper($match->serving ?: '-') }}</span>
          </button>
        @endif
      </div>
      <div class="controls-group">
        <div class="timeout-box">
          <span class="timeout-label">Timeout Home <b id="homeTimeoutOut">{{ $match->home_timeouts }} / 2</b></span>
          <button type="button" class="mini" data-timeout="home" data-delta="-1">&minus;</button>
          <button type="button" class="mini plus" data-timeout="home" data-delta="1">+</button>
        </div>
        <div class="timeout-box">
          <span class="timeout-label">Timeout Away <b id="awayTimeoutOut">{{ $match->away_timeouts }} / 2</b></span>
          <button type="button" class="mini" data-timeout="away" data-delta="-1">&minus;</button>
          <button type="button" class="mini plus" data-timeout="away" data-delta="1">+</button>
        </div>
      </div>
    </div>
    <!-- Kontrol overlay vMix (tampil/sembunyi tiap scene) -->
    @php($overlayState = \App\Http\Controllers\OverlayController::state())
    <div class="bottombar overlaybar">
      <span class="overlaybar-label">Overlay vMix</span>
      @foreach (['live' => 'Papan Skor', 'start' => 'Opening', 'end' => 'Hasil Akhir'] as $overlayScene => $overlayLabel)
        <button type="button" class="ctrl-btn @if($overlayState[$overlayScene]) is-on @endif" data-overlay-scene="{{ $overlayScene }}">
          {{ $overlayLabel }}
          <span>{{ $overlayState[$overlayScene] ? 'Tampil' : 'Sembunyi' }}</span>
        </button>
      @endforeach
      @if($match->sport === 'volley')
        <button type="button" class="ctrl-btn" id="serveCueBtn">Tampilkan Info Servis</button>
      @endif
    </div>
    <div id="toast">Gagal menyimpan — ulangi</div>
  @endif

  <script>
    @if($match)
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    let lastWrite = 0;

    async function refresh() {
      const started = Date.now();
      const res = await fetch('{{ route('api.match') }}', { cache: 'no-store' });
      if (!res.ok) return;
      const data = await res.json();
      // Abaikan respons poll yang dimulai sebelum perubahan terakhir kita
      if (started >= lastWrite) applyState(data);
    }

    function showToast() {
      const t = document.getElementById('toast');
      t.style.display = 'block';
      clearTimeout(showToast._h);
      showToast._h = setTimeout(() => t.style.display = 'none', 2500);
    }

    async function postJson(url, payload) {
      try {
        const res = await fetch(url, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json'
          },
          body: JSON.stringify(payload)
        });
        if (!res.ok) throw new Error(res.status);
        lastWrite = Date.now();
        return await res.json();
      } catch (e) {
        showToast();
        refresh();
        return null;
      }
    }

    function applyState(data) {
      if (!data.home || !data.away) return;
      for (const side of ['home', 'away']) {
        const t = data[side];
        document.getElementById(side + 'ScoreOut').textContent = t.score;
        document.getElementById(side + 'TimeoutOut').textContent = t.timeouts + ' / 2';
        document.getElementById(side + 'NameOut').textContent = t.name;
        document.getElementById(side + 'SubOut').textContent = t.sub;
        document.getElementById(side + 'Panel').style.setProperty('--team-color', t.color || '#c00000');
        const setWon = document.getElementById(side + 'SetWon');
        if (setWon) setWon.textContent = t.setsWon;
      }
      const homePanel = document.getElementById('homePanel');
      const awayPanel = document.getElementById('awayPanel');
      const servingOut = document.getElementById('servingOut');

      homePanel.classList.toggle('serving', data.serving === 'home');
      awayPanel.classList.toggle('serving', data.serving === 'away');
      if (servingOut) servingOut.textContent = 'Serve: ' + (data.serving ? data.serving.toUpperCase() : '-');
      if (data.period) document.getElementById('periodBadge').textContent = data.period;
      if (data.status) document.getElementById('statusBadge').textContent = data.status;
      const setBadge = document.getElementById('setBadge');
      if (setBadge) setBadge.textContent = 'Set ' + data.currentSet + ' \u00b7 ' + data.home.setsWon + '-' + data.away.setsWon;
      const clockBadge = document.getElementById('clockBadge');
      if (clockBadge && data.clock) clockBadge.textContent = data.clock;
      if (data.overlay) applyOverlayState(data.overlay);
    }

    function applyOverlayState(state) {
      document.querySelectorAll('[data-overlay-scene]').forEach(btn => {
        const visible = state[btn.dataset.overlayScene] !== false;
        btn.classList.toggle('is-on', visible);
        btn.querySelector('span').textContent = visible ? 'Tampil' : 'Sembunyi';
      });
    }

    // Tombol tampil/sembunyi overlay
    document.querySelectorAll('[data-overlay-scene]').forEach(btn => {
      btn.addEventListener('click', async () => {
        if (btn.disabled) return;
        btn.disabled = true;
        try {
          const visible = !btn.classList.contains('is-on');
          const state = await postJson('{{ route('overlay.visibility') }}', { scene: btn.dataset.overlayScene, visible });
          if (state) applyOverlayState(state);
        } finally {
          btn.disabled = false;
        }
      });
    });

    const serveCueBtn = document.getElementById('serveCueBtn');
    if (serveCueBtn) {
      serveCueBtn.addEventListener('click', () => postJson('{{ route('overlay.serve-cue') }}', {}));
    }

    // Score +/- buttons
    document.querySelectorAll('.btn-score').forEach(btn => {
      btn.addEventListener('click', async () => {
        if (btn.disabled) return;
        btn.disabled = true;
        try {
          const delta = parseInt(btn.dataset.delta, 10);
          const reason = delta < 0 ? prompt('Alasan koreksi skor:') : null;
          if (delta < 0 && !reason) return;
          const res = await postJson('{{ route('match.score') }}', { side: btn.dataset.side, delta, reason });
          if (res) applyState(res);
        } finally {
          btn.disabled = false;
        }
      });
    });

    // Timeout -/+ buttons
    document.querySelectorAll('button[data-timeout]').forEach(btn => {
      btn.addEventListener('click', async () => {
        if (btn.disabled) return;
        btn.disabled = true;
        try {
          const delta = parseInt(btn.dataset.delta, 10);
          const reason = delta < 0 ? prompt('Alasan koreksi timeout:') : null;
          if (delta < 0 && !reason) return;
          const res = await postJson('{{ route('match.timeout') }}', { side: btn.dataset.timeout, delta, reason });
          if (res) applyState(res);
        } finally {
          btn.disabled = false;
        }
      });
    });

    // Toggle serve button
    const toggleServeBtn = document.getElementById('toggleServeBtn');
    if (toggleServeBtn) {
      toggleServeBtn.addEventListener('click', async () => {
        const currentServing = document.getElementById('homePanel').classList.contains('serving') ? 'home' : 
                               (document.getElementById('awayPanel').classList.contains('serving') ? 'away' : '');
        let nextServing = 'home';
        if (currentServing === 'home') nextServing = 'away';
        else if (currentServing === 'away') nextServing = '';
        
        const res = await postJson('{{ route('match.serve') }}', { serving: nextServing });
        if (res) applyState(res);
      });
    }

    // Sinkron dari server (perubahan settings / perangkat lain)
    setInterval(refresh, 1000);
    @endif
  </script>
</body>
</html>
