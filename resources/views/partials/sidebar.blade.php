<!-- Sidebar navigasi utama -->
<aside class="sidebar">
  <div>

    <!-- Bagian header sidebar: logo + nama role -->
    <div class="sb-head">
      <div class="logo">
        <!-- Logo aplikasi -->
        <img src="{{ asset('images/logottd.png') }}" alt="Logo">
      </div>

      <!-- Menampilkan nama role user -->
      <div class="brand">{{ Auth::user()->role->nama_role ?? 'User' }}</div>
    </div>

    <!-- Menu navigasi -->
    <nav class="menu">

      <!-- Menu umum untuk semua role -->
      <a href="/dashboard" class="{{ Request::is('dashboard') ? 'active' : '' }}">
        <i class="fa-solid fa-chart-line fa-fw"></i><span>Dashboard</span>
      </a>

      <!-- Menu khusus untuk role TU -->
      @if(Auth::user()->role_id == 1)
        <a href="/Tu/upload" class="{{ Request::is('Tu/upload*') ? 'active' : '' }}">
          <i class="fa-solid fa-upload fa-fw"></i><span>Unggah Surat</span>
        </a>

        <a href="#" class="{{ Request::is('finalisasi') ? 'active' : '' }}">
          <i class="fa-solid fa-file-signature fa-fw"></i><span>Finalisasi Surat</span>
        </a>
      @endif


      <!-- Menu untuk Kaprodi D3 & Kaprodi D4 -->
      @if(in_array(Auth::user()->role_id, [2, 3]))
          <a href="{{ route('kaprodi.review') }}" class="{{ Request::is('review-surat') ? 'active' : '' }}">
              <i class="fa-regular fa-file-lines"></i> Review Surat
          </a>
          <a href="{{ route('kaprodi.paraf') }}" class="{{ Request::is('paraf-surat') ? 'active' : '' }}">
              <i class="fa-solid fa-stamp"></i> Paraf Surat
          </a>
      @endif

      <!-- Menu untuk Kajur & Sekjur -->
      @if(in_array(Auth::user()->role_id, [4, 5]))
        <a href="#" class="{{ Request::is('tandatangan') ? 'active' : '' }}">
          <i class="fa-solid fa-signature fa-fw"></i><span>Tanda Tangan Surat</span>
        </a>
      @endif

    </nav>
  </div>

  <!-- Bagian footer sidebar: tombol logout -->
  <div class="sb-foot">
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="logout-btn">
          <i class="fa-solid fa-arrow-right-from-bracket fa-fw"></i><span>Logout</span>
        </button>
      </form>
  </div>
</aside>
