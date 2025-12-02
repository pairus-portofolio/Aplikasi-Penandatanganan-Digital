@extends('layouts.app')

@section('title', 'Arsip Surat')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/tu/arsip/index.css') }}">
@endpush

@section('content')

  <h1 class="page-title">Arsip Surat Final</h1>

  @include('partials.search-arsip')

  <div class="table-shell">
    <table>
      <thead>
        <tr>
          <th>Judul Surat</th>
          <th>Pengunggah</th>
          <th>Tanggal Finalisasi</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>
      </thead>

      <tbody>
        @forelse($documents as $d)
        <tr>

          {{-- Judul + Nomor surat --}}
          <td>
             <div style="font-weight:bold;">{{ $d->judul_surat }}</div>

             @if($d->nomor_surat)
             <div style="font-size:12px; color:#64748b;">
                 {{ $d->nomor_surat }}
             </div>
             @endif
          </td>

          {{-- Pengunggah --}}
          <td>{{ $d->uploader->nama_lengkap ?? '-' }}</td>

          {{-- Tanggal Updated --}}
          <td>{{ $d->updated_at->format('d/m/Y') }}</td>

          {{-- Status --}}
          <td>
            <span class="pill hijau">
              Final
            </span>
          </td>

          {{-- Aksi --}}
          <td>
            <a href="{{ route('tu.arsip.show', $d->id) }}" class="btn-action abu">
                Lihat
            </a>
          </td>

        </tr>
        @empty

        <tr>
          <td colspan="5" style="text-align:center;color:#94a3b8;padding:22px">
            Belum ada arsip surat.
          </td>
        </tr>

        @endforelse
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <div style="margin-top: 20px;">
      {{ $documents->links('partials.pagination') }}
  </div>

@endsection

@section('popup')
    @include('partials.logout-popup')
@endsection
