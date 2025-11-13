<div class="popup" id="logoutPopup">
  <div class="popup-content">
    <h3>Konfirmasi Logout</h3>
    <p>Apakah Anda yakin ingin keluar dari sistem?</p>
    <div class="popup-btns">
      <button class="btn-cancel" id="cancelLogout">Batal</button>
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn-confirm">Logout</button>
      </form>
    </div>
  </div>
</div>
