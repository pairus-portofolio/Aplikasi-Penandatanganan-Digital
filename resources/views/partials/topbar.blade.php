<header class="topbar">
  <div class="tleft">

    <button id="hb" class="hamb" aria-label="Toggle sidebar">
      <i class="fa-solid fa-bars"></i>
    </button>

    <h2 style="font-size:20px;font-weight:700;margin:0;">
      @php
        $roleId = Auth::user()->role_id ?? null;
        // Ambil ID dari Enum agar lebih aman (opsional, tapi disarankan)
        // atau pakai angka langsung sesuai database.
      @endphp

      @switch($roleId)
          @case(1)
              Dashboard Tata Usaha
              @break

          @case(2)
              {{-- ID 2 sekarang adalah Koordinator Program Studi --}}
              Dashboard Koordinator Program Studi
              @break

          @case(3)
              {{-- ID 3 sekarang adalah Dosen --}}
              Dashboard Dosen
              @break

          @case(4)
              Dashboard Ketua Jurusan
              @break

          @case(5)
              Dashboard Sekretaris Jurusan
              @break

          @case(6) 
              Dashboard Admin
              @break

          @default
              Dashboard
      @endswitch
    </h2>
  </div>

  <div class="user-wrap">
    <span>{{ Auth::user()->nama_lengkap ?? 'Nama Pengguna' }}</span>

    <button class="avatar" title="{{ Auth::user()->role->nama_role ?? '' }}">
      <i class="fa-solid fa-user"></i>
    </button>
  </div>
</header>