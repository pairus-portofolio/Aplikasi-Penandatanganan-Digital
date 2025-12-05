@extends('layouts.app')

@section('title', 'Lihat Dokumen')

@push('styles')
    {{-- CSS Shared View --}}
    <link rel="stylesheet" href="{{ asset('css/kaprodi/preview-top.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kaprodi/preview-bottom.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kaprodi/zoom-button.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kaprodi/revision-button.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kaprodi/notif-button.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kaprodi/preview-layout.css') }}">
@endpush

@section('page-header')
    @include('partials.doc-header', [
        'judulSurat'  => 'Pratinjau: ' . $document->judul_surat,
        'currentPage' => 1,
        'totalPages'  => 1, 
    ])
@endsection

@section('content')
    <div class="review-preview-area">
        
        {{-- Container Scroll --}}
        <div id="scrollContainer">
            <div id="pdf-render-container"></div>
        </div>
    </div>
@endsection

@section('popup')
    @if(isset($showRevisionButton) && $showRevisionButton)
        {{-- Jika tombol revisi aktif (Kaprodi & Giliran Aktif) --}}
        @include('partials.action-review')
    @else
        {{-- Jika View Only murni (Hanya Zoom) --}}
        <div class="pv-controls">
            @include('partials.shared.zoom-controls')
        </div>
    @endif

    
@endsection

@push('scripts')
    {{-- 1. Load Library PDF.js (Wajib) --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
    
    {{-- 2. Konfigurasi Data dari Laravel ke JS --}}
    <script>
        window.reviewConfig = {
            pdfUrl: "{{ route('document.download', $document->id) }}",
            workerSrc: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js'
        };
    </script>

    {{-- 3. Panggil Script Eksternal (Reuse script review-surat.js karena fungsinya sama: render PDF) --}}
    <script src="{{ asset('js/kaprodi/review-surat.js') }}"></script>
@endpush
