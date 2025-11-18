@extends('layouts.app')

@section('title', 'Paraf Surat')

@push('styles')
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
        'judulSurat'  => 'Pratijau: Nama Surat',
        'currentPage' => 1,
        'totalPages'  => 5,
    ])
@endsection

@section('content')
    
    <div class="paraf-layout-container">
        
        <div class="paraf-sidebar">
            <p style="font-weight: 600; color: #333; margin-top:0; margin-bottom: 10px;">Paraf Tersedia:</p>
            
            <div id="parafBox" class="paraf-template-box">
                <span class="paraf-text">Klik untuk upload</span>
                <img id="parafImage" class="paraf-image-preview" src="" alt="Paraf" draggable="true">
                <div class="paraf-box-actions">
                    <button type="button" class="paraf-action-btn" id="parafGantiBtn" title="Ganti Paraf">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </button>
                    <button type="button" class="paraf-action-btn" id="parafHapusBtn" title="Hapus Paraf">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </div>
            
            <input type="file" id="parafImageUpload" style="display: none;" accept="image/png">
        </div>
        
        <div class="paraf-preview-area">
            <div id="previewPage" class="paraf-drop-zone" style="height: 150vh; background: #f0f0f0; border: 1px dashed #ccc; display:flex; align-items:center; justify-content:center; color: #999; transform-origin: top center; position: relative; overflow: hidden;">
                (Area Preview Dokumen)
            </div>
        </div>
    </div>

@endsection

@section('popup')
    @include('partials.action-paraf')
    @include('partials.logout-popup')
@endsection

@push('scripts')
    <script src="{{ asset('js/kaprodi/paraf-surat.js') }}"></script>
@endpush