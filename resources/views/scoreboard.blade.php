<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>Skor Lapangan - {{ config('app.name') }}</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; user-select: none; -webkit-tap-highlight-color: transparent; }
    body {
      font-family: system-ui, -apple-system, sans-serif;
      background: #090d16;
      color: #f0f6fc;
      height: 100vh;
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }
    /* Top Bar */
    .topbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: #111827;
      border-bottom: 1px solid #1f2937;
      padding: 10px 16px;
      height: 56px;
    }
    .topbar-left, .topbar-right {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .badge {
      background: #1f2937;
      color: #9ca3af;
      padding: 6px 12px;
      border-radius: 6px;
      font-size: 14px;
      font-weight: 700;
      letter-spacing: 0.5px;
    }
    .badge.live { background: #7f1d1d; color: #fca5a5; }
    .btn-link {
      background: #1f2937;
      color: #e5e7eb;
      text-decoration: none;
      padding: 8px 14px;
      border-radius: 6px;
      font-size: 13px;
      font-weight: 600;
      border: 1px solid #374151;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    .btn-link:active { background: #374151; }

    /* Main Arena */
    .arena {
      flex: 1;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
      padding: 12px;
      height: calc(100vh - 130px);
    }
    .team-panel {
      background: #111827;
      border: 2px solid #1f2937;
      border-radius: 12px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      padding: 16px;
      position: relative;
      overflow: hidden;
    }
    .team-header {
      text-align: center;
    }
    .team-name {
      font-size: 24px;
      font-weight: 900;
      text-transform: uppercase;
      letter-spacing: 1px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      margin-bottom: 2px;
    }
    .team-sub {
      font-size: 13px;
      color: #9ca3af;
    }
    .set-won {
      margin-top: 6px;
      font-size: 13px;
      color: #8b949e;
      letter-spacing: 1px;
    }
    .set-won b { font-size: 20px; color: #f0f6fc; }
    .set-row {
      display: flex;
      gap: 6px;
      justify-content: center;
      margin-top: 6px;
    }
    .btn-set {
      padding: 6px 14px;
      background: #1f2937;
      border: 1px solid #374151;
      color: #e5e7eb;
      border-radius: 6px;
      font-size: 13px;
      font-weight: 700;
      cursor: pointer;
    }
    .btn-set:active { background: #374151; }
    .btn-set[data-delta="-1"] { color: #f87171; }
    .serve-indicator {
      display: inline-block;
      width: 10px;
      height: 10px;
      border-radius: 50%;
      background: #374151;
      margin-right: 6px;
      vertical-align: middle;
    }
    .serving .serve-indicator {
      background: #10b981;
      box-shadow: 0 0 10px #10b981;
    }

    /* Score Display & Big Buttons */
    .score-display {
      text-align: center;
      margin: auto 0;
    }
    .score-value {
      font-size: min(28vw, 160px);
      font-weight: 900;
      line-height: 1;
      font-variant-numeric: tabular-nums;
      margin: 8px 0;
    }
    .score-buttons {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
      margin-top: 8px;
    }
    .btn-score {
      background: #1f2937;
      color: #fff;
      border: none;
      border-radius: 10px;
      font-size: 36px;
      font-weight: 800;
      padding: 20px 0;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 4px 6px -1px rgba(0,0,0,0.3);
    }
    .btn-score:active { transform: scale(0.97); }
    .btn-minus { background: #374151; color: #f87171; font-size: 28px; }
    .btn-plus { background: #2563eb; color: #fff; }
    .btn-plus:active { background: #1d4ed8; }

    /* Bottom Control Bar */
    .bottombar {
      background: #111827;
      border-top: 1px solid #1f2937;
      padding: 10px 16px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      height: 74px;
    }
    .controls-group {
      display: flex;
      gap: 8px;
      align-items: center;
    }
    .ctrl-btn {
      background: #1f2937;
      color: #e5e7eb;
      border: 1px solid #374151;
      border-radius: 8px;
      padding: 10px 16px;
      font-size: 14px;
      font-weight: 700;
      cursor: pointer;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 2px;
    }
    .ctrl-btn:active { background: #374151; }
    .ctrl-btn span { font-size: 11px; color: #9ca3af; font-weight: normal; }
    .timeout-box {
      display: flex;
      align-items: center;
      gap: 8px;
      background: #1f2937;
      border: 1px solid #374151;
      border-radius: 8px;
      padding: 6px 10px;
    }
    .timeout-label { font-size: 13px; color: #9ca3af; white-space: nowrap; }
    .timeout-label b { color: #f0f6fc; }
    .mini {
      width: 40px; height: 40px;
      border: 1px solid #374151;
      background: #374151;
      color: #f87171;
      border-radius: 6px;
      font-size: 20px;
      font-weight: 800;
      cursor: pointer;
      line-height: 1;
    }
    .mini.plus { background: #2563eb; border-color: #2563eb; color: #fff; }
    .mini:active { opacity: 0.8; }
    #toast {
      position: fixed;
      bottom: 90px;
      left: 50%;
      transform: translateX(-50%);
      background: #7f1d1d;
      color: #fecaca;
      border: 1px solid #b91c1c;
      padding: 10px 20px;
      border-radius: 8px;
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
    .btn-primary {
      background: #2563eb;
      color: #fff;
      padding: 14px 28px;
      border-radius: 8px;
      font-size: 16px;
      font-weight: 700;
      text-decoration: none;
      border: none;
      cursor: pointer;
      margin-top: 16px;
    }
  </style>
</head>
<body>
  @if (!$match)
    <div class="empty-screen">
      <h2 style="font-size: 24px; margin-bottom: 8px;">Belum Ada Pertandingan Aktif</h2>
      <p style="color: #9ca3af; margin-bottom: 20px;">Silakan buat atau aktifkan pertandingan melalui menu pengaturan.</p>
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
          <span class="badge" id="clockBadge" style="font-family: monospace;">{{ $match->clock }}</span>
        @endif
      </div>
      <div class="topbar-right">
        <a href="{{ route('overlay') }}" target="_blank" class="btn-link">🖥️ vMix Overlay</a>
        <a href="{{ route('settings') }}" class="btn-link">⚙️ Pengaturan Tim</a>
      </div>
    </div>

    <!-- Arena / Skor Utama -->
    <div class="arena">
      <!-- HOME TEAM -->
      <div class="team-panel @if($match->serving === 'home') serving @endif" id="homePanel">
        <div class="team-header">
          <div class="team-name" style="color: {{ $match->home_color ?: '#f87171' }}">
            <span class="serve-indicator"></span><span id="homeNameOut">{{ $match->home_name }}</span>
          </div>
          <div class="team-sub" id="homeSubOut">{{ $match->home_sub }}</div>
          @if($match->sport === 'volley')
            <div class="set-won"><b id="homeSetWon">{{ $sw['home'] }}</b> SET</div>
            <div class="set-row">
              <button type="button" class="btn-set" data-set="home" data-delta="-1">&minus; Set</button>
              <button type="button" class="btn-set" data-set="home" data-delta="1">+ Set</button>
            </div>
          @endif
        </div>

        <div class="score-display">
          <div class="score-value" id="homeScoreOut" style="color: {{ $match->home_color ?: '#f87171' }}">{{ $match->home_score }}</div>
        </div>

        <div class="score-buttons">
          <button type="button" class="btn-score btn-minus" data-side="home" data-delta="-1">&minus;</button>
          <button type="button" class="btn-score btn-plus" data-side="home" data-delta="1">+</button>
        </div>
      </div>

      <!-- AWAY TEAM -->
      <div class="team-panel @if($match->serving === 'away') serving @endif" id="awayPanel">
        <div class="team-header">
          <div class="team-name" style="color: {{ $match->away_color ?: '#60a5fa' }}">
            <span class="serve-indicator"></span><span id="awayNameOut">{{ $match->away_name }}</span>
          </div>
          <div class="team-sub" id="awaySubOut">{{ $match->away_sub }}</div>
          @if($match->sport === 'volley')
            <div class="set-won"><b id="awaySetWon">{{ $sw['away'] }}</b> SET</div>
            <div class="set-row">
              <button type="button" class="btn-set" data-set="away" data-delta="-1">&minus; Set</button>
              <button type="button" class="btn-set" data-set="away" data-delta="1">+ Set</button>
            </div>
          @endif
        </div>

        <div class="score-display">
          <div class="score-value" id="awayScoreOut" style="color: {{ $match->away_color ?: '#60a5fa' }}">{{ $match->away_score }}</div>
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
            🏐 Pindah Serve
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
    <div id="toast">Gagal menyimpan — ulangi</div>
  @endif

  <script>
    @if($match)
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    async function refresh() {
      const res = await fetch('{{ route('api.match') }}', { cache: 'no-store' });
      if (res.ok) applyState(await res.json());
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
        document.getElementById(side + 'NameOut').style.color = t.color || '';
        document.getElementById(side + 'ScoreOut').style.color = t.color || '';
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
    }

    // Score +/- buttons
    document.querySelectorAll('.btn-score').forEach(btn => {
      btn.addEventListener('click', async () => {
        if (btn.disabled) return;
        btn.disabled = true;
        try {
          const res = await postJson('{{ route('match.score') }}', { side: btn.dataset.side, delta: parseInt(btn.dataset.delta, 10) });
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
          const res = await postJson('{{ route('match.timeout') }}', { side: btn.dataset.timeout, delta: parseInt(btn.dataset.delta, 10) });
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

    // +/- Set: catat/batalkan set untuk tim (minus mengembalikan skor set tsb)
    document.querySelectorAll('button[data-set]').forEach(btn => {
      btn.addEventListener('click', async () => {
        if (btn.disabled) return;
        btn.disabled = true;
        try {
          const res = await postJson('{{ route('match.set') }}', { side: btn.dataset.set, delta: parseInt(btn.dataset.delta, 10) });
          if (res) applyState(res);
        } finally {
          btn.disabled = false;
        }
      });
    });

    // Sinkron dari server (perubahan settings / perangkat lain)
    setInterval(refresh, 1000);
    @endif
  </script>
</body>
</html>
