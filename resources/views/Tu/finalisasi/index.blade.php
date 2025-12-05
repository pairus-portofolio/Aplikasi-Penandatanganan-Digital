@extends('layouts.app')

@section('title', 'Finalisasi Surat')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/tu/arsip/index.css') }}">
@endpush

@section('content')

  <h1 class="page-title">Daftar Surat Siap Finalisasi</h1>

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
        @forelse($suratFinalisasi as $s)
          <tr>
            <td>
              <div style="font-weight:bold;">{{ $s->judul_surat }}</div>
            </td>

            <td>{{ $s->uploader->nama_lengkap ?? '-' }}</td>

            <td>{{ $s->created_at->format('d/m/Y') }}</td>

            <td>
              <span class="pill hijau">Ditandatangani</span>
            </td>

            <td>
              <a class="btn-action abu" href="{{ route('tu.finalisasi.show', $s->id) }}">
                Lihat
              </a>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="5" style="text-align:center;color:#94a3b8;padding:22px">
              Tidak ada surat yang siap finalisasi.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

@endsection

{{-- Popup handled by ModalManager (Bootstrap Modal) --}}



@push('scripts')
  <script src="{{ asset('js/tu/finalisasi.js') }}"></script>
@endpush