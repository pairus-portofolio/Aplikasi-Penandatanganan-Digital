@extends('layouts.app')
@section('title', 'Dashboard TU')

@section('content')
  <h1 class="page-title">Dashboard</h1>

  <!-- {{-- Pesan sambutan, menampilkan nama pengguna dari session --}} -->
  <p class="welcome">
    Selamat datang <strong>{{ session('user_name') ?? 'Nama Pengguna' }}</strong>!
  </p>

  <!-- {{-- Menyertakan file partial untuk menampilkan card (misalnya ringkasan data) --}} -->
  @include('partials.cards')
    
  <!-- {{-- Tabel daftar surat --}} -->
  <div class="table-shell">
    <table>
      <thead>
        <tr>
          <th>Nomor Surat</th>
          <th>Judul</th>
          <th>Tanggal</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <!-- {{-- Menampilkan data surat jika tersedia, atau data contoh jika belum ada --}} -->
        @forelse(($daftarSurat ?? [
          ['nomor'=>'B/6756/UN31.FST/PK.01.00/2025','nama'=>'Nama surat','tanggal'=>'26/08/2025','status'=>'Ditinjau','status_class'=>'kuning'],
          ['nomor'=>'B/6756/UN31.FST/PK.01.00/2025','nama'=>'Nama surat','tanggal'=>'07/11/2024','status'=>'Perlu Revisi','status_class'=>'merah'],
          ['nomor'=>'B/6756/UN31.FST/PK.01.00/2025','nama'=>'Nama surat','tanggal'=>'19/01/2025','status'=>'Diparaf','status_class'=>'biru'],
          ['nomor'=>'B/6756/UN31.FST/PK.01.00/2025','nama'=>'Nama surat','tanggal'=>'17/03/2025','status'=>'Ditandatangani','status_class'=>'hijau'],
        ]) as $s)
        <tr>
          <!-- {{-- Menampilkan data surat --}} -->
          <td>{{ $s['nomor'] }}</td>
          <td>{{ $s['nama'] }}</td>
          <td>{{ $s['tanggal'] }}</td>

          <!-- {{-- Status surat dengan warna sesuai class --}} -->
          <td><span class="pill {{ $s['status_class'] }}">{{ $s['status'] }}</span></td>

          <!-- {{-- Tombol aksi untuk melihat detail surat --}} -->
          <td><a class="aksi" href="#">Lihat</a></td>
        </tr>

        <!-- {{-- Jika tidak ada data surat --}} -->
        @empty
        <tr>
          <td colspan="5" style="text-align:center;color:#94a3b8;padding:22px">
            Belum ada data surat.
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <!-- {{-- Footer halaman --}} -->
  <div class="foot">Sistem Manajemen Surat - Politeknik Negeri Bandung</div>
@endsection

@section('popup')
  {{-- Popup konfirmasi logout --}}
  <div class="popup" id="logoutPopup">
    <div class="popup-content">
      <h3>Konfirmasi Logout</h3>
      <p>Apakah Anda yakin ingin keluar dari sistem?</p>

      {{-- Tombol aksi popup --}}
      <div class="popup-btns">
        {{-- Tombol batal logout --}}
        <button class="btn-cancel" id="cancelLogout">Batal</button>

        {{-- Tombol konfirmasi logout (kirim form POST ke route logout) --}}
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" class="btn-confirm">Logout</button>
        </form>
      </div>
    </div>
  </div>
@endsection
