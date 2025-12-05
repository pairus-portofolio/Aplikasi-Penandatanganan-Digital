<!-- Kontrol dokumen: zoom + tombol permintaan revisi -->
<div class="pv-controls">
    <!-- Include kontrol zoom (pastikan file ini ada) -->
    @include('partials.shared.zoom-controls')

    <!-- Tombol pemicu modal (handled by ModalManager) -->
    <button type="button" class="pv-primary-btn btn-blue" data-modal="review-revise"
            data-action-url="{{ route('kaprodi.review.revise', $document->id) }}"
            data-uploader-email="{{ $document->uploader->email ?? '-' }}"
            data-default-subject="{{ 'Revisi Dokumen: ' . $document->judul_surat }}">
        Minta Revisi
    </button>
</div>

<!-- modal markup removed; handled by ModalManager -->