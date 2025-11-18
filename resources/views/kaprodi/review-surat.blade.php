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
    @include('partials.doc-header', [
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
    @include('partials.action-review')
    @include('partials.logout-popup')
@endsection

@push('scripts')
    {{-- Script review-surat.js dihapus karena logikanya pindah ke app.js --}}
@endpush