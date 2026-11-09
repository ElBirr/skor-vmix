@extends('layouts.app')

@section('title', 'Data')
@section('content')
<div class="top">
  <div>
    <h1>Data</h1>
    <p class="subtitle">Hanya baca. Password dan token sesi tidak ditampilkan.</p>
  </div>
  <div class="actions">
    <a href="{{ route('admin.backup') }}" class="btn">Unduh Backup</a>
  </div>
</div>

@foreach ($counts as $table => $count)
  <a class="match" href="{{ route('admin.data.show', $table) }}">
    <div class="teams">{{ $table }}</div>
    <div class="meta">{{ $count }} baris · buka tabel</div>
  </a>
@endforeach
@endsection
