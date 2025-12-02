@extends('layouts.app')

@section('title', 'Paraf Surat')

@push('styles')
    {{-- CSRF Token for AJAX requests --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    {{-- Page-specific stylesheets --}}
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

        {{-- Paraf Sidebar --}}
        <div class="paraf-sidebar">
            <p class="paraf-sidebar-title">
                Paraf Tersedia:
            </p>

            <div id="parafBox" class="paraf-template-box {{ $parafData['hasImage'] ? 'has-image' : '' }}">
                <span class="paraf-text">Klik untuk upload</span>

                {{-- Paraf Image --}}
                <img id="parafImage"
                     class="paraf-image-preview"
                     src="{{ $parafData['url'] }}"
                     alt="Paraf"
                     draggable="true">

                {{-- Action Buttons --}}
                <div class="paraf-box-actions">
                    <button type="button" class="paraf-action-btn" id="parafGantiBtn" title="Ganti Paraf">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </button>

                    <button type="button" class="paraf-action-btn" id="parafHapusBtn" title="Hapus Paraf">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </div>

            {{-- Hidden File Input --}}
            <input type="file"
                   id="parafImageUpload"
                   class="paraf-file-input"
                   accept="image/png, image/jpeg, image/jpg">
        </div>

        {{-- Document Preview Area --}}
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
    {{-- PDF.js Library --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>

    {{-- PDF Configuration --}}
    <script>
        window.pdfConfig = {
            pdfUrl: "{{ route('document.download', $document->id) }}",
            workerSrc: "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js",
            saveUrl: "{{ route('kaprodi.paraf.save', $document->id) }}",
            uploadUrl: "{{ route('kaprodi.paraf.upload') }}",
            deleteUrl: "{{ route('kaprodi.paraf.delete') }}",
            csrfToken: "{{ csrf_token() }}",
            savedParaf: @json($savedParaf ?? null)
        };
    </script>

    {{-- Application Scripts --}}
    <script src="{{ asset('js/pdf-signer.js') }}"></script>
    <script src="{{ asset('js/kaprodi/paraf-surat.js') }}"></script>

    {{-- Show popup if session exists --}}
    @if(session('popup'))
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                document.getElementById("parafNotifPopup").classList.add("show");
            });
        </script>
    @endif
@endpush
