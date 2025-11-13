<header class="topbar">
  <div class="tleft">
    <button id="hb" class="hamb" aria-label="Toggle sidebar">
      <i class="fa-solid fa-bars"></i>
    </button>

    <!-- {{-- Judul topbar berdasarkan role --}} -->
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

  <div class="user-wrap">
    <!-- {{-- Menampilkan nama pengguna yang sedang login --}} -->
    <span>{{ Auth::user()->nama_lengkap ?? 'Nama Pengguna' }}</span>

    {{-- Avatar/icon user --}}
    <button class="avatar" title="{{ Auth::user()->role->nama_role ?? '' }}">
      <i class="fa-solid fa-user"></i>
    </button>
  </div>
</header>
