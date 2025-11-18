<!-- Sidebar utama aplikasi -->
<aside class="sidebar">
  <div>

    <!-- Bagian header: logo aplikasi + role pengguna -->
    <div class="sb-head">
      <div class="logo">
        <img src="{{ asset('images/logottd.png') }}" alt="Logo">
      </div>

      <!-- Menampilkan role user yang sedang login -->
      <div class="brand">{{ Auth::user()->role->nama_role ?? 'User' }}</div>
    </div>

    <!-- Menu navigasi utama -->
    <nav class="menu">

      <!-- Link dashboard (selalu tersedia untuk semua role) -->
      <a href="/dashboard" class="{{ Request::is('dashboard') ? 'active' : '' }}">
        <i class="fa-solid fa-chart-line fa-fw"></i><span>Dashboard</span>
      </a>

      <!-- Menu khusus role TU -->
      @if(Auth::user()->role_id == 1)
        <!-- Halaman upload surat -->
        <a href="/tu/upload" class="{{ Request::is('tu/upload*') ? 'active' : '' }}">
          <i class="fa-solid fa-upload fa-fw"></i><span>Unggah Surat</span>
        </a>

        <!-- Halaman finalisasi surat -->
        <a href="#" class="{{ Request::is('finalisasi') ? 'active' : '' }}">
          <i class="fa-solid fa-file-signature fa-fw"></i><span>Finalisasi Surat</span>
        </a>
      @endif

      <!-- Menu khusus Kaprodi D3 & D4 -->
      @if(in_array(Auth::user()->role_id, [2, 3]))
        <!-- Halaman untuk review surat -->
        <a href="{{ route('kaprodi.review') }}" class="{{ Request::is('review-surat') ? 'active' : '' }}">
          <i class="fa-regular fa-file-lines"></i> Review Surat
        </a>

        <!-- Halaman untuk melakukan paraf -->
        <a href="{{ route('kaprodi.paraf') }}" class="{{ Request::is('paraf-surat') ? 'active' : '' }}">
          <i class="fa-solid fa-stamp"></i> Paraf Surat
        </a>
      @endif

      <!-- Menu khusus Kajur & Sekjur -->
      @if(in_array(Auth::user()->role_id, [4, 5]))
        <!-- Halaman tanda tangan surat -->
        <a href="{{ route('kajur.tandatangan') }}" class="{{ Request::is('tandatangan-surat') ? 'active' : '' }}">
          <i class="fa-solid fa-signature fa-fw"></i><span>Tanda Tangan Surat</span>
        </a>
      @endif

    </nav>
  </div>

  <!-- Bagian footer sidebar: tombol logout -->
  <div class="sb-foot">
      <!-- Trigger popup logout -->
      <button type="button" id="logoutBtn" class="logout-btn">
        <i class="fa-solid fa-arrow-right-from-bracket fa-fw"></i>
        <span>Logout</span>
      </button>
  </div>
</aside>
