<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Dashboard')</title>

  <!-- Memuat ikon Font Awesome dan font Inter -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Memuat stylesheet utama layout dashboard -->
  <link rel="stylesheet" href="{{ asset('css/dashboard/base.css') }}">
  <link rel="stylesheet" href="{{ asset('css/dashboard/layout.css?v=4') }}">
  <link rel="stylesheet" href="{{ asset('css/dashboard/sidebar.css') }}">
  <link rel="stylesheet" href="{{ asset('css/dashboard/topbar.css?v=4') }}">
  <link rel="stylesheet" href="{{ asset('css/dashboard/content.css?v=4') }}">
  <link rel="stylesheet" href="{{ asset('css/dashboard/cards.css') }}">
  <link rel="stylesheet" href="{{ asset('css/dashboard/tables.css') }}">
  <link rel="stylesheet" href="{{ asset('css/dashboard/pagination.css') }}">

  <!-- Area untuk stylesheet tambahan -->
  @stack('styles')
</head>

<body>

  <!-- Wrapper utama antara sidebar & konten -->
  <div class="wrap" id="wrap">

    <!-- Memuat sidebar navigasi -->
    @include('partials.sidebar')

    <!-- Area konten utama -->
    <main class="main @yield('main-class')">

      <!-- Sticky Wrapper untuk Topbar dan Page Header -->
      <div class="sticky-wrapper" style="position: sticky; top: 0; z-index: 500;">
        <!-- Memuat topbar dashboard -->
        @include('partials.topbar')

        <!-- Header halaman dinamis -->
        @yield('page-header')
      </div>

      <!-- Konten halaman -->
      <section class="content @yield('content-class')">
        <div class="inner">
          @yield('content')
        </div>
      </section>

    </main>
  </div>

  <!-- Overlay untuk mobile sidebar -->
  <div id="overlay"></div>

  <!-- Global modal container for dynamic Bootstrap modals -->
  <div id="globalModal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
      <div class="modal-content" style="border-radius: 8px;">
        <div class="modal-header" style="border-bottom: 1px solid #e9ecef;">
          <h6 class="modal-title" id="globalModalTitle" style="font-weight: 600; color: #1a1a1a;">Informasi</h6>
          <button type="button" id="globalModalCloseBtn" class="btn-close" data-bs-dismiss="modal"
            aria-label="Close"></button>
        </div>
        <div class="modal-body" id="globalModalBody" style="padding: 16px; color: #333; font-size: 0.9rem;"></div>
        <div class="modal-footer" id="globalModalFooter"
          style="border-top: 1px solid #e9ecef; padding: 12px; gap: 8px;"></div>
      </div>
    </div>
  </div>

  <!-- Legacy per-page popups (keystroke for backward compatibility). Prefer using ModalManager. -->
  @yield('popup')

  <!-- Script utama dashboard -->
  <script src="{{ asset('js/app.js') }}"></script>

  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- Notifikasi Global -->
  @if(session('success'))
    <script>
      document.addEventListener("DOMContentLoaded", function () {
        Swal.fire({
          toast: true,
          icon: 'success',
          title: '{{ session('success') }}',
          position: 'top-end',
          timer: 2500,
          showConfirmButton: false,
        });
      });
    </script>
  @endif

  @if ($errors->any())
    <script>
      document.addEventListener("DOMContentLoaded", function () {
        Swal.fire({
          icon: 'error',
          title: 'Terjadi Kesalahan',
          html: `{!! implode('<br>', $errors->all()) !!}`,
        });
      });
    </script>
  @endif

  <!-- Area untuk script tambahan -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="{{ asset('js/modal-manager.js') }}"></script>
  @stack('scripts')
</body>

</html>