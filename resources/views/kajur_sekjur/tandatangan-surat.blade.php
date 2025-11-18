@extends('layouts.app')

@section('title', 'Tanda Tangan Surat')

@push('styles')
    {{-- 1. CSS Layout Dasar --}}
    <link rel="stylesheet" href="{{ asset('css/kaprodi/preview-top.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kaprodi/preview-bottom.css') }}">
    
    {{-- 2. CSS Tombol & Zoom --}}
    <link rel="stylesheet" href="{{ asset('css/kaprodi/zoom-button.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kaprodi/revision-button.css') }}"> 
    <link rel="stylesheet" href="{{ asset('css/kaprodi/notif-button.css') }}">

    {{-- 3. CSS Layout Dokumen & Popup --}}
    <link rel="stylesheet" href="{{ asset('css/kaprodi/paraf-layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kaprodi/popup.css') }}?v=3">
    
    {{-- 4. CSS Khusus Halaman Ini (TTD Sidebar & Captcha Baru) --}}
    <link rel="stylesheet" href="{{ asset('css/kajur_sekjur/ttd-sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kajur_sekjur/captcha.css') }}"> 
@endpush

@section('page-header')
    @include('partials.doc-header', [
        'judulSurat'  => 'Pratinjau: Nama Surat',
        'currentPage' => 1,
        'totalPages'  => 5,
    ])
@endsection

@section('content')
    <div class="paraf-layout-container">
        
        <div class="paraf-sidebar">
            <p style="font-weight: 600; color: #333; margin-top:0; margin-bottom: 15px;">Aksi:</p>
            
            <div class="ttd-btn-container">
                <button id="btnTriggerCaptcha" class="ttd-sidebar-btn" title="Tanda Tangan">
                    <i class="fa-solid fa-pen-nib"></i>
                    <span>Tanda<br>Tangan</span>
                </button>
            </div>
        </div>
        
        <div class="paraf-preview-area">
            <div id="previewPage" class="paraf-drop-zone" style="height: 150vh; background: #f0f0f0; border: 1px dashed #ccc; display:flex; align-items:center; justify-content:center; color: #999; transform-origin: top center; position: relative; overflow: hidden;">
                (Area Dokumen Tanda Tangan)
            </div>
        </div>
    </div>
@endsection

@section('popup')
    {{-- 1. Toolbar Bawah --}}
    @include('partials.action-tandatangan')

    {{-- 2. Popup Logout --}}
    @include('partials.logout-popup')

    {{-- 3. POPUP KHUSUS CAPTCHA --}}
    <div class="popup" id="captchaPopup">
        <div class="popup-content">
            
            {{-- Visual Captcha --}}
            <div class="captcha-visual">
                <div class="captcha-scratch"></div>
                <span class="num-pink">29</span> 
                <span class="num-orange">+</span>
                <span class="num-orange">1</span>
                <span class="num-pink">=</span>
            </div>

            {{-- Input Field --}}
            <input type="text" id="inputCaptchaCode" class="captcha-input" placeholder="Enter Captcha">

            {{-- Garis Pemisah --}}
            <div class="captcha-divider"></div>

            {{-- Tombol Kirim --}}
            <button id="prosesCaptcha" class="captcha-btn">Kirim</button>

        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/kajur_sekjur/tandatangan.js') }}"></script>
@endpush