<!-- Tombol Submit Pemicu Modal -->
<!-- Style display tergantung apakah ini mode edit atau upload baru (diatur via JS upload.js nanti) -->
<div id="submit-button-wrapper" style="text-align: center; margin-top: 32px; {{ isset($document) ? '' : 'display: none;' }}">
    <button type="button" class="upload-btn" data-modal="upload-confirm"
            data-is-edit="{{ isset($document) ? 'true' : 'false' }}"
            style="cursor: pointer;">
        {{ isset($document) ? 'Simpan Revisi' : 'Unggah Surat' }}
    </button>
</div>

{{-- Modal handled by ModalManager --}}
<script>
    // Register custom handler for upload-confirm
    document.addEventListener('DOMContentLoaded', function(){
        document.addEventListener('click', function(e){
            const btn = e.target.closest('[data-modal="upload-confirm"]');
            if(!btn || !window.ModalManager) return;
            e.preventDefault();

            const isEdit = btn.getAttribute('data-is-edit') === 'true';
            const message = isEdit ? 'Dokumen lama akan diganti dengan yang baru.' : 'Surat akan disimpan ke sistem.';

            ModalManager.confirm({
                title: 'Konfirmasi',
                message: message + '<p class="fw-bold mt-3">Kirim notifikasi email ke penandatangan pertama?</p>',
                okText: 'Ya',
                cancelText: 'Tidak',
                onOk: function(){
                    const hiddenInput = document.getElementById('sendNotificationValue');
                    if(hiddenInput) {
                        hiddenInput.value = '1';
                        const form = hiddenInput.closest('form');
                        if(form) form.submit();
                    }
                }
            });

            // Add another confirm for "Tidak" (don't send notification)
            setTimeout(()=>{
                const cancelBtn = document.getElementById('modalCancelBtn');
                if(cancelBtn){
                    cancelBtn.removeEventListener('click', ModalManager.hide);
                    cancelBtn.addEventListener('click', function(){
                        const hiddenInput = document.getElementById('sendNotificationValue');
                        if(hiddenInput) {
                            hiddenInput.value = '0';
                            const form = hiddenInput.closest('form');
                            if(form) form.submit();
                        }
                        ModalManager.hide();
                    });
                }
            }, 100);
        });
    });
</script>
