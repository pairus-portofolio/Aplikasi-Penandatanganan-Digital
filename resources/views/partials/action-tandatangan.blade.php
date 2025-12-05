<!-- Kontrol bawah halaman untuk tanda tangan -->
<div class="pv-controls">

    @include('partials.shared.zoom-controls')

    <button type="button" class="pv-primary-btn btn-blue" data-modal="ttd-confirm">
        Selesai
    </button>
</div>

<!-- Form Submit (Hidden) -->
<form action="{{ route('kajur.tandatangan.submit', $document->id) }}" method="POST" id="formTtd">
    @csrf
    <input type="hidden" name="send_notification" id="sendNotifTtd" value="0">
</form>

{{-- Modal handled by ModalManager --}}
<script>
    document.addEventListener('DOMContentLoaded', function(){
        document.addEventListener('click', function(e){
            const btn = e.target.closest('[data-modal="ttd-confirm"]');
            if(!btn || !window.ModalManager) return;
            e.preventDefault();

            ModalManager.confirm({
                title: 'Konfirmasi Tanda Tangan',
                message: 'Tanda tangan tidak bisa diubah lagi setelah Anda konfirmasi.<p class="fw-bold mt-3">Apakah Anda ingin mengirim email notifikasi ke orang selanjutnya?</p>',
                okText: 'Ya',
                cancelText: 'Tidak',
                onOk: function(){
                    document.getElementById('sendNotifTtd').value = '1';
                    document.getElementById('formTtd').submit();
                }
            });

            // Handle "Tidak" separately
            setTimeout(()=>{
                const cancelBtn = document.getElementById('modalCancelBtn');
                if(cancelBtn){
                    cancelBtn.removeEventListener('click', ModalManager.hide);
                    cancelBtn.addEventListener('click', function(){
                        document.getElementById('sendNotifTtd').value = '0';
                        document.getElementById('formTtd').submit();
                        ModalManager.hide();
                    });
                }
            }, 100);
        });
    });
</script>
