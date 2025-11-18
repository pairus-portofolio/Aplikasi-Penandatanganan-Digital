@extends('layouts.app')

@section('title', 'Daftar Review Surat')

@section('content')

  <h1 class="page-title">Daftar Surat Perlu Direview</h1>

  <div class="table-shell">
    <table>
      <thead>
        <tr>
          <th>Judul Surat</th>
          <th>Pengunggah</th>
          <th>Tanggal Pembuat</th>
          <th>Status</th>
          <th>Aksi</th> </tr>
      </thead>
      <tbody>
        @forelse($daftarSurat as $s)
        <tr>
          <td>
             <div style="font-weight:bold;">{{ $s['nama'] }}</div>
          </td>
          <td>{{ $s['pengunggah'] }}</td>
          <td>{{ $s['tanggal'] }}</td>
          <td>
            <span class="pill {{ $s['status_class'] }}">
              {{ $s['status'] }}
            </span>
          </td>
          <td>
            <a class="aksi" href="{{ route('kaprodi.review.show', $s['id_raw']) }}">
               Lihat
            </a>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="5" style="text-align:center;color:#94a3b8;padding:22px">
            Tidak ada surat yang perlu direview.
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

@endsection

@section('popup')
    @include('partials.logout-popup')
@endsection