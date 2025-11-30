@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')

  <h1 class="page-title">Dashboard</h1>

  <p class="welcome">
    Selamat datang <strong>{{ Auth::user()->nama_lengkap ?? 'Pengguna' }}</strong>!
  </p>

  @include('partials.cards')
    
  <div class="table-shell">
    <table>
      <thead>
        <tr>
          <th>Judul Surat</th>
          <th>Pengunggah</th>
          <th>Tanggal Pembuat</th>
          <th>Status</th>
        </tr>
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
            {{-- LOGIKA: Cek apakah ada URL Revisi (Khusus TU yang kena Revisi) --}}
            @if(!empty($s['revision_url']))
                {{-- TAMPILAN LINK (Tanpa Border Tombol, Cuma Link Biasa) --}}
                <a href="{{ $s['revision_url'] }}" 
                   class="pill {{ $s['status_class'] }}" 
                   style="text-decoration: underline; cursor: pointer;"
                   title="Klik untuk memperbaiki surat ini">
                    {{ $s['status'] }}
                </a>
            @else
                {{-- TAMPILAN STATUS BIASA (Span) --}}
                <span class="pill {{ $s['status_class'] }}">
                  {{ $s['status'] }}
                </span>
            @endif
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="4" style="text-align:center;color:#94a3b8;padding:22px">
            Belum ada data surat.
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

  <div class="foot">Sistem Manajemen Surat - Politeknik Negeri Bandung</div>

@endsection

@section('popup')
  @include('partials.logout-popup')
@endsection