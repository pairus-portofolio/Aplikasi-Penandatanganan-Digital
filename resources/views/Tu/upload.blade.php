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
    
    @if(isset($document))
        @method('PUT')
    @endif

    <div class="upload-box" id="drop-area">
        <p class="upload-title">Seret & letakan file disini</p>
        <p class="upload-subtitle">hanya mendukung file PDF</p>
        <button type="button" class="upload-btn">Pilih File</button>
    </div>

    <input id="file-input" name="file_surat" type="file" accept=".pdf" style="display:none;" required>

    <div class="form-container">
        <div class="detail-container">
            <div class="detail-title">Detail Surat</div>

            <div class="detail-wrapper">

                <div class="detail-field-box">
                    <label for="judul_surat">Judul Surat :</label>
                    <input id="judul_surat" name="judul_surat" type="text" class="detail-input-inner" 
                           value="{{ old('judul_surat', $document->judul_surat ?? '') }}" 
                           required placeholder="Judul akan terisi otomatis dari nama file">
                </div>

                <div class="detail-field-box">
                    <label for="kategori">Kategori surat :</label>
                    <input id="kategori" name="kategori" type="text" class="detail-input-inner" 
                           value="{{ old('kategori', $document->kategori ?? '') }}"
                           required>
                </div>

                <div class="detail-field-box">
                    <label for="tanggal">Tanggal :</label>
                    <input id="tanggal" name="tanggal" type="date" class="detail-input-inner" 
                           value="{{ old('tanggal', $document->tanggal_surat ?? '') }}"
                           required>
                </div>

                {{-- 1. Pemilihan Pemaraf (Kaprodi) --}}
                <div class="detail-field-box">
                    <label for="parafSelect">1. Pilih Pemaraf (Kaprodi):</label>
                    <select id="parafSelect" class="detail-input-inner">
                        <option value="" disabled selected hidden>Pilih untuk Paraf...</option>
                        @foreach($kaprodis as $user)
                            <option value="{{ $user->id }}" data-name="{{ $user->nama_lengkap }}" data-role="{{ $user->role->nama_role ?? 'Kaprodi' }}">{{ $user->nama_lengkap }} ({{ $user->role->nama_role ?? 'Kaprodi' }})</option>
                        @endforeach
                    </select>
                    <small class="text-muted" style="font-size:0.8rem; margin-top:4px;">*Opsional, Paraf maksimal 2 Kaprodi.</small>
                    
                    {{-- Kontainer custom list untuk Paraf --}}
                    <div id="selectedParafContainer" class="alur-list" style="margin-top: 10px; display: flex; flex-direction: column; gap: 8px;">
                        {{-- Paraf terpilih akan dirender di sini oleh JS --}}
                    </div>
                </div>

                {{-- 2. Pemilihan Penandatangan (Kajur/Sekjur) --}}
                <div class="detail-field-box">
                    <label for="ttdSelect">2. Pilih Penandatangan (Kajur/Sekjur):</label>
                    <select id="ttdSelect" class="detail-input-inner">
                        <option value="" disabled selected hidden>Pilih untuk Tanda Tangan...</option>
                        @foreach($penandatangan as $user)
                            <option value="{{ $user->id }}" data-name="{{ $user->nama_lengkap }}" data-role="{{ $user->role->nama_role ?? 'Pejabat' }}">{{ $user->nama_lengkap }} ({{ $user->role->nama_role ?? 'Pejabat' }})</option>
                        @endforeach
                    </select>
                    <small class="text-muted" style="font-size:0.8rem; margin-top:4px;">*Wajib, Tanda Tangan hanya boleh 1.</small>
                    
                    {{-- Kontainer custom list untuk TTD --}}
                    <div id="selectedTtdContainer" class="alur-list" style="margin-top: 10px; display: flex; flex-direction: column; gap: 8px;">
                        {{-- TTD terpilih akan dirender di sini oleh JS --}}
                    </div>
                </div>
                
                @php
                    // Untuk Mode Revisi: Ambil data alur lama
                    $existingAlur = isset($existingAlurIds) ? implode(',', $existingAlurIds) : '';
                @endphp
                
                <input type="hidden" name="alur" id="alurInput" value="{{ $existingAlur }}">
            </div>
        </div>
    </div>

    {{-- Hidden Notifikasi --}}
    <input type="hidden" name="send_notification" id="sendNotificationValue" value="0">

    <!-- PANGGIL PARTIAL TOMBOL & MODAL (Yang baru kamu buat) -->
    @include('partials.action-upload')

</form>

@endsection

@section('popup')
    
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
