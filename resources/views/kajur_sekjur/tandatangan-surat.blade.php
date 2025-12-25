@extends('layouts.app')

@section('title', 'Tanda Tangan Surat')

@push('styles')
    <!-- Load seluruh file CSS yang dibutuhkan halaman ini -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('css/kaprodi/preview-top.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kaprodi/preview-bottom.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kaprodi/zoom-button.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kaprodi/revision-button.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kaprodi/notif-button.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kaprodi/paraf-layout.css') }}">
    <!-- CSS Paraf kita pakai ulang untuk styling box upload -->
    <link rel="stylesheet" href="{{ asset('css/kaprodi/paraf.css') }}">
@endpush

@section('page-header')
    @include('partials.doc-header', [
        'judulSurat'  => 'Pratinjau: ' . $document->judul_surat,
        'currentPage' => 1,
        'totalPages'  => 1,
    ])
@endsection

@section('content')
    <!-- Layout utama: sidebar aksi + area preview dokumen -->
    <div class="paraf-layout-container">
        
        <!-- Sidebar tombol aksi TTD (Hanya tampil jika bukan View Only) -->
        @if(!isset($isViewOnly) || !$isViewOnly)
        <div class="paraf-sidebar">
            <p style="font-weight: 600; color: #333; margin-top:0; margin-bottom: 10px;">
                Tanda Tangan Tersedia:
            </p>

            @php
                $path = Auth::user()->img_ttd_path;
                $adaTtd = !empty($path);
                $urlTtd = $adaTtd ? asset('storage/' . $path) : '';
            @endphp

            <div id="parafBox" class="paraf-template-box {{ $adaTtd ? 'has-image' : '' }}">
                <span class="paraf-text">Klik untuk upload</span>

                <img id="parafImage"
                     class="paraf-image-preview"
                     src="{{ $urlTtd }}"
                     alt="Tanda Tangan"
                     draggable="true">

                <div class="paraf-box-actions">
                    <button type="button" class="paraf-action-btn" id="parafGantiBtn" title="Ganti TTD">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </button>

                    <button type="button" class="paraf-action-btn" id="parafHapusBtn" title="Hapus TTD">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </div>

            <div class="paraf-resize-control" style="margin-top: 15px; padding: 10px; background: #f8f9fa; border-radius: 8px; border: 1px solid #e5e7eb;">
                <label for="ttdSize" style="font-size: 0.9rem; font-weight: 600; color: #333; display:flex; justify-content:space-between; margin-bottom:5px;">
                    <span>Ukuran:</span>
                    <span id="sizeLabel">100px</span>
                </label>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fa-solid fa-image" style="font-size: 0.8rem; color: #666;"></i>
                    <input type="range" id="ttdSize" min="25" max="200" value="100" style="width: 100%; cursor: pointer;">
                    <i class="fa-solid fa-image" style="font-size: 1.2rem; color: #666;"></i>
                </div>
            </div>

            <input type="file"
                   id="parafImageUpload"
                   style="display: none;"
                   accept="image/png, image/jpeg, image/jpg">
        </div>
        @endif
        
        <!-- Area utama untuk menampilkan dokumen yang akan ditandatangani -->
        <div class="paraf-preview-area" @if(isset($isViewOnly) && $isViewOnly) style="width: 100%;" @endif>
            <div id="scrollContainer">
                <div id="pdf-render-container"></div>
            </div>
        </div>
    </div>
@endsection

@section('popup')
    <!-- Bootstrap Modal untuk Captcha - Transparan, hanya robot -->
    <div class="modal fade" id="captchaModal" tabindex="-1" aria-hidden="true"
        data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered" style="pointer-events: none; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; max-width: 100%;">
            <div style="pointer-events: auto; background: transparent; border: none; box-shadow: none;">
                <div id="captchaBox">
                    <div class="g-recaptcha" data-sitekey="{{ env('RECAPTCHA_SITE_KEY') }}"
                        data-callback="onCaptchaSuccess">
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    @include('partials.action-tandatangan')
@endsection

@push('scripts')
    {{-- 1. Load Library PDF.js (Wajib) --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
    
    {{-- 2. Konfigurasi Data dari Laravel ke JS --}}
    <script>
        window.reviewConfig = {
            pdfUrl: "{{ route('document.download', $document->id) }}",
            workerSrc: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js',
            saveUrl: "{{ route('kajur.tandatangan.save', $document->id) }}",
            deleteUrl: "{{ route('kajur.tandatangan.delete') }}",
            csrfToken: "{{ csrf_token() }}",
            savedSignature: @json($savedSignature ?? null)
        };
        window.suratId = "{{ $document->id }}";
    </script>

    {{-- 3. Panggil Script yang dibuat --}}
    <script src="{{ asset('js/pdf-signer.js') }}"></script>
    <script src="{{ asset('js/kajur_sekjur/tandatangan.js') }}"></script>
    <script src="{{ asset('js/kajur_sekjur/captcha.js') }}"></script>

    {{-- 4. Load Google reCAPTCHA --}}
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>

    <script>
        @if(session('popup'))
            document.addEventListener("DOMContentLoaded", function () {
                document.getElementById("ttdNotifPopup").classList.add("show");
            });
        @endif
    </script>

@endpush
