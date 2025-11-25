@extends('layouts.app')

@section('title', 'Daftar Tanda Tangan')

@section('content')

  <h1 class="page-title">Daftar Surat Perlu Tanda Tangan</h1>

  <div class="table-shell">
    <table>
      <thead>
        <tr>
          <th>Judul Surat</th>
          <th>Pengunggah</th>
          <th>Tanggal Pembuat</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        @forelse($daftarSurat as $s)
        <tr>
          <td>
             <div style="font-weight:bold;">{{ $s['nama'] }}</div>
             <div style="font-size:12px; color:#64748b;">{{ $s['nomor'] }}</div>
          </td>
          <td>{{ $s['pengunggah'] }}</td>
          <td>{{ $s['tanggal'] }}</td>
          <td>
            <span class="pill {{ $s['status_class'] }}">
              {{ $s['status'] }}
            </span>
          </td>
          <td>
            <a class="aksi" href="{{ route('kajur.tandatangan.show', $s['id_raw']) }}">
               Lihat
            </a>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="5" style="text-align:center;color:#94a3b8;padding:22px">
            Tidak ada surat yang perlu ditandatangani.
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <!-- Pagination Links -->
  <div style="margin-top: 20px;">
      {{ $daftarSurat->links('partials.pagination') }}
  </div>

@endsection

@section('popup')

    <!-- Popup konfirmasi logout -->
    @include('partials.logout-popup')
@endsection
