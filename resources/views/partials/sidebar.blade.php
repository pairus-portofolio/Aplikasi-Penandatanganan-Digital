<aside class="sidebar">
  <div>
    <div class="sb-head">
      <div class="logo">
        <img src="{{ asset('images/logottd.png') }}" alt="Logo">
      </div>
      <div class="brand">{{ Auth::user()->role->nama_role ?? 'User' }}</div>
    </div>

    <nav class="menu">
      <!-- {{-- Menu umum untuk semua role --}} -->
      <a href="/dashboard" class="{{ Request::is('dashboard') ? 'active' : '' }}">
        <i class="fa-solid fa-chart-line fa-fw"></i><span>Dashboard</span>
      </a>

      <!-- {{-- Role TU --}} -->
      @if(Auth::user()->role_id == 1)
        <a href="#" class="{{ Request::is('unggah') ? 'active' : '' }}">
          <i class="fa-solid fa-upload fa-fw"></i><span>Unggah Surat</span>
        </a>
        <a href="#" class="{{ Request::is('finalisasi') ? 'active' : '' }}">
          <i class="fa-solid fa-file-signature fa-fw"></i><span>Finalisasi Surat</span>
        </a>
      @endif

      <!-- {{-- Role Kaprodi D3 & D4 --}} -->
      @if (Auth::check() && in_array(Auth::user()->role->nama_role, ['Kaprodi D3', 'Kaprodi D4']))
          <a href="{{ route('kaprodi.review') }}" class="{{ Request::is('review-surat') ? 'active' : '' }}">
              <i class="fa-regular fa-file-lines"></i> Review Surat
          </a>
          <a href="{{ route('kaprodi.paraf') }}" class="{{ Request::is('paraf-surat') ? 'active' : '' }}">
              <i class="fa-solid fa-stamp"></i> Paraf Surat
          </a>
      @endif

      <!-- {{-- Role Kajur & Sekjur --}} -->
      @if(in_array(Auth::user()->role_id, [4, 5]))
        <a href="#" class="{{ Request::is('tandatangan') ? 'active' : '' }}">
          <i class="fa-solid fa-signature fa-fw"></i><span>Tanda Tangan Surat</span>
        </a>
      @endif
    </nav>
  </div>

  <div class="sb-foot">
      <button type="button" id="logoutBtn">
        <i class="fa-solid fa-arrow-right-from-bracket fa-fw"></i><span>Logout</span>
      </button>
    </div>
</aside>
