<!-- Topbar utama yang berisi tombol sidebar dan informasi pengguna -->
<header class="topbar">
  <div class="tleft">

    <!-- Tombol untuk membuka/menutup sidebar -->
    <button id="hb" class="hamb" aria-label="Toggle sidebar">
      <i class="fa-solid fa-bars"></i>
    </button>

    <!-- Menampilkan judul dashboard sesuai role pengguna -->
    <h2 style="font-size:20px;font-weight:700;margin:0;">
      @php
        $roleId = Auth::user()->role_id ?? null;
      @endphp

      @switch($roleId)
          @case(1)
              Dashboard Tata Usaha
              @break

          @case(2)
              Dashboard Kaprodi D3
              @break

          @case(3)
              Dashboard Kaprodi D4
              @break

          @case(4)
              Dashboard Kajur
              @break

          @case(5)
              Dashboard Sekjur
              @break

          @default
              Dashboard
      @endswitch
    </h2>
  </div>

  <!-- Bagian kanan topbar yang menampilkan nama & avatar user -->
  <div class="user-wrap">
    <span>{{ Auth::user()->nama_lengkap ?? 'Nama Pengguna' }}</span>

    <!-- Avatar user (ikon profil) -->
    <button class="avatar" title="{{ Auth::user()->role->nama_role ?? '' }}">
      <i class="fa-solid fa-user"></i>
    </button>
  </div>
</header>
