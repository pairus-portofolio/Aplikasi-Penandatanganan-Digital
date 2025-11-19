@extends('layouts.app')

@section('title', 'Paraf Surat')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/kaprodi/preview-top.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kaprodi/preview-bottom.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kaprodi/zoom-button.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kaprodi/revision-button.css') }}">
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
        
        {{-- SIDEBAR --}}
        <div class="paraf-sidebar">
            <p style="font-weight: 600; color: #333; margin-top:0; margin-bottom: 10px;">Paraf Tersedia:</p>
            
            <!-- Kotak untuk upload / mengganti / menghapus paraf -->
            <div id="parafBox" class="paraf-template-box">
                <span class="paraf-text">Klik untuk upload</span>

                <!-- Gambar paraf yang tampil setelah upload -->
                <img id="parafImage" class="paraf-image-preview" src="" alt="Paraf" draggable="true">

                <!-- Tombol aksi: ganti & hapus paraf -->
                <div class="paraf-box-actions">
                    <button type="button" class="paraf-action-btn" id="parafGantiBtn" title="Ganti Paraf">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </button>
                    <button type="button" class="paraf-action-btn" id="parafHapusBtn" title="Hapus Paraf">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </div>
            
            <!-- Input tersembunyi untuk upload file gambar paraf -->
            <input type="file" id="parafImageUpload" style="display: none;" accept="image/png">
        </div>

        {{-- PREVIEW AREA --}}
        <div class="paraf-preview-area">
            <div id="scrollContainer">
                <div id="pdf-render-container"></div>
            </div>
        </div>
    </div>
@endsection

@section('popup')
    @include('partials.action-paraf')
    @include('partials.logout-popup')
@endsection

@push('scripts')
    {{-- 1. Library PDF.js --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>

    {{-- 2. JEMBATAN DATA: Mengirim data dari Laravel ke JS Eksternal --}}
    <script>
        // Kita simpan data penting di window object agar bisa dibaca file .js lain
        window.pdfConfig = {
            pdfUrl: "{{ route('document.download', $document->id) }}",
            workerSrc: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js'
        };
    </script>

    {{-- 3. File Logic --}}
    <script src="{{ asset('js/kaprodi/paraf-surat.js') }}"></script>
@endpush