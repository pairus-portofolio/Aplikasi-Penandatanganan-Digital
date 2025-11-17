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

    <!-- Sidebar navigasi -->
    @include('partials.sidebar')

    <!-- Container utama bagian konten -->
    <main class="main">
      <!-- Topbar navigasi -->
      @include('partials.topbar')

      @yield('page-header')

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

  <!-- Script utama untuk interaksi layout -->
  <script src="{{ asset('js/app.js') }}"></script>

  <!-- Lokasi untuk menambahkan script tambahan dari view lain -->
  @stack('scripts')
</body>
</html>
