<!-- Kontrol bawah halaman: zoom + tombol selesai -->
<div class="pv-controls">

    @include('partials.shared.zoom-controls')

    {{-- Tombol Selesai (gunakan ModalManager) --}}
    <button type="button" class="pv-primary-btn btn-blue" data-modal="paraf-confirm" data-modal-title="Konfirmasi Paraf">
        Selesai
    </button>

</div>


<!-- Form Submit (Hidden) -->
<form action="{{ route('kaprodi.paraf.submit', $document->id) }}" method="POST" id="formParaf">
    @csrf
    <input type="hidden" name="send_notification" id="sendNotifParaf" value="0">
</form>

<!-- Note: modal markup removed; handled by modal-manager.js -->