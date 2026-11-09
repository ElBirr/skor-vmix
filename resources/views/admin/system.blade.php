@extends('layouts.app')

@section('title', 'Sistem')
@section('content')
<div class="top">
  <div>
    <h1>Sistem</h1>
    <p class="subtitle">Kesehatan aplikasi dan pembersihan file logo.</p>
  </div>
  <div class="actions">
    <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">&larr; Admin</a>
    <a href="{{ route('admin.backup') }}" class="btn">Unduh Backup</a>
  </div>
</div>

<div class="card">
  <h2>Status</h2>
  <table class="table">
    <tbody>
      <tr><th>PHP</th><td>{{ $phpVersion }}</td></tr>
      <tr><th>Laravel</th><td>{{ $laravelVersion }}</td></tr>
      <tr><th>Database</th><td>{{ $driver }}</td></tr>
      @if ($databaseSize !== null)
        <tr><th>Ukuran database</th><td>{{ number_format($databaseSize / 1048576, 2) }} MB</td></tr>
      @endif
      <tr><th>Storage link</th><td>{{ $storageLinked ? 'Aktif' : 'Belum dibuat (jalankan php artisan storage:link)' }}</td></tr>
      <tr><th>Storage bisa ditulis</th><td>{{ $storageWritable ? 'Ya' : 'Tidak' }}</td></tr>
      @if ($diskFree !== null)
        <tr><th>Ruang disk tersisa</th><td>{{ number_format($diskFree / 1073741824, 2) }} GB</td></tr>
      @endif
    </tbody>
  </table>
</div>

<div class="card">
  <h2>Logo</h2>
  <p class="meta">{{ $logoCount }} file · {{ number_format($logoSize / 1024, 1) }} KB · {{ $orphanCount }} tidak terpakai ({{ number_format($orphanSize / 1024, 1) }} KB)</p>
  <form method="POST" action="{{ route('admin.system.logos') }}" onsubmit="return confirm('Hapus {{ $orphanCount }} file logo yang tidak dipakai?')">
    @csrf
    <button type="submit" class="btn" @disabled($orphanCount === 0)>Bersihkan Logo Tidak Terpakai</button>
  </form>
</div>
@endsection
