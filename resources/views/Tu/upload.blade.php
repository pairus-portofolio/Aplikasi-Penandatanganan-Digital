@extends('layouts.app')

@section('title', 'Unggah Surat')

@section('content')

<!-- Tampilkan pesan sukses jika upload berhasil -->
@if(session('success'))
    <div class="bg-green-200 text-green-700 p-3 rounded mb-4">
        {{ session('success') }}
    </div>
@endif

<!-- Tampilkan error validasi jika ada -->
@if ($errors->any())
    <div class="bg-red-200 text-red-700 p-3 rounded mb-4">
        <ul class="list-disc ms-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<!-- Load CSS khusus halaman upload -->
<link rel="stylesheet" href="{{ asset('css/upload.css') }}">

<h1>Unggah Surat</h1>

<!-- Form upload surat -->
<form method="POST" action="{{ route('tu.upload.store') }}" enctype="multipart/form-data">
    @csrf

    <!-- Area untuk drag & drop file -->
    <div class="upload-box" id="drop-area">
        <p class="upload-title">Seret & letakan file disini</p>
        <p class="upload-subtitle">hanya mendukung file docx</p>
        <button type="button" class="upload-btn">pilih file</button>
    </div>

    <!-- Input file asli (disembunyikan) -->
    <input id="file-input" name="file_surat" type="file" accept=".docx" style="display:none;" required>

    <!-- Container form detail surat -->
    <div class="form-container">

        <!-- Wrapper detail surat -->
        <div class="detail-container">
            <div class="detail-title">Detail Surat</div>

            <div class="detail-wrapper">

                <!-- Judul surat yang diambil otomatis dari nama file -->
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

                 <!-- List alur penandatangan surat -->
                <div id="alurStepsContainer" class="alur-steps">
                    <p class="alur-placeholder">Alur surat.</p>
                    <ol id="alurList" class="alur-list"></ol>
                </div>

                 <!-- Input tersembunyi untuk menyimpan urutan penandatangan -->
                <input type="hidden" name="alur" id="alurInput">

            </div>
        </div>
    </div>

    <!-- Tombol submit akan muncul setelah file dipilih -->
    <div id="submit-button-wrapper" style="text-align: center; margin-top: 32px; display: none;">
        <button type="submit" class="upload-btn" style="cursor: pointer;">Unggah</button>
    </div>

</form>

@endsection

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", function() {

    // === Element utama ===
    const dropArea = document.getElementById("drop-area");
    const fileInput = document.getElementById("file-input");
    const submitButtonWrapper = document.getElementById("submit-button-wrapper");
    const judulSuratInput = document.getElementById("judul_surat");

    // Simpan HTML awal upload-box untuk kebutuhan reset
    const initialDropAreaHTML = dropArea.innerHTML;

    // Set agar hanya file docx diterima
    fileInput.setAttribute('accept', '.docx');

    // ======================================================
    // === Klik area upload untuk membuka file dialog     ===
    // ======================================================
    dropArea.addEventListener("click", () => {
        fileInput.value = "";
        fileInput.click();
    });

    // ======================================================
    // === Drag & Drop file ke area upload                 ===
    // ======================================================

    // Tambahkan efek saat file di-drag ke area upload
    dropArea.addEventListener("dragover", (e) => {
        e.preventDefault();
        dropArea.classList.add("drag-over");
    });

    // Hilangkan efek saat drag keluar area
    dropArea.addEventListener("dragleave", () => {
        dropArea.classList.remove("drag-over");
    });

    // Jika file di-drop, tangani file tersebut
    dropArea.addEventListener("drop", (e) => {
        e.preventDefault();
        dropArea.classList.remove("drag-over");
        const files = e.dataTransfer.files;

        if (files.length > 0) {
            try { fileInput.files = files; } catch (err) {}
            handleFile(files[0]);
        }
    });

    // ======================================================
    // === Upload file menggunakan file dialog             ===
    // ======================================================
    fileInput.addEventListener("change", function () {
        if (this.files && this.files.length > 0) {
            handleFile(this.files[0]);
        } else {
            resetFileSelection();
        }
    });

    // ======================================================
    // === Fungsi menangani file yang diupload             ===
    // ======================================================
    function handleFile(file) {

        // Cek file valid (harus .docx)
        if (!file.name.toLowerCase().endsWith(".docx")) {
            alert("Hanya file .docx yang diperbolehkan.");
            resetFileSelection();
            return;
        }

        // Ubah area upload menjadi tampilan file terpilih
        dropArea.classList.add('has-file');
        dropArea.innerHTML = `
            <div class="selected-file">
                <span class="selected-file-icon">📄</span>
                <span class="selected-file-label">Nama File</span>
                <span class="selected-file-name" title="${file.name}">${file.name}</span>
            </div>
        `;

        // Isi judul surat berdasarkan nama file
        judulSuratInput.value = file.name;

        // Tampilkan tombol submit
        submitButtonWrapper.style.display = "block";
    }

    // ======================================================
    // === Alur penandatangan surat                       ===
    // ======================================================
    const userSelect = document.getElementById("userSelect");
    const alurList = document.getElementById("alurList");
    const alurInput = document.getElementById("alurInput");

    // Tambahkan user ke daftar alur ketika dipilih
    userSelect.addEventListener('change', function() {
        const userId = this.value;
        const userName = this.options[this.selectedIndex].text;
        
        if (userId) {

            // Buat elemen list baru
            const listItem = document.createElement('li');
            listItem.classList.add('alur-item');
            listItem.textContent = userName;
            listItem.dataset.userId = userId;

            // Buat tombol hapus setiap alur
            const removeButton = document.createElement('button');
            removeButton.classList.add('remove-alur-btn');
            removeButton.textContent = 'Hapus';

            // Event tombol hapus
            removeButton.onclick = function() {
                alurList.removeChild(listItem);
                updateAlurInput();
            };

            // Tempelkan tombol hapus
            listItem.appendChild(removeButton);
            alurList.appendChild(listItem);

            // Update input hidden
            updateAlurInput();

            // Reset dropdown
            this.value = ""; 
        }
    });

    // Simpan urutan ID penandatangan ke input hidden
    function updateAlurInput() {
        const alurUserIds = [];
        
        alurList.querySelectorAll('li').forEach(item => {
            alurUserIds.push(item.dataset.userId);
        });

        alurInput.value = alurUserIds.join(',');
    }

    // ======================================================
    // === Reset file upload                              ===
    // ======================================================
    window.resetFileSelection = function() {
        fileInput.value = "";
        dropArea.classList.remove('has-file');
        dropArea.innerHTML = initialDropAreaHTML;
        submitButtonWrapper.style.display = "none";
        judulSuratInput.value = "";
    }
});
</script>
@endpush
