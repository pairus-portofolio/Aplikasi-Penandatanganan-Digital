@extends('layouts.app')

@section('title', isset($document) ? 'Revisi Surat' : 'Unggah Surat')

@section('content')

<!-- Notifikasi SweetAlert -->
@if(session('success'))
<script>
    document.addEventListener("DOMContentLoaded", function () {
        Swal.fire({ toast: true, icon: 'success', title: '{{ session('success') }}', position: 'top-end', timer: 2500, showConfirmButton: false });
    });
</script>
@endif

@if ($errors->any())
<script>
    document.addEventListener("DOMContentLoaded", function () {
        Swal.fire({ icon: 'error', title: 'Validasi Gagal', html: `{!! implode('<br>', $errors->all()) !!}` });
    });
</script>
@endif

<link rel="stylesheet" href="{{ asset('css/tu/upload.css') }}">

<!-- JUDUL DINAMIS -->
<h1>{{ isset($document) ? 'Revisi Surat: ' . $document->judul_surat : 'Unggah Surat' }}</h1>

<!-- FORM UTAMA -->
<!-- Action dinamis: Ke 'revisi' (PUT) jika ada dokumen, atau ke 'store' (POST) jika baru -->
<form method="POST" 
      action="{{ isset($document) ? route('tu.document.revisi', $document->id) : route('tu.upload.store') }}" 
      enctype="multipart/form-data">
    
    @csrf
    
    <!-- Method Spoofing untuk PUT jika Mode Revisi -->
    @if(isset($document))
        @method('PUT')
    @endif

    <!-- Area Drag & Drop -->
    <div class="upload-box" id="drop-area">
        <p class="upload-title">Seret & letakan file disini</p>
        <p class="upload-subtitle">hanya mendukung file PDF</p>
        <button type="button" class="upload-btn">Pilih File</button>
    </div>

    <!-- Input file (Hidden) -->
    <input id="file-input" name="file_surat" type="file" accept=".pdf" style="display:none;" required>

    <!-- Detail Form Container -->
    <div class="form-container">
        <div class="detail-container">
            <div class="detail-title">Detail Surat</div>
            <div class="detail-wrapper">

                <!-- Judul Surat -->
                <!-- Readonly dihapus agar bisa diedit saat revisi -->
                <div class="detail-field-box">
                    <label for="judul_surat">Judul Surat :</label>
                    <input id="judul_surat" name="judul_surat" type="text" class="detail-input-inner" 
                           value="{{ old('judul_surat', $document->judul_surat ?? '') }}" 
                           required placeholder="Judul akan terisi otomatis dari nama file">
                </div>

                <!-- Kategori -->
                <div class="detail-field-box">
                    <label for="kategori">Kategori surat :</label>
                    <input id="kategori" name="kategori" type="text" class="detail-input-inner" 
                           value="{{ old('kategori', $document->kategori ?? '') }}" required>
                </div>

                <!-- Tanggal -->
                <div class="detail-field-box">
                    <label for="tanggal">Tanggal :</label>
                    <input id="tanggal" name="tanggal" type="date" class="detail-input-inner" 
                           value="{{ old('tanggal', $document->tanggal_surat ?? '') }}" required>
                </div>

                <!-- ALUR -->
                <div class="detail-field-box">
                    <label for="userSelect">Pilih Alur Penandatanganan :</label>
                    <select id="userSelect" class="detail-input-inner">
                        <option value="">Pilih penandatangan...</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->nama_lengkap }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- List Penandatangan (Diisi oleh JS) -->
                <div id="alurStepsContainer" class="alur-steps">
                    <p class="alur-placeholder">Alur surat.</p>
                    <ol id="alurList" class="alur-list"></ol>
                </div>

                <!-- Input Hidden Alur (Menyimpan ID user urut dipisah koma) -->
                @php
                    // Jika revisi, ambil urutan user dari workflow steps lama
                    $existingAlur = isset($document) ? $document->workflowSteps->pluck('user_id')->join(',') : '';
                @endphp
                <input type="hidden" name="alur" id="alurInput" value="{{ $existingAlur }}">

            </div>
        </div>
    </div>

    <!-- Hidden Notifikasi (Nilainya diisi oleh JS di Partial saat tombol diklik) -->
    <input type="hidden" name="send_notification" id="sendNotificationValue" value="0">

    <!-- PANGGIL PARTIAL TOMBOL & MODAL (Yang baru kamu buat) -->
    @include('partials.action-upload')

</form>

@endsection

@section('popup')
    @include('partials.logout-popup')
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('js/tu/upload.js') }}"></script>
    
    <!-- Script Load Data Revisi (Alur Lama) -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const existingAlur = document.getElementById("alurInput").value;
            
            // Jika ada data alur lama (Mode Revisi)
            if (existingAlur) {
                const userIds = existingAlur.split(',');
                const userSelect = document.getElementById("userSelect");
                const alurList = document.getElementById("alurList");
                const placeholder = document.querySelector(".alur-placeholder");
                
                // Sembunyikan placeholder "Alur surat"
                if(placeholder) placeholder.style.display = 'none';

                userIds.forEach(id => {
                    // Cari nama user di option dropdown berdasarkan ID
                    let option = userSelect.querySelector(`option[value="${id}"]`);
                    if (option) {
                        const userName = option.text;
                        
                        // Buat elemen list (tampilan visual alur)
                        const listItem = document.createElement("li");
                        listItem.classList.add("alur-item");
                        listItem.textContent = userName;
                        listItem.dataset.userId = id;

                        // Tombol hapus
                        const removeButton = document.createElement("button");
                        removeButton.classList.add("remove-alur-btn");
                        removeButton.textContent = "Hapus";
                        removeButton.onclick = function () {
                            alurList.removeChild(listItem);
                            updateAlurInput(); // Panggil fungsi global dari upload.js
                            option.disabled = false;
                        };

                        listItem.appendChild(removeButton);
                        alurList.appendChild(listItem);
                        
                        // Disable opsi di dropdown biar gak dipilih 2x
                        option.disabled = true;
                    }
                });
            }
        });
    </script>
@endpush