@extends('layouts.app')

@section('title', 'Unggah Surat')

@section('content')

<!-- Notifikasi modern pakai SweetAlert2 -->
@if(session('success'))
<script>
    document.addEventListener("DOMContentLoaded", function () {
        Swal.fire({
            toast: true,
            icon: 'success',
            title: '{{ session('success') }}',
            position: 'top-end',
            timer: 2500,
            timerProgressBar: true,
            showConfirmButton: false,
        });
    });
</script>
@endif

@if ($errors->any())
<script>
    document.addEventListener("DOMContentLoaded", function () {
        Swal.fire({
            icon: 'error',
            title: 'Validasi Gagal',
            html: `{!! implode('<br>', $errors->all()) !!}`,
        });
    });
</script>
@endif

<!-- Load CSS halaman unggah surat -->
<link rel="stylesheet" href="{{ asset('css/tu/upload.css') }}">

<h1>Unggah Surat</h1>

<!-- Form utama untuk upload surat -->
<form method="POST" action="{{ route('tu.upload.store') }}" enctype="multipart/form-data">
    @csrf

    <!-- Area drag & drop untuk upload file -->
    <div class="upload-box" id="drop-area">
        <p class="upload-title">Seret & letakan file disini</p>
        <p class="upload-subtitle">hanya mendukung file PDF</p>
        <button type="button" class="upload-btn">Pilih File</button>
    </div>

    <!-- Input file yang disembunyikan -->
    <input id="file-input" name="file_surat" type="file" accept=".pdf" style="display:none;" required>

    <!-- Container bagian input detail surat -->
    <div class="form-container">
        <div class="detail-container">
            <div class="detail-title">Detail Surat</div>

            <div class="detail-wrapper">

                <!-- Judul surat (otomatis dari nama file) -->
                <div class="detail-field-box">
                    <label for="judul_surat">Judul Surat :</label>
                    <input id="judul_surat" name="judul_surat" type="text" class="detail-input-inner" required readonly>
                </div>

                <!-- Input kategori surat -->
                <div class="detail-field-box">
                    <label for="kategori">Kategori surat :</label>
                    <input id="kategori" name="kategori" type="text" class="detail-input-inner" required>
                </div>

                <!-- Input tanggal surat -->
                <div class="detail-field-box">
                    <label for="tanggal">Tanggal :</label>
                    <input id="tanggal" name="tanggal" type="date" class="detail-input-inner" required>
                </div>

                <!-- Dropdown untuk memilih penandatangan -->
                <div class="detail-field-box">
                    <label for="userSelect">Pilih Alur Penandatanganan :</label>
                    <select id="userSelect" class="detail-input-inner">
                        <option value="">Pilih penandatangan...</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->nama_lengkap }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Daftar urutan penandatangan -->
                <div id="alurStepsContainer" class="alur-steps">
                    <p class="alur-placeholder">Alur surat.</p>
                    <ol id="alurList" class="alur-list"></ol>
                </div>

                <!-- Input tersembunyi untuk menyimpan urutan user -->
                <input type="hidden" name="alur" id="alurInput">

            </div>
        </div>
    </div>

    <!-- Tombol unggah muncul setelah file dipilih -->
    <div id="submit-button-wrapper" style="text-align: center; margin-top: 32px; display: none;">
        <button type="submit" class="upload-btn" style="cursor: pointer;">Unggah</button>
    </div>
</form>

@endsection

@section('popup')
    @include('partials.logout-popup')
@endsection

@push('scripts')
    <!-- SweetAlert2 (untuk notifikasi modern) -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Script untuk logika drag-and-drop dan alur sign -->
    <script src="{{ asset('js/tu/upload.js') }}"></script>
@endpush
