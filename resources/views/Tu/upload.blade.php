@extends('layouts.app')
@section('title', isset($document) ? 'Revisi Surat' : 'Unggah Surat')

@section('content')

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

<link rel="stylesheet" href="{{ asset('css/tu/upload.css') }}">

<h1>{{ isset($document) ? 'Revisi Surat: ' . ($document->judul_surat ?? 'Dokumen') : 'Unggah Surat' }}</h1>

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
                           required placeholder="">
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

    {{-- Tombol Submit --}}
    <div id="submit-button-wrapper" style="text-align: center; margin-top: 32px; {{ isset($document) ? '' : 'display: none;' }}">
        <button type="button" class="upload-btn" data-bs-toggle="modal" data-bs-target="#confirmUploadModal" style="cursor: pointer;">
            {{ isset($document) ? 'Simpan Revisi' : 'Unggah Surat' }}
        </button>
    </div>
</form>

<div class="modal fade" id="confirmUploadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Konfirmasi Pengiriman</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <p>{{ isset($document) ? 'Dokumen lama akan diganti dengan yang baru.' : 'Surat akan disimpan ke sistem.' }}</p>
                <p class="fw-bold">Kirim notifikasi email ke penandatangan pertama?</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-success" onclick="submitFormWithNotif(1)">
                    Ya, Kirim Notifikasi
                </button>
                <button type="button" class="btn btn-secondary" onclick="submitFormWithNotif(0)">
                    Ya, Jangan Kirim
                </button>
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">
                    Batal
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Fungsi ini tetap diperlukan di Blade karena menggunakan variabel global 'bootstrap'
    function submitFormWithNotif(val) {
        document.getElementById('sendNotificationValue').value = val;
        
        // Ambil form dan tombol
        const form = document.getElementById('sendNotificationValue').closest('form');
        const modal = bootstrap.Modal.getInstance(document.getElementById('confirmUploadModal'));
        const submitButtons = document.querySelectorAll('#confirmUploadModal button[type="button"]');
        
        // Disable semua tombol di modal
        submitButtons.forEach(btn => {
            btn.disabled = true;
            if (btn.classList.contains('btn-success') || btn.classList.contains('btn-secondary')) {
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memproses...';
            }
        });
        
        // Close modal
        if (modal) {
            modal.hide();
        }
        
        // Submit form
        form.submit();
    }
</script>

@endsection

@section('popup')
    @include('partials.logout-popup')
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('js/tu/upload.js') }}"></script>
    
    @if(isset($document))
    <script>
        // Fungsi ini akan dipanggil setelah DOM siap
        document.addEventListener("DOMContentLoaded", function () {
            // Ambil nilai alur dari hidden input (yang sudah diisi oleh PHP)
            const existingAlur = document.getElementById("alurInput").value;
            
            // Variabel parafUsers dan ttdUser dideklarasikan di tu/upload.js
            
            if (existingAlur) {
                const userIds = existingAlur.split(',');
                const parafSelect = document.getElementById("parafSelect");
                const ttdSelect = document.getElementById("ttdSelect");

                userIds.forEach(id => {
                    // Cari opsi di kedua dropdown
                    let parafOption = parafSelect.querySelector(`option[value="${id}"]`);
                    let ttdOption = ttdSelect.querySelector(`option[value="${id}"]`);

                    if (parafOption) {
                        // Tambahkan ke array parafUsers
                        window.parafUsers.push({
                            id: id,
                            name: parafOption.dataset.name,
                            role: parafOption.dataset.role
                        });
                    } else if (ttdOption) {
                        // Set ttd user
                        window.ttdUser = {
                            id: id,
                            name: ttdOption.dataset.name,
                            role: ttdOption.dataset.role
                        };
                    }
                });

                // Panggil fungsi global dari upload.js untuk merender ulang UI
                updateAlur();
            }
        });
    </script>
    @endif
@endpush