<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>@yield('title', 'Dashboard')</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('css/dashboard/base.css') }}">
    
    <link rel="stylesheet" href="{{ asset('css/dashboard/layout.css') }}">
    
    <link rel="stylesheet" href="{{ asset('css/dashboard/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard/topbar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard/content.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard/cards.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard/tables.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard/popup.css') }}">
    @stack('styles')
</head>

<body>
  <div class="wrap" id="wrap">
    @include('partials.sidebar')

    <main class="main">
      @include('partials.topbar')

      @yield('page-header')

      <section class="content">
        <div class="inner">
          @yield('content')
        </div>
      </section>
    </main>
  </div>

  <div id="overlay"></div>
  
  @yield('popup')

  <script>
    (function(){
      const hb = document.getElementById('hb');
      const html = document.documentElement;
      const overlay = document.getElementById('overlay');
      const isMobile = () => window.matchMedia('(max-width: 992px)').matches;

      if(hb) {
        hb.addEventListener('click', () => {
          if (isMobile()) document.body.classList.toggle('show-sb');
          else html.classList.toggle('collapsed');
        });
      }
      if(overlay) {
        overlay.addEventListener('click', () => document.body.classList.remove('show-sb'));
      }
      window.addEventListener('resize', () => { if (!isMobile()) document.body.classList.remove('show-sb'); });

      const logoutBtn = document.getElementById('logoutBtn');
      const popup = document.getElementById('logoutPopup');
      const cancelBtn = document.getElementById('cancelLogout');
      
      if(logoutBtn && popup && cancelBtn) {
        logoutBtn.addEventListener('click', () => popup.classList.add('show'));
        cancelBtn.addEventListener('click', () => popup.classList.remove('show'));
      }
    })();
  </script>
  
  @yield('scripts')

</body>
</html>