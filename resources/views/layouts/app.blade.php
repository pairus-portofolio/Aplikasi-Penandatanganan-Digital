<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>@yield('title', 'Dashboard')</title>

    <!-- Memuat ikon Font Awesome dan font Inter -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Memuat stylesheet utama layout dashboard -->
    <link rel="stylesheet" href="{{ asset('css/dashboard/base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard/layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard/topbar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard/content.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard/cards.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard/tables.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard/popup.css') }}">

    <!-- Area untuk stylesheet tambahan -->
    @stack('styles')
</head>

<body>

  <!-- Wrapper utama antara sidebar & konten -->
  <div class="wrap" id="wrap">

    <!-- Memuat sidebar navigasi -->
    @include('partials.sidebar')

    <!-- Area konten utama -->
    <main class="main">

      <!-- Memuat topbar dashboard -->
      @include('partials.topbar')

      <!-- Header halaman dinamis -->
      @yield('page-header')

      <!-- Konten halaman -->
      <section class="content">
        <div class="inner">
          @yield('content')
        </div>
      </section>

    </main>
  </div>

  <!-- Overlay untuk mobile sidebar -->
  <div id="overlay"></div>

  <!-- Popup dinamis -->
  @yield('popup')

  <!-- Script utama dashboard -->
  <script src="{{ asset('js/app.js') }}"></script>

  <!-- Area untuk script tambahan -->
  @stack('scripts')
</body>
</html>
