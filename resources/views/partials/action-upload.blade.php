<!-- Tombol Submit Pemicu Modal -->
<!-- Style display tergantung apakah ini mode edit atau upload baru (diatur via JS upload.js nanti) -->
<div id="submit-button-wrapper" style="text-align: center; margin-top: 32px; {{ isset($document) ? '' : 'display: none;' }}">
    <button type="button" class="upload-btn" data-modal="upload-confirm"
            data-is-edit="{{ isset($document) ? 'true' : 'false' }}"
            style="cursor: pointer;">
        {{ isset($document) ? 'Simpan Revisi' : 'Unggah Surat' }}
    </button>
</div>

