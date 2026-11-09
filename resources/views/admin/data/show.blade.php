@extends('layouts.app')

@section('title', $table)
@section('content')
<div class="top">
  <div>
    <h1>{{ $table }}</h1>
    <p class="subtitle">{{ $rows->total() }} baris · halaman {{ $rows->currentPage() }} dari {{ $rows->lastPage() }}</p>
  </div>
  <div class="actions">
    <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">&larr; Admin</a>
    <a href="{{ route('admin.data') }}" class="btn btn-secondary">&larr; Data</a>
    <a href="{{ route('admin.data.export', $table) }}" class="btn">Ekspor CSV</a>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          @foreach ($columns as $column)
            <th>{{ $column }}</th>
          @endforeach
        </tr>
      </thead>
      <tbody>
        @forelse ($rows as $row)
          <tr>
            @foreach ($columns as $column)
              <td title="{{ $row->{$column} }}">{{ \Illuminate\Support\Str::limit((string) $row->{$column}, 80) }}</td>
            @endforeach
          </tr>
        @empty
          <tr><td colspan="{{ count($columns) }}" class="empty">Belum ada data.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  {{ $rows->links('vendor.pagination.gt') }}
</div>
@endsection
