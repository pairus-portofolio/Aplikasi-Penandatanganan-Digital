<!-- Popup konfirmasi untuk logout -->
<div class="popup" id="logoutPopup">

  <!-- Isi utama popup -->
  <div class="popup-content">
    <h3>Konfirmasi Logout</h3>

    <!-- Pesan pertanyaan -->
    <p>Apakah Anda yakin ingin keluar dari sistem?</p>

    <!-- Tombol-tombol aksi pada popup -->
    <div class="popup-btns">

      <!-- Tombol untuk menutup popup tanpa logout -->
      <button class="btn-cancel" id="cancelLogout">Batal</button>

      <!-- Form untuk melakukan logout (POST) -->
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn-confirm">Logout</button>
      </form>

    </div>
  </div>

</div>
