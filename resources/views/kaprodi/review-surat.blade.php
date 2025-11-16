@extends('layouts.app')

@section('title', 'Review Surat')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/kaprodi/preview-top.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kaprodi/preview-bottom.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kaprodi/zoom-button.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kaprodi/revision-button.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kaprodi/popup.css') }}">
@endpush

@section('page-header')
    @include('partials.preview-header', [
        'judulSurat'  => 'Pratijau: Nama Surat',
        'currentPage' => 1,
        'totalPages'  => 5,
    ])
@endsection

@section('content')
    <div id="previewPage" style="height: 150vh; background: #f0f0f0; border: 1px dashed #ccc; display:flex; align-items:center; justify-content:center; color: #999; transform-origin: top center;">
        (Area Preview Dokumen)
    </div>
@endsection

@section('popup')
    @include('partials.preview-toolbar')
    @include('partials.logout-popup')
@endsection

@section('scripts')
    <script src="{{ asset('js/kaprodi/review-surat.js') }}"></script>
@endsection