<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>@yield('title', 'Dashboard')</title>

  <!-- Icons & Font -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">

  <!-- CSS utama -->
  <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
</head>

<body>
  <div class="wrap" id="wrap">
    <!-- Sidebar -->
    @include('partials.sidebar')

    <!-- Main content -->
    <main class="main">
      @include('partials.topbar')
      <section class="content">
        <div class="inner">
          @yield('content')
        </div>
      </section>
    </main>
  </div>

  <div id="overlay"></div>
  @yield('popup')

  <!-- JS interaktif -->
  <script>
    (function(){
      const hb = document.getElementById('hb');
      const html = document.documentElement;
      const overlay = document.getElementById('overlay');
      const isMobile = () => window.matchMedia('(max-width: 992px)').matches;

      hb.addEventListener('click', () => {
        if (isMobile()) document.body.classList.toggle('show-sb');
        else html.classList.toggle('collapsed');
      });
      overlay.addEventListener('click', () => document.body.classList.remove('show-sb'));
      window.addEventListener('resize', () => { if (!isMobile()) document.body.classList.remove('show-sb'); });

      const logoutBtn = document.getElementById('logoutBtn');
      const popup = document.getElementById('logoutPopup');
      const cancelBtn = document.getElementById('cancelLogout');
      logoutBtn.addEventListener('click', () => popup.classList.add('show'));
      cancelBtn.addEventListener('click', () => popup.classList.remove('show'));
    })();
  </script>
</body>
</html>
