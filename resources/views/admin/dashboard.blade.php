@extends('layouts.app')

@section('title', 'Admin')
@section('content')
<div class="top">
  <div>
    <h1>Admin</h1>
    <p class="subtitle">{{ $activeCount }} pertandingan aktif · {{ $finishedCount }} selesai · {{ $eventCount }} event audit</p>
  </div>
  <div class="actions">
    <a href="{{ route('admin.backup') }}" class="btn">Unduh Backup</a>
  </div>
</div>

<a class="match" href="{{ route('admin.data') }}">
  <div class="teams">Data</div>
  <div class="meta">{{ $matchCount }} pertandingan · {{ $eventCount }} event · {{ $userCount }} akun · ekspor CSV</div>
</a>

<a class="match" href="{{ route('admin.users') }}">
  <div class="teams">Operator</div>
  <div class="meta">{{ $adminCount }} admin · {{ $operatorCount }} operator · tambah, nonaktifkan, reset sandi</div>
</a>

<a class="match" href="{{ route('admin.system') }}">
  <div class="teams">Sistem</div>
  <div class="meta">Status database, storage, dan pembersihan logo</div>
</a>

<a class="match" href="{{ route('account.edit') }}">
  <div class="teams">Akun</div>
  <div class="meta">Ubah nama, username, dan kata sandi sendiri</div>
</a>
@endsection
