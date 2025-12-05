<!-- Kontrol bawah halaman: zoom + tombol selesai -->
<div class="pv-controls">

    @include('partials.shared.zoom-controls')

    <!-- Tombol SELESAI dengan ModalManager untuk notifikasi -->
    <button type="button"
            class="pv-primary-btn btn-blue"
            data-modal="submission-notification"
            data-action-url="{{ $actionUrl }}"
            data-default-subject="{{ $defaultSubject ?? 'Pemberitahuan Proses Alur' }}">
        Selesai
    </button>

</div>

{{-- Note: Modal markup removed; handled by ModalManager (modal-manager.js) --}}

