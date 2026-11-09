@extends('layouts.app')

@section('title', 'Akun')
@section('content')
<div class="top">
  <div>
    <h1>Akun</h1>
    <p class="subtitle">{{ auth()->user()->name }} · {{ '@'.auth()->user()->username }} · {{ auth()->user()->role }}</p>
  </div>
</div>

<div class="card">
  <h2>Profil</h2>
  <form method="POST" action="{{ route('account.update') }}">
    @csrf
    @method('PUT')
    <div class="row">
      <div>
        <label>Nama</label>
        <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}" maxlength="255" required />
      </div>
      <div>
        <label>Nama pengguna</label>
        <input type="text" name="username" value="{{ old('username', auth()->user()->username) }}" minlength="3" maxlength="30" required />
      </div>
    </div>
    <button type="submit" class="btn">Simpan Profil</button>
  </form>
</div>

<div class="card">
  <h2>Kata Sandi</h2>
  <form method="POST" action="{{ route('account.password') }}">
    @csrf
    @method('PUT')
    <label>Kata Sandi Sekarang</label>
    <input type="password" name="current_password" required autocomplete="current-password" />
    <div class="row">
      <div>
        <label>Kata Sandi Baru (min. 12 karakter)</label>
        <input type="password" name="password" minlength="12" required autocomplete="new-password" />
      </div>
      <div>
        <label>Ulangi Kata Sandi Baru</label>
        <input type="password" name="password_confirmation" minlength="12" required autocomplete="new-password" />
      </div>
    </div>
    <button type="submit" class="btn">Ganti Kata Sandi</button>
  </form>
</div>
@endsection
