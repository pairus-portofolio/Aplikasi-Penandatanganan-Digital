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
            @forelse($daftarSurat as $tugas)
                <tr>
                    {{-- 1. Judul Surat (Key di controller: 'nama') --}}
                    <td>{{ $tugas['nama'] }}</td>

                    {{-- 2. Pengunggah (Key di controller: 'pengunggah') --}}
                    <td>{{ $tugas['pengunggah'] }}</td>

                    {{-- 3. Tanggal (Key di controller: 'tanggal') - Sudah diformat d/m/Y di controller --}}
                    <td>{{ $tugas['tanggal'] }}</td>

                    {{-- 4. Status (Key di controller: 'status' & 'status_class') --}}
                    <td>
                        {{-- Tambahkan class warna status biar sesuai logika controller --}}
                        <span class="pill {{ $tugas['status_class'] }}">
                            {{ $tugas['status'] }}
                        </span>
                    </td>

                    <td>
                        {{-- LINK KE HALAMAN REVIEW --}}
                        {{-- 5. ID (Key di controller: 'id_raw') --}}
                        <a class="aksi" href="{{ route('kaprodi.review.show', $tugas['id_raw']) }}">
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