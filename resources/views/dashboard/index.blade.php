@extends('layouts.app')
@section('title', 'Dashboard TU')

@section('content')

  <!-- Judul halaman -->
  <h1 class="page-title">Dashboard</h1>

  <!-- Pesan sambutan berdasarkan session user -->
  <p class="welcome">
    Selamat datang <strong>{{ session('user_name') ?? 'Nama Pengguna' }}</strong>!
  </p>

  <!-- Menampilkan kartu ringkasan (jumlah surat berdasarkan role) -->
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

        <!-- Loop daftar surat (gunakan dummy jika data masih kosong) -->
        @forelse(($daftarSurat ?? [
          ['nomor'=>'B/6756/UN31.FST/PK.01.00/2025','nama'=>'Nama surat','tanggal'=>'26/08/2025','status'=>'Ditinjau','status_class'=>'kuning'],
          ['nomor'=>'B/6756/UN31.FST/PK.01.00/2025','nama'=>'Nama surat','tanggal'=>'07/11/2024','status'=>'Perlu Revisi','status_class'=>'merah'],
          ['nomor'=>'B/6756/UN31.FST/PK.01.00/2025','nama'=>'Nama surat','tanggal'=>'19/01/2025','status'=>'Diparaf','status_class'=>'biru'],
          ['nomor'=>'B/6756/UN31.FST/PK.01.00/2025','nama'=>'Nama surat','tanggal'=>'17/03/2025','status'=>'Ditandatangani','status_class'=>'hijau'],
        ]) as $s)

        <!-- Baris data surat -->
        <tr>
          <td>{{ $s['nomor'] }}</td>
          <td>{{ $s['nama'] }}</td>
          <td>{{ $s['tanggal'] }}</td>

          <!-- Badge status sesuai warna -->
          <td>
            <span class="pill {{ $s['status_class'] }}">
              {{ $s['status'] }}
            </span>
          </td>

          <!-- Tombol aksi lihat detail -->
          <td><a class="aksi" href="#">Lihat</a></td>
        </tr>

        @empty
        <!-- Pesan jika tidak ada surat -->
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

      <!-- Pesan popup -->
      <p>Apakah Anda yakin ingin keluar dari sistem?</p>

      <!-- Tombol-tombol aksi popup -->
      <div class="popup-btns">

        <!-- Menutup popup tanpa logout -->
        <button class="btn-cancel" id="cancelLogout">Batal</button>

        <!-- Form logout -->
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" class="btn-confirm">Logout</button>
        </form>

      </div>
    </div>
  </div>
@endsection
