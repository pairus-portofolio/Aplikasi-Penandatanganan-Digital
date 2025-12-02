@extends('layouts.app')

@section('title', 'Arsip Surat')

@push('styles')
    {{-- CSS modul yang sudah ada --}}
    <link rel="stylesheet" href="{{ asset('css/kaprodi/preview-top.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kaprodi/preview-bottom.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kaprodi/zoom-button.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kaprodi/preview-layout.css') }}">
@endpush

@section('page-header')
    @include('partials.doc-header', [
        'judulSurat'  => 'Pratinjau: ' . ($document->judul_surat ?? 'Dokumen Arsip'),
        // kita akan update currentPage/totalPages via JS
        'currentPage' => 1,
        'totalPages'  => 1,
    ])
@endsection

@section('content')
    <div class="review-preview-area">

        <div id="scrollContainer">
            <div id="pdf-render-container"></div>
        </div>
    </div>

    {{-- PANEL KONTROL BAWAH --}}
    <div class="pv-controls">

        {{-- Zoom buttons --}}
        @include('partials.shared.zoom-controls')
        {{-- Tombol utama tengah: Download --}}
    <form action="{{ route('tu.arsip.download', $document->id) }}" method="GET">
        <button type="submit" class="pv-primary-btn btn btn-primary">
            <i class="fa-solid fa-download"></i> Download
        </button>
    </form>
    </div>
@endsection

@section('popup')
    @include('partials.logout-popup')
@endsection

@push('scripts')
    {{-- PDF.js (CDN) --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>

    {{-- Konfigurasi untuk JS --}}
    <script>
        window.reviewConfig = {
            pdfUrl: "{{ route('tu.arsip.preview', $document->id) }}",
            workerSrc: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js'
        };
    </script>

    {{-- Script js --}}
    <script src="{{ asset('js/kaprodi/review-surat.js') }}"></script>
@endpush
