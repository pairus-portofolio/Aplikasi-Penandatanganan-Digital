<!DOCTYPE html>
<html lang="id">
<head>
    <!-- Memuat file CSS utama untuk styling dashboard -->
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">

    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>

    <!-- Judul halaman (dapat dioverride oleh view lain) -->
    <title>@yield('title', 'Dashboard')</title>

    <!-- Ikon FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>

    <!-- Font utama (Inter) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">

</head>

<body>
  <div class="wrap" id="wrap">

    <!-- Sidebar navigasi -->
    @include('partials.sidebar')

    <!-- Container utama bagian konten -->
    <main class="main">
      <!-- Topbar navigasi -->
      @include('partials.topbar')

      <section class="content">
        <div class="inner">
          <!-- Area konten dinamis yang diisi masing-masing halaman -->
          @yield('content')
        </div>
      </section>
    </main>
  </div>

  <!-- Overlay untuk sidebar versi mobile -->
  <div id="overlay"></div>

  <!-- Placeholder untuk popup dari halaman tertentu -->
  @yield('popup')

  <!-- Script utama untuk interaksi dashboard -->
  <script>
    (function(){
      const hb = document.getElementById('hb');            // Tombol hamburger
      const html = document.documentElement;               // Elemen HTML
      const overlay = document.getElementById('overlay');  // Overlay hitam untuk mobile
      
      // Fungsi cek apakah tampilan adalah mode mobile
      const isMobile = () => window.matchMedia('(max-width: 992px)').matches;

      // Event: klik tombol hamburger → toggle sidebar
      hb.addEventListener('click', () => {
        if (isMobile()) document.body.classList.toggle('show-sb'); // Mode mobile
        else html.classList.toggle('collapsed');                   // Mode desktop
      });

      // Event: klik overlay → tutup sidebar mobile
      overlay.addEventListener('click', () => 
        document.body.classList.remove('show-sb')
      );

      // Event: saat browser di-resize → pastikan sidebar mobile tidak nge-bug
      window.addEventListener('resize', () => { 
        if (!isMobile()) document.body.classList.remove('show-sb'); 
      });

      // === Logika Popup Logout ===
      const logoutBtn = document.getElementById('logoutBtn');     // Tombol untuk membuka popup logout
      const popup = document.getElementById('logoutPopup');       // Elemen popup logout
      const cancelBtn = document.getElementById('cancelLogout');  // Tombol untuk menutup popup

      // Event: buka popup logout
      logoutBtn.addEventListener('click', () => popup.classList.add('show'));

      // Event: tutup popup logout
      cancelBtn.addEventListener('click', () => popup.classList.remove('show'));
    })();
  </script>

  <!-- Lokasi untuk menambahkan script tambahan dari view lain -->
  @stack('scripts')
</body>
</html>
