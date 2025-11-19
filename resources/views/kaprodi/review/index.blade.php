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
            {{-- Gunakan $daftarTugas --}}
            @forelse($daftarTugas as $tugas)
            <tr>
                <td>{{ $tugas->document->judul_surat }}</td>
                <td>{{ $tugas->document->uploader->nama_lengkap }}</td>
                <td>{{ $tugas->document->created_at->format('d/m/Y') }}</td>
                <td><span class="pill">{{ $tugas->status }}</span></td>
                <td>
                    {{-- LINK KE HALAMAN REVIEW DETAIL --}}
                    <a class="aksi" href="{{ route('kaprodi.review.show', $tugas->document_id) }}">
                        Lihat
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align:center; padding: 20px; color: gray;">
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