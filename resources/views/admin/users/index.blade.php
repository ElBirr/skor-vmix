@extends('layouts.app')

@section('title', 'Operator')
@section('content')
<div class="top">
  <div>
    <h1>Operator</h1>
    <p class="subtitle">Akun baru otomatis berperan operator. Hanya admin yang bisa membuka halaman ini.</p>
  </div>
  <div class="actions">
    <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">&larr; Admin</a>
  </div>
</div>

<div class="card">
  <h2>Tambah Operator</h2>
  <form method="POST" action="{{ route('admin.users.store') }}">
    @csrf
    <div class="row">
      <div>
        <label>Nama</label>
        <input type="text" name="name" value="{{ old('name') }}" maxlength="255" required />
      </div>
      <div>
        <label>Nama pengguna</label>
        <input type="text" name="username" value="{{ old('username') }}" minlength="3" maxlength="30" required />
      </div>
    </div>
    <div class="row">
      <div>
        <label>Kata Sandi Awal (min. 12 karakter)</label>
        <input type="password" name="password" minlength="12" required autocomplete="new-password" />
      </div>
      <div>
        <label>Ulangi Kata Sandi</label>
        <input type="password" name="password_confirmation" minlength="12" required autocomplete="new-password" />
      </div>
    </div>
    <button type="submit" class="btn">Tambah Operator</button>
  </form>
</div>

<div class="card">
  <h2>Daftar Akun</h2>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Nama</th>
          <th>Nama pengguna</th>
          <th>Peran</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($users as $user)
          <tr>
            <td>{{ $user->name }}</td>
            <td>{{ $user->username }}</td>
            <td><span class="pill">{{ $user->role }}</span></td>
            <td>
              @if ($user->is_active)
                <span class="pill pill-ok">Aktif</span>
              @else
                <span class="pill pill-off">Nonaktif</span>
              @endif
            </td>
            <td>
              <form method="POST" action="{{ route('admin.users.update', $user) }}" class="inline-form">
                @csrf
                @method('PUT')
                <select name="role">
                  <option value="operator" @selected($user->role === 'operator')>operator</option>
                  <option value="admin" @selected($user->role === 'admin')>admin</option>
                </select>
                <select name="is_active">
                  <option value="1" @selected($user->is_active)>aktif</option>
                  <option value="0" @selected(! $user->is_active)>nonaktif</option>
                </select>
                <button type="submit" class="btn btn-secondary">Simpan</button>
              </form>

              <details class="reset">
                <summary>Reset sandi</summary>
                <form method="POST" action="{{ route('admin.users.password', $user) }}" class="inline-form">
                  @csrf
                  @method('PUT')
                  <input type="password" name="password" minlength="12" placeholder="Sandi baru" required autocomplete="new-password" />
                  <input type="password" name="password_confirmation" minlength="12" placeholder="Ulangi sandi" required autocomplete="new-password" />
                  <button type="submit" class="btn">Reset</button>
                </form>
              </details>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  {{ $users->links('vendor.pagination.gt') }}
</div>
@endsection
