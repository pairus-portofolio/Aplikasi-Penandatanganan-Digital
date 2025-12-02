<!-- Tombol Submit Pemicu Modal -->
<!-- Style display tergantung apakah ini mode edit atau upload baru (diatur via JS upload.js nanti) -->
<div id="submit-button-wrapper" style="text-align: center; margin-top: 32px; {{ isset($document) ? '' : 'display: none;' }}">
    <button type="button" class="upload-btn" data-bs-toggle="modal" data-bs-target="#confirmUploadModal" style="cursor: pointer;">
        {{ isset($document) ? 'Simpan Revisi' : 'Unggah Surat' }}
    </button>
</div>

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
                <button type="button" class="btn btn-success" onclick="submitFormWithNotif(1)">Ya</button>
                <button type="button" class="btn btn-danger" onclick="submitFormWithNotif(0)">Tidak</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Fungsi untuk menangani submit dari modal
    function submitFormWithNotif(val) {
        // Isi nilai hidden input di form utama
        const hiddenInput = document.getElementById('sendNotificationValue');
        if(hiddenInput) {
            hiddenInput.value = val;
            
            // Ambil form pembungkus
            const form = hiddenInput.closest('form');
            const modalEl = document.getElementById('confirmUploadModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            const submitButtons = modalEl.querySelectorAll('button[type="button"]');
            
            // UX: Disable tombol biar gak diklik 2x
            submitButtons.forEach(btn => {
                btn.disabled = true;
                if (btn.classList.contains('btn-success') || btn.classList.contains('btn-secondary')) {
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memproses...';
                }
            });
            
            // Tutup modal
            if (modal) {
                modal.hide();
            }
            
            // Submit form
            form.submit();
        } else {
            console.error("Input hidden 'sendNotificationValue' tidak ditemukan!");
        }
    }
</script>