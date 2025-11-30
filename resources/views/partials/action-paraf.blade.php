<!-- Kontrol bawah halaman: zoom + tombol selesai -->
<div class="pv-controls">

    @include('partials.shared.zoom-controls')

    {{-- Tombol Selesai --}}
    <button type="button" class="pv-primary-btn" data-bs-toggle="modal" data-bs-target="#parafNotifPopup">
        Selesai
    </button>

</div>

<!-- Form Submit (Hidden) -->
<form action="{{ route('kaprodi.paraf.submit', $document->id) }}" method="POST" id="formParaf">
    @csrf
    <input type="hidden" name="send_notification" id="sendNotifParaf" value="0">
</form>

<!-- Modal Konfirmasi Notifikasi -->
<div class="modal fade" id="parafNotifPopup" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-center">Konfirmasi Paraf</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <p>Paraf tidak bisa diubah lagi setelah Anda konfirmasi.</p>
                <p class="fw-bold">Apakah Anda ingin mengirim email notifikasi ke orang selanjutnya?</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-success" onclick="submitParaf(1)">Ya</button>
                <button type="button" class="btn btn-secondary" onclick="submitParaf(0)">Tidak</button>
                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Batal</button>
            </div>
        </div>
    </div>
</div>

<script>
    function submitParaf(val) {
        document.getElementById('sendNotifParaf').value = val;
        document.getElementById('formParaf').submit();
    }
</script>