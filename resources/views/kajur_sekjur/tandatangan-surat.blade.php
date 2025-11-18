@extends('layouts.app')

@section('title', 'Tanda Tangan Surat')

@push('styles')
    <!-- Load seluruh file CSS yang dibutuhkan halaman ini -->
    <link rel="stylesheet" href="{{ asset('css/kaprodi/preview-top.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kaprodi/preview-bottom.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kaprodi/zoom-button.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kaprodi/revision-button.css') }}"> 
    <link rel="stylesheet" href="{{ asset('css/kaprodi/notif-button.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kaprodi/paraf-layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kaprodi/popup.css') }}?v=3">
    <link rel="stylesheet" href="{{ asset('css/kajur_sekjur/ttd-sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kajur_sekjur/captcha.css') }}">
@endpush

@section('page-header')
    <!-- Header dokumen (judul + pagination halaman) -->
    @include('partials.doc-header', [
        'judulSurat'  => 'Pratinjau: Nama Surat',
        'currentPage' => 1,
        'totalPages'  => 5,
    ])
@endsection

@section('content')
    <!-- Layout utama: sidebar aksi + area preview dokumen -->
    <div class="paraf-layout-container">
        
        <!-- Sidebar tombol aksi TTD -->
        <div class="paraf-sidebar">
            <p style="font-weight: 600; color: #333; margin-top:0; margin-bottom: 15px;">Aksi:</p>
            
            <div class="ttd-btn-container">
                <!-- Tombol membuka popup CAPTCHA -->
                <button id="btnTriggerCaptcha" class="ttd-sidebar-btn" title="Tanda Tangan">
                    <i class="fa-solid fa-pen-nib"></i>
                    <span>Tanda<br>Tangan</span>
                </button>
            </div>
        </div>
        
        <!-- Area utama untuk menampilkan dokumen yang akan ditandatangani -->
        <div class="paraf-preview-area">
            <div id="previewPage" 
                 class="paraf-drop-zone"
                 style="height: 150vh; background: #f0f0f0; border: 1px dashed #ccc; display:flex; align-items:center; justify-content:center; color: #999; transform-origin: top center; position: relative; overflow: hidden;">
                (Area Dokumen Tanda Tangan)
            </div>
        </div>
    </div>
@endsection

@section('popup')

    <!-- Toolbar bawah halaman (Zoom, navigasi, dll) -->
    @include('partials.action-tandatangan')

    <!-- Popup konfirmasi logout -->
    @include('partials.logout-popup')

    <!-- Popup CAPTCHA sebelum tanda tangan -->
    <div class="popup" id="captchaPopup">
        <div class="popup-content">

            <!-- Tampilan CAPTCHA visual -->
            <div class="captcha-visual">
                <div class="captcha-scratch"></div>
                <span class="num-pink">29</span> 
                <span class="num-orange">+</span>
                <span class="num-orange">1</span>
                <span class="num-pink">=</span>
            </div>

            <!-- Input jawaban CAPTCHA -->
            <input type="text" id="inputCaptchaCode" class="captcha-input" placeholder="Enter Captcha">

            <!-- Garis pemisah -->
            <div class="captcha-divider"></div>

            <!-- Tombol kirim verifikasi -->
            <button id="prosesCaptcha" class="captcha-btn">Kirim</button>
        </div>
    </div>
@endsection

@push('scripts')
    <!-- Script utama halaman tanda tangan (drag-drop, captcha, zoom, dll) -->
    <script src="{{ asset('js/kajur_sekjur/tandatangan.js') }}"></script>
@endpush
