@extends('layouts.app')
@section('title', 'Dashboard TU')

@section('content')
  <h1 class="page-title">Dashboard</h1>

  <!-- Menampilkan pesan sambutan dengan nama user dari session -->
  <p class="welcome">
    Selamat datang <strong>{{ session('user_name') ?? 'Nama Pengguna' }}</strong>!
  </p>

  <!-- Menyertakan komponen kartu ringkasan data -->
  @include('partials.cards')
    
  <!-- Wrapper tabel daftar surat -->
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

        <!-- Menampilkan daftar surat jika tersedia. Jika tidak ada, gunakan data dummy -->
        @forelse(($daftarSurat ?? [
          ['nomor'=>'B/6756/UN31.FST/PK.01.00/2025','nama'=>'Nama surat','tanggal'=>'26/08/2025','status'=>'Ditinjau','status_class'=>'kuning'],
          ['nomor'=>'B/6756/UN31.FST/PK.01.00/2025','nama'=>'Nama surat','tanggal'=>'07/11/2024','status'=>'Perlu Revisi','status_class'=>'merah'],
          ['nomor'=>'B/6756/UN31.FST/PK.01.00/2025','nama'=>'Nama surat','tanggal'=>'19/01/2025','status'=>'Diparaf','status_class'=>'biru'],
          ['nomor'=>'B/6756/UN31.FST/PK.01.00/2025','nama'=>'Nama surat','tanggal'=>'17/03/2025','status'=>'Ditandatangani','status_class'=>'hijau'],
        ]) as $s)

        <tr>
          <!-- Menampilkan nomor surat -->
          <td>{{ $s['nomor'] }}</td>

          <!-- Menampilkan judul surat -->
          <td>{{ $s['nama'] }}</td>

          <!-- Menampilkan tanggal surat -->
          <td>{{ $s['tanggal'] }}</td>

          <!-- Menampilkan badge status dengan warna sesuai class -->
          <td><span class="pill {{ $s['status_class'] }}">{{ $s['status'] }}</span></td>

          <!-- Tombol untuk melihat detail surat -->
          <td><a class="aksi" href="#">Lihat</a></td>
        </tr>

        @empty
        <!-- Pesan jika tidak ada data surat -->
        <tr>
          <td colspan="5" style="text-align:center;color:#94a3b8;padding:22px">
            Belum ada data surat.
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <!-- Footer halaman -->
  <div class="foot">Sistem Manajemen Surat - Politeknik Negeri Bandung</div>
@endsection

@section('popup')
  <!-- Popup konfirmasi logout -->
  <div class="popup" id="logoutPopup">
    <div class="popup-content">

      <!-- Judul popup -->
      <h3>Konfirmasi Logout</h3>

      <!-- Pesan konfirmasi -->
      <p>Apakah Anda yakin ingin keluar dari sistem?</p>

      <!-- Tombol-tombol di popup -->
      <div class="popup-btns">

        <!-- Tombol untuk menutup popup tanpa logout -->
        <button class="btn-cancel" id="cancelLogout">Batal</button>

        <!-- Form logout yang mengirim request POST -->
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" class="btn-confirm">Logout</button>
        </form>
      </div>
    </div>
  </div>
@endsection
