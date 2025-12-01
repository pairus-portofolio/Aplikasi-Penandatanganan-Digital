@extends('layouts.app')

@section('title', isset($document) ? 'Revisi Surat' : 'Unggah Surat')

@section('content')

<!-- Notifikasi -->
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

<!-- FORM DINAMIS -->
<!-- Jika ada $document, arahkan ke route updateRevision dengan method PUT -->
<!-- Jika tidak, arahkan ke store dengan method POST -->
<form method="POST" 
      action="{{ isset($document) ? route('tu.document.revisi', $document->id) : route('tu.upload.store') }}" 
      enctype="multipart/form-data">
    
    @csrf
    
    <!-- METHOD PUT UNTUK REVISI -->
    @if(isset($document))
        @method('PUT')
    @endif

    <div class="upload-box" id="drop-area">
        <p class="upload-title">Seret & letakan file disini</p>
        <p class="upload-subtitle">hanya mendukung file PDF</p>
        <button type="button" class="upload-btn">Pilih File</button>
    </div>

    <!-- Input file -->
    <input id="file-input" name="file_surat" type="file" accept=".pdf" style="display:none;" required>

    <div class="form-container">
        <div class="detail-container">
            <div class="detail-title">Detail Surat</div>
            <div class="detail-wrapper">

                <!-- Judul -->
                <div class="detail-field-box">
                    <label for="judul_surat">Judul Surat :</label>
                    <input id="judul_surat" name="judul_surat" type="text" class="detail-input-inner" 
                           value="{{ old('judul_surat', $document->judul_surat ?? '') }}" required {{ isset($document) ? '' : 'readonly' }}>
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

                <!-- ALUR (Hanya Penampil, logika JS dibawah yang mengisi) -->
                <div class="detail-field-box">
                    <label for="userSelect">Pilih Alur Penandatanganan :</label>
                    <select id="userSelect" class="detail-input-inner">
                        <option value="">Pilih penandatangan...</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->nama_lengkap }}</option>
                        @endforeach
                    </select>
                </div>

                <div id="alurStepsContainer" class="alur-steps">
                    <p class="alur-placeholder">Alur surat.</p>
                    <ol id="alurList" class="alur-list"></ol>
                </div>

                <!-- Input Hidden Alur -->
                <!-- PRE-FILL ALUR JIKA REVISI (Mengambil ID user dari workflow steps lama) -->
                @php
                    $existingAlur = isset($document) ? $document->workflowSteps->pluck('user_id')->join(',') : '';
                @endphp
                <input type="hidden" name="alur" id="alurInput" value="{{ $existingAlur }}">

            </div>
        </div>
    </div>

    <!-- Hidden Notifikasi -->
    <input type="hidden" name="send_notification" id="sendNotificationValue" value="0">

    <!-- Tombol Submit -->
    <div id="submit-button-wrapper" style="text-align: center; margin-top: 32px; {{ isset($document) ? '' : 'display: none;' }}">
        <button type="button" class="upload-btn" data-bs-toggle="modal" data-bs-target="#confirmUploadModal" style="cursor: pointer;">
            {{ isset($document) ? 'Simpan Revisi' : 'Unggah Surat' }}
        </button>
    </div>
</form>

<!-- Modal Konfirmasi -->
<div class="modal fade" id="confirmUploadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Konfirmasi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <p>{{ isset($document) ? 'Dokumen lama akan diganti dengan yang baru.' : 'Surat akan disimpan ke sistem.' }}</p>
                <p class="fw-bold">Kirim notifikasi email ke penandatangan pertama?</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-success" onclick="submitFormWithNotif(1)">Ya, Kirim Notifikasi</button>
                <button type="button" class="btn btn-secondary" onclick="submitFormWithNotif(0)">Ya, Jangan Kirim</button>
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Batal</button>
            </div>
        </div>
    </div>
</div>

<script>
    function submitFormWithNotif(val) {
        document.getElementById('sendNotificationValue').value = val;
        document.getElementById('sendNotificationValue').closest('form').submit();
    }
</script>

@endsection

@section('popup')
    @include('partials.logout-popup')
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('js/tu/upload.js') }}"></script>
    
    <!-- Script Tambahan untuk Load Data Lama saat Revisi -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Jika ada nilai alur (mode revisi), load list-nya
            const existingAlur = document.getElementById("alurInput").value;
            if (existingAlur) {
                const userIds = existingAlur.split(',');
                const userSelect = document.getElementById("userSelect");
                const alurList = document.getElementById("alurList");
                
                // Hapus placeholder
                const placeholder = document.querySelector(".alur-placeholder");
                if(placeholder) placeholder.style.display = 'none';

                userIds.forEach(id => {
                    // Cari nama user di option dropdown
                    let option = userSelect.querySelector(`option[value="${id}"]`);
                    if (option) {
                        const userName = option.text;
                        
                        // Buat elemen list manual (copy logic dari upload.js)
                        const listItem = document.createElement("li");
                        listItem.classList.add("alur-item");
                        listItem.textContent = userName;
                        listItem.dataset.userId = id;

                        const removeButton = document.createElement("button");
                        removeButton.classList.add("remove-alur-btn");
                        removeButton.textContent = "Hapus";
                        removeButton.onclick = function () {
                            alurList.removeChild(listItem);
                            updateAlurInput(); // Panggil fungsi dari upload.js (pastikan scope-nya global/accessible)
                            option.disabled = false;
                        };

                        listItem.appendChild(removeButton);
                        alurList.appendChild(listItem);
                        option.disabled = true;
                    }
                });
            }
        });
    </script>
@endpush