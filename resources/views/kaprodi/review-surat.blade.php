@extends('layouts.app')

@section('title', 'Review Surat')

@push('styles')
    <!-- Memuat seluruh stylesheet yang diperlukan untuk halaman review -->
    <link rel="stylesheet" href="{{ asset('css/kaprodi/preview-top.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kaprodi/preview-bottom.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kaprodi/zoom-button.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kaprodi/revision-button.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kaprodi/popup.css') }}">
@endpush

@section('page-header')
    <!-- Header dokumen: judul surat dan informasi nomor halaman -->
    @include('partials.doc-header', [
        'judulSurat'  => 'Pratijau: Nama Surat',
        'currentPage' => 1,
        'totalPages'  => 5,
    ])
@endsection

@section('content')

    <!-- Area pratinjau surat yang ditampilkan sebelum direvisi atau disetujui -->
    <div 
        id="previewPage"
        style="height: 150vh; background: #f0f0f0; border: 1px dashed #ccc; display:flex; align-items:center; justify-content:center; color: #999; transform-origin: top center;"
    >
        (Area Preview Dokumen)
    </div>

@endsection

@section('popup')
    <!-- Popup aksi untuk melakukan review (revisi, setujui, dll.) -->
    @include('partials.action-review')

    <!-- Popup konfirmasi logout -->
    @include('partials.logout-popup')
@endsection

@push('scripts')
    <!-- Placeholder untuk script tambahan bila diperlukan -->
@endpush
