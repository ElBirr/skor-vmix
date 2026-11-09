@extends('layouts.app')

@section('title', 'Kontrol vMix')
@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}" />

<div class="top">
  <div>
    <h1>Kontrol vMix</h1>
    <p class="subtitle">Semua tombol grafis dalam satu layar untuk operator vMix. Perubahan tampil di vMix sekitar 1 detik.</p>
  </div>
</div>

<div class="card">
  <h2>Overlay</h2>
  <div class="control-grid">
    @foreach (['live' => 'Papan Skor', 'start' => 'Opening', 'end' => 'Hasil Akhir'] as $scene => $label)
      <button type="button" class="control-btn @if($overlayState[$scene]) is-on @endif" data-overlay-scene="{{ $scene }}">
        <strong>{{ $label }}</strong>
        <span>{{ $overlayState[$scene] ? 'Tampil' : 'Sembunyi' }}</span>
      </button>
    @endforeach
    <button type="button" class="control-btn" id="swapSidesBtn">
      <strong>Tukar Posisi</strong>
      <span>Home &harr; Away</span>
    </button>
    <button type="button" class="control-btn" id="serveCueBtn">
      <strong>Info Servis</strong>
      <span>Tampilkan lagi</span>
    </button>
  </div>
  <p class="subtitle" id="controlStatus" hidden></p>
</div>

<div class="card">
  <h2>Lower Third</h2>
  @if ($currentLowerThird)
    <p>
      <span class="pill pill-ok">LIVE</span>
      <strong>{{ $currentLowerThird->title }}</strong>
      @if ($currentLowerThird->subtitle) &mdash; {{ $currentLowerThird->subtitle }} @endif
    </p>
    <form method="POST" action="{{ route('lower-thirds.hide') }}" class="inline-form">
      @csrf
      <button type="submit" class="btn">Sembunyikan</button>
    </form>
  @else
    <p class="subtitle">Tidak ada lower third yang tayang.</p>
  @endif

  @if ($lowerThirds->isEmpty())
    <p class="subtitle">Belum ada preset. <a href="{{ route('lower-thirds.index') }}">Buat preset</a>.</p>
  @else
    <div class="control-grid">
      @foreach ($lowerThirds as $lowerThird)
        <form method="POST" action="{{ route('lower-thirds.show', $lowerThird) }}">
          @csrf
          <button type="submit" class="control-btn @if($currentLowerThird?->is($lowerThird)) is-on @endif">
            <strong>{{ $lowerThird->title }}</strong>
            <span>{{ $lowerThird->subtitle ?: ($lowerThird->duration > 0 ? $lowerThird->duration.' detik' : 'manual') }}</span>
          </button>
        </form>
      @endforeach
    </div>
  @endif
</div>

<div class="card">
  <h2>URL Browser Input vMix</h2>
  <p class="subtitle">Ukuran 1920×1080. Klik Salin lalu tempel di vMix &rarr; Add Input &rarr; Web Browser.</p>
  <div class="table-wrap">
    <table class="table">
      <tbody>
        @foreach ([
          'Papan skor' => route('overlay'),
          'Opening' => route('overlay', ['scene' => 'start']),
          'Hasil akhir' => route('overlay', ['scene' => 'end']),
          'Lower third' => route('overlay', ['scene' => 'lower']),
          'Layar jeda (input biasa)' => route('overlay', ['scene' => 'break']),
          'Stinger transisi' => route('overlay', ['scene' => 'transition']),
        ] as $label => $url)
          <tr>
            <td>{{ $label }}</td>
            <td><code>{{ $url }}</code></td>
            <td><button type="button" class="btn btn-secondary" data-copy="{{ $url }}">Salin</button></td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>

<script>
  (function () {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const status = document.getElementById('controlStatus');

    function flash(message) {
      status.textContent = message;
      status.hidden = false;
      clearTimeout(flash.timer);
      flash.timer = setTimeout(() => { status.hidden = true; }, 2500);
    }

    async function postJson(url, payload) {
      try {
        const res = await fetch(url, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
          body: JSON.stringify(payload),
        });
        if (!res.ok) throw new Error(res.status);
        return await res.json();
      } catch (e) {
        flash('Gagal menyimpan, coba lagi.');
        return null;
      }
    }

    function applyOverlayState(state) {
      document.querySelectorAll('[data-overlay-scene]').forEach(btn => {
        const visible = state[btn.dataset.overlayScene] !== false;
        btn.classList.toggle('is-on', visible);
        btn.querySelector('span').textContent = visible ? 'Tampil' : 'Sembunyi';
      });
    }

    document.querySelectorAll('[data-overlay-scene]').forEach(btn => {
      btn.addEventListener('click', async () => {
        btn.disabled = true;
        const state = await postJson('{{ route('overlay.visibility') }}', {
          scene: btn.dataset.overlayScene,
          visible: !btn.classList.contains('is-on'),
        });
        if (state) applyOverlayState(state);
        btn.disabled = false;
      });
    });

    document.getElementById('swapSidesBtn').addEventListener('click', async () => {
      if (!confirm('Tukar posisi tim? Nama, logo, warna, skor, timeout, servis, dan riwayat set akan ditukar.')) return;
      if (await postJson('{{ route('match.swap') }}', {})) flash('Posisi tim ditukar.');
    });

    document.getElementById('serveCueBtn').addEventListener('click', async () => {
      if (await postJson('{{ route('overlay.serve-cue') }}', {})) flash('Info servis ditampilkan.');
    });

    document.querySelectorAll('[data-copy]').forEach(btn => {
      btn.addEventListener('click', async () => {
        try {
          await navigator.clipboard.writeText(btn.dataset.copy);
          flash('URL disalin.');
        } catch (e) {
          window.prompt('Salin URL ini:', btn.dataset.copy);
        }
      });
    });

    // Sinkron bila tombol diubah dari perangkat lain (mis. halaman Skor).
    setInterval(async () => {
      const res = await fetch('{{ route('api.match') }}', { cache: 'no-store' });
      if (!res.ok) return;
      const data = await res.json();
      if (data && data.overlay) applyOverlayState(data.overlay);
    }, 2000);
  })();
</script>
@endsection
