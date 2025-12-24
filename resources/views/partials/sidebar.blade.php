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

      {{-- Define ID untuk perbandingan di Blade --}}
      @php
          $adminId = \App\Enums\RoleEnum::ID_ADMIN;
          $koordinatorId = \App\Enums\RoleEnum::ID_KOORDINATOR_PRODI;
      @endphp

      {{-- Semua user SELAIN Admin bisa lihat Dashboard (Termasuk Dosen & Koordinator) --}}
      @if(Auth::user()->role_id != $adminId)
        <a href="/dashboard" class="{{ Request::is('dashboard') ? 'active' : '' }}">
          <i class="fa-solid fa-chart-line fa-fw"></i><span>Dashboard</span>
        </a>
      @endif

      @if(Auth::user()->role_id == \App\Enums\RoleEnum::ID_TU)
         <a href="/tu/upload" class="{{ Request::is('tu/upload*') ? 'active' : '' }}">
          <i class="fa-solid fa-upload fa-fw"></i><span>Unggah Surat</span>
        </a>
        <a href="{{ route('tu.finalisasi.index') }}" class="{{ Request::is('tu/finalisasi*') ? 'active' : '' }}">
            <i class="fa-solid fa-file-signature fa-fw"></i><span>Finalisasi Surat</span>
        </a>
        <a href="{{ route('tu.arsip.index') }}" class="{{ Request::is('tu/arsip*') ? 'active' : '' }}">
            <i class="fa-solid fa-archive fa-fw"></i><span>Arsip Surat</span>
        </a>
      @endif

      @if(Auth::user()->role_id == $koordinatorId)
        <a href="{{ route('kaprodi.review.index') }}" class="{{ Request::is('review-surat*') ? 'active' : '' }}">
          <i class="fa-regular fa-file-lines"></i> Review Surat
        </a>

        <a href="{{ route('kaprodi.paraf.index') }}" class="{{ Request::is('paraf-surat*') ? 'active' : '' }}">
          <i class="fa-solid fa-stamp"></i> Paraf Surat
        </a>
      @endif

      @if(in_array(Auth::user()->role_id, [\App\Enums\RoleEnum::ID_KAJUR, \App\Enums\RoleEnum::ID_SEKJUR]))
        <a href="{{ route('kajur.tandatangan.index') }}" class="{{ Request::is('tandatangan-surat*') ? 'active' : '' }}">
          <i class="fa-solid fa-signature fa-fw"></i><span>Tanda Tangan Surat</span>
        </a>
      @endif

      {{-- Menu khusus role ADMIN --}}
      @if(Auth::user()->role_id == $adminId)
        <a href="{{ route('admin.users.index') }}" class="{{ Request::is('admin/users*') ? 'active' : '' }}">
          <i class="fa-solid fa-users-gear fa-fw"></i><span>Manajemen Pengguna</span>
        </a>
      @endif

    </nav>
  </div>

  <!-- Bagian footer sidebar: tombol logout -->
  <div class="sb-foot">
      <!-- Trigger modal via ModalManager (data-modal="logout") -->
      <button type="button" class="logout-btn" data-modal="logout" data-modal-title="Konfirmasi Logout">
        <i class="fa-solid fa-arrow-right-from-bracket fa-fw"></i>
        <span>Logout</span>
      </button>
  </div>
</aside>