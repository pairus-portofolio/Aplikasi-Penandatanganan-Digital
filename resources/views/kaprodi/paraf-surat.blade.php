@extends('layouts.app')

@section('title', 'Paraf Surat')

@push('styles')
    <!-- Load seluruh file CSS khusus halaman paraf -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('css/kaprodi/preview-top.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kaprodi/preview-bottom.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kaprodi/zoom-button.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kaprodi/revision-button.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kaprodi/paraf.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kaprodi/paraf-layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kaprodi/notif-button.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kaprodi/popup.css') }}">
@endpush

@section('page-header')
    @include('partials.doc-header', [
        'judulSurat'  => 'Pratinjau: ' . $document->judul_surat,
        'currentPage' => 1,
        'totalPages'  => 1,
    ])
@endsection

@section('content')
    <div class="paraf-layout-container">

        <!-- ========================= -->
        <!--      PARAF SIDEBAR        -->
        <!-- ========================= -->
        <div class="paraf-sidebar">

            <p style="font-weight: 600; color: #333; margin-top:0; margin-bottom: 10px;">
                Paraf Tersedia:
            </p>

            @php
                $path = Auth::user()->img_paraf_path;
                $adaParaf = !empty($path);
                $urlParaf = $adaParaf ? asset('storage/' . $path) : '';
            @endphp

            <div id="parafBox" class="paraf-template-box {{ $adaParaf ? 'has-image' : '' }}">
                <span class="paraf-text">Klik untuk upload</span>

                <!-- Gambar paraf -->
                <img id="parafImage"
                     class="paraf-image-preview"
                     src="{{ $urlParaf }}"
                     alt="Paraf"
                     draggable="true">

                <!-- Tombol aksi -->
                <div class="paraf-box-actions">
                    <button type="button" class="paraf-action-btn" id="parafGantiBtn" title="Ganti Paraf">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </button>

                    <button type="button" class="paraf-action-btn" id="parafHapusBtn" title="Hapus Paraf">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </div>

            <!-- Input tersembunyi -->
            <input type="file"
                   id="parafImageUpload"
                   style="display: none;"
                   accept="image/png, image/jpeg, image/jpg">
        </div>

        <!-- ========================= -->
        <!--      PREVIEW DOKUMEN     -->
        <!-- ========================= -->
        <div class="paraf-preview-area">
            <div id="scrollContainer">
                <div id="pdf-render-container"></div>
            </div>
        </div>

    </div>
@endsection

@section('popup')
    @include('partials.shared.action-submit', [
        'actionUrl'      => route('kaprodi.paraf.submit', $document->id),
        'modalId'        => 'parafNotifPopup',
        'inputIdPrefix'  => 'paraf',
        'defaultSubject' => 'Dokumen Selesai Diparaf',
        'cancelBtnId'    => 'batalKirim',
        'confirmBtnId'   => 'konfirmasiKirim'
    ])
    @include('partials.logout-popup')
@endsection

@push('scripts')

    <!-- PDF.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>

    <!-- Bridge Data -->
    <script>
        window.pdfConfig = {
            pdfUrl: "{{ route('document.download', $document->id) }}",
            workerSrc: "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js",
            // TAMBAHAN BARU: URL untuk simpan posisi & Token keamanan
            saveUrl: "{{ route('kaprodi.paraf.save', $document->id) }}",
            csrfToken: "{{ csrf_token() }}",
            savedParaf: @json($savedParaf ?? null)
        };
    </script>

    <script>
        window.suratId = "{{ $document->id }}";
    </script>

    {{-- 3. Panggil Script yang dibuat --}}
    <script src="{{ asset('js/pdf-signer.js') }}"></script>
    <!-- Script utama -->
    <script src="{{ asset('js/kaprodi/paraf-surat.js') }}"></script>

    <script>
        @if(session('popup'))
            document.addEventListener("DOMContentLoaded", function () {
                document.getElementById("parafNotifPopup").classList.add("show");
            });
        @endif
    </script>

@endpush
