@extends('layouts.app')

@section('title', 'Lower Third')
@section('content')
<div class="top">
  <div>
    <h1>Lower Third</h1>
    <p class="subtitle">Siapkan preset sebelum acara, lalu klik Tampilkan saat live. Di vMix pakai Browser Input <code>{{ route('overlay', ['scene' => 'lower']) }}</code>.</p>
  </div>
</div>

<div class="card">
  <h2>Sedang Tayang</h2>
  @if ($current)
    <p>
      <span class="pill pill-ok">LIVE</span>
      <strong>{{ $current->title }}</strong>
      @if ($current->subtitle) &mdash; {{ $current->subtitle }} @endif
      @if ($current->duration > 0) <span class="pill">turun otomatis {{ $current->duration }} detik</span> @endif
    </p>
    <form method="POST" action="{{ route('lower-thirds.hide') }}">
      @csrf
      <button type="submit" class="btn">Sembunyikan</button>
    </form>
  @else
    <p class="subtitle">Tidak ada lower third yang tayang.</p>
  @endif
</div>

<div class="card">
  <h2>Tambah Preset</h2>
  <form method="POST" action="{{ route('lower-thirds.store') }}" enctype="multipart/form-data">
    @csrf
    <div class="row">
      <div>
        <label>Judul (mis. nama pemain / sponsor)</label>
        <input type="text" name="title" value="{{ old('title') }}" maxlength="60" required />
      </div>
      <div>
        <label>Subjudul (opsional)</label>
        <input type="text" name="subtitle" value="{{ old('subtitle') }}" maxlength="120" />
      </div>
    </div>
    <div class="row">
      <div>
        <label>Turun otomatis setelah (detik, 0 = manual)</label>
        <input type="number" name="duration" value="{{ old('duration', 8) }}" min="0" max="600" required />
      </div>
      <div>
        <label>Logo (opsional)</label>
        <input type="file" name="logo" accept="image/*" />
      </div>
    </div>
    <button type="submit" class="btn">Tambah Preset</button>
  </form>
</div>

<div class="card">
  <h2>Daftar Preset</h2>
  @if ($lowerThirds->isEmpty())
    <p class="subtitle">Belum ada preset.</p>
  @else
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Logo</th>
            <th>Judul</th>
            <th>Subjudul</th>
            <th>Durasi</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($lowerThirds as $lowerThird)
            <tr>
              <td>
                @if ($lowerThird->logo)
                  <img src="{{ $lowerThird->logo }}" alt="" style="height:32px;" />
                @endif
              </td>
              <td>
                <strong>{{ $lowerThird->title }}</strong>
                @if ($current?->is($lowerThird)) <span class="pill pill-ok">LIVE</span> @endif
              </td>
              <td>{{ $lowerThird->subtitle }}</td>
              <td>{{ $lowerThird->duration > 0 ? $lowerThird->duration.' detik' : 'manual' }}</td>
              <td>
                <form method="POST" action="{{ route('lower-thirds.show', $lowerThird) }}" class="inline-form">
                  @csrf
                  <button type="submit" class="btn">Tampilkan</button>
                </form>
                <form method="POST" action="{{ route('lower-thirds.destroy', $lowerThird) }}" class="inline-form" onsubmit="return confirm('Hapus preset ini?')">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-secondary">Hapus</button>
                </form>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>
@endsection
