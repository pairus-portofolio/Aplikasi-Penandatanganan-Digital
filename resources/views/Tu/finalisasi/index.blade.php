@extends('layouts.app')

@section('title', 'Finalisasi Surat')

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
            @if($s->nomor_surat)
                <div style="font-size:12px; color:#64748b;">
                    {{ $s->nomor_surat }}
                </div>
            @endif
          </td>

          <td>{{ $s->uploader->nama_lengkap ?? '-' }}</td>

          <td>{{ $s->created_at->format('d M Y') }}</td>

          <td>
            <span class="pill hijau">Ditandatangani</span>
          </td>

          <td>
            <a class="aksi" href="{{ route('tu.finalisasi.show', $s->id) }}">
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

@section('popup')

  
  {{-- 1. Logika untuk menampilkan popup konfirmasi download --}}
  @if(Session::has('download_doc_id'))
      @php
          // Ambil model Document untuk mendapatkan nama file dan ID
          $downloadDocument = App\Models\Document::find(Session::get('download_doc_id'));
      @endphp
      
      {{-- Sertakan file finalisasi-popup.blade.php --}}
      @include('partials.finalisasi-popup', ['document' => $downloadDocument])
  @endif

@endsection

@push('scripts')
  <script src="{{ asset('js/tu/finalisasi.js') }}"></script>
@endpush