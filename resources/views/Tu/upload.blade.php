@extends('layouts.app')

@section('title', 'Unggah Surat')

@section('content')

@if(session('success'))
    <div class="bg-green-200 text-green-700 p-3 rounded mb-4">
        {{ session('success') }}
    </div>
@endif

{{-- Tampilkan error validasi kalau ada --}}
@if ($errors->any())
    <div class="bg-red-200 text-red-700 p-3 rounded mb-4">
        <ul class="list-disc ms-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<link rel="stylesheet" href="{{ asset('css/upload.css') }}">

<h1>Unggah Surat</h1>

<form method="POST" action="{{ route('tu.upload.store') }}" enctype="multipart/form-data">
    @csrf

    {{-- BOX AWAL UPLOAD --}}
    <div class="upload-box" id="drop-area">
        <p class="upload-title">Seret & letakan file disini</p>
        <p class="upload-subtitle">hanya mendukung file docx</p>
        <button type="button" class="upload-btn">pilih file</button>
    </div>

    {{-- input file disimpan DI LUAR upload-box supaya tidak hilang saat innerHTML diganti --}}
    <input id="file-input" name="file_surat" type="file" accept=".docx" style="display:none;" required>

    {{-- DETAIL SURAT DAN ALUR PENANDATANGANAN DOSEN SEJAJAR --}}
    <div class="form-container">
        {{-- Detail Surat --}}
        <div class="detail-container">
            <div class="detail-title">Detail Surat</div>
            <div class="detail-wrapper">
                <div class="detail-field-box">
                    <label for="judul_surat">Judul Surat :</label>
                    <input id="judul_surat" name="judul_surat" type="text" class="detail-input-inner" required readonly>
                </div>
                <div class="detail-field-box">
                    <label for="kategori">Kategori surat :</label>
                    <input id="kategori" name="kategori" type="text" class="detail-input-inner" required>
                </div>
                <div class="detail-field-box">
                    <label for="tanggal">Tanggal :</label>
                    <input id="tanggal" name="tanggal" type="date" class="detail-input-inner" required>
                </div>
                <div class="detail-field-box">
                    <label for="dosenSelect">Pilih Alur Penandatanganan :</label>
                    <select id="dosenSelect" class="detail-input-inner">
                        <option value="">Pilih alur…</option>
                        <option value="kaprodi_d3">Kaprodi D3</option>
                        <option value="kaprodi_d4">Kaprodi D4</option>
                        <option value="kajur">Kajur</option>
                    </select>
                </div>

                <div id="alurStepsContainer" class="alur-steps">
                    <p class="alur-placeholder">Alur surat.</p>
                    <ol id="alurList" class="alur-list"></ol>
                </div>

                {{-- Hidden input untuk kirim urutan ke backend --}}
                <input type="hidden" name="alur" id="alurInput">
            </div>
        </div>
    </div>

    {{-- TOMBOL SUBMIT: muncul setelah file dipilih --}}
    <div id="submit-button-wrapper" style="text-align: center; margin-top: 32px; display: none;">
        <button type="submit" class="upload-btn" style="cursor: pointer;">Unggah</button>
    </div>

</form>

@endsection

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", function() {
    const dropArea = document.getElementById("drop-area");
    const fileInput = document.getElementById("file-input");
    const submitButtonWrapper = document.getElementById("submit-button-wrapper");
    const judulSuratInput = document.getElementById("judul_surat");

    // simpan tampilan awal upload-box untuk keperluan reset
    const initialDropAreaHTML = dropArea.innerHTML;

    // Pastikan hanya .docx ditampilkan pada dialog
    fileInput.setAttribute('accept', '.docx');

    // === CLICK AREA (box + tombol "pilih file") ===
    dropArea.addEventListener("click", () => {
        fileInput.value = "";
        fileInput.click();
    });

    // === DRAG & DROP ===
    dropArea.addEventListener("dragover", (e) => {
        e.preventDefault();
        dropArea.classList.add("drag-over");
    });

    dropArea.addEventListener("dragleave", () => {
        dropArea.classList.remove("drag-over");
    });

    dropArea.addEventListener("drop", (e) => {
        e.preventDefault();
        dropArea.classList.remove("drag-over");
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            try { fileInput.files = files; } catch (err) {}
            handleFile(files[0]);
        }
    });

    // === FILE DARI DIALOG ===
    fileInput.addEventListener("change", function () {
        if (this.files && this.files.length > 0) {
            handleFile(this.files[0]);
        } else {
            resetFileSelection();
        }
    });

    // ========== HANDLE FILE =============
    function handleFile(file) {
        if (!file) {
            resetFileSelection();
            return;
        }

        if (!file.name.toLowerCase().endsWith(".docx")) {
            alert("Hanya file .docx yang diperbolehkan.");
            resetFileSelection();
            return;
        }

        // Ubah tampilan upload-box menjadi tampilan file
        dropArea.classList.add('has-file');
        dropArea.innerHTML = `
            <div class="selected-file">
                <span class="selected-file-icon">📄</span>
                <span class="selected-file-label">Nama File</span>
                <span class="selected-file-name" title="${file.name}">${file.name}</span>
            </div>
        `;

        // Set judul surat (otomatis ambil nama file)
        judulSuratInput.value = file.name;

        // 🔥 TOMBOL UNGGAH MUNCUL DISINI
        submitButtonWrapper.style.display = "block";
    }

    // Fungsi untuk menambah dosen ke dalam alur penandatanganan
    dosenSelect.addEventListener('change', function() {
        const dosenValue = this.value;
        if (dosenValue) {
            // Menambahkan dosen ke list alur
            const listItem = document.createElement('li');
            listItem.classList.add('alur-item');
            listItem.textContent = dosenSelect.options[dosenSelect.selectedIndex].text;

            // Menambah tombol hapus untuk item alur
            const removeButton = document.createElement('button');
            removeButton.classList.add('remove-alur-btn');
            removeButton.textContent = 'Hapus';
            removeButton.onclick = function() {
                alurList.removeChild(listItem);
                updateAlurInput();
            };
            listItem.appendChild(removeButton);

            alurList.appendChild(listItem);
            updateAlurInput();
        }
    });

    // Update hidden input alur
    function updateAlurInput() {
        const alurSteps = [];
        alurList.querySelectorAll('li').forEach((item) => {
            alurSteps.push(item.textContent.replace('Hapus', '').trim());
        });
        alurInput.value = alurSteps.join(',');  // Mengirimkan data alur sebagai string
    }

    // FUNGSI RESET (kalau nanti mau dipakai)
    window.resetFileSelection = function() {
        fileInput.value = "";
        dropArea.classList.remove('has-file');
        dropArea.innerHTML = initialDropAreaHTML;
        submitButtonWrapper.style.display = "none";
        judulSuratInput.value = ""; // Kosongkan judul surat
    }
});
</script>
@endpush
