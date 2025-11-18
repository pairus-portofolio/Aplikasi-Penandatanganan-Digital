<!-- Popup konfirmasi sebelum user melakukan logout -->
<div class="popup" id="logoutPopup">
  <div class="popup-content">
    <h3>Konfirmasi Logout</h3>

    <!-- Pesan konfirmasi untuk user -->
    <p>Apakah Anda yakin ingin keluar dari sistem?</p>

    <div class="popup-btns">
      <!-- Tombol untuk menutup popup tanpa logout -->
      <button class="btn-cancel" id="cancelLogout">Batal</button>

      <!-- Form untuk mengirim request logout -->
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn-confirm">Logout</button>
      </form>
    </div>
  </div>
</div>
