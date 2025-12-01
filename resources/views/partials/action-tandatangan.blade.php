<!-- Kontrol bawah halaman untuk tanda tangan -->
<div class="pv-controls">

    @include('partials.shared.zoom-controls')

    <button type="button" class="pv-primary-btn" data-bs-toggle="modal" data-bs-target="#ttdNotifPopup">
        Selesai
    </button>
</div>

<!-- Form Submit (Hidden) -->
<form action="{{ route('kajur.tandatangan.submit', $document->id) }}" method="POST" id="formTtd">
    @csrf
    <input type="hidden" name="send_notification" id="sendNotifTtd" value="0">
</form>

<!-- Modal Konfirmasi -->
<div class="modal fade" id="ttdNotifPopup" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-center">Konfirmasi Tanda Tangan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <p>Tanda tangan tidak bisa diubah lagi setelah Anda konfirmasi.</p>
                <p class="fw-bold">Apakah Anda ingin mengirim email notifikasi ke orang selanjutnya?</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-success" onclick="submitTtd(1)">Ya</button>
                <button type="button" class="btn btn-secondary" onclick="submitTtd(0)">Tidak</button>
                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Batal</button>
            </div>
        </div>
    </div>
</div>

<script>
    function submitTtd(val) {
        document.getElementById('sendNotifTtd').value = val;
        document.getElementById('formTtd').submit();
    }
</script>