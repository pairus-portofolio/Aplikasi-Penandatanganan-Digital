<!-- Kontrol bawah halaman: zoom + tombol selesai -->
<div class="pv-controls">

    @include('partials.shared.zoom-controls')

    <!-- Tombol SELESAI langsung submit Workflow -->
    <form id="workflowSubmitForm"
          action="{{ route('kaprodi.paraf.submit', $document->id) }}"
          method="POST"
          style="display: inline;">
        @csrf
        <button type="submit" class="pv-primary-btn btn-blue">
            Selesai
        </button>
    </form>

</div>

<!-- Tetap include popup notifikasi -->
@include('partials.shared.popup-modal', [
    'modalId'       => 'parafNotifPopup',
    'title'         => 'Kirim Notifikasi Email',
    'inputIdPrefix' => 'paraf',
    'defaultSubject'=> 'Dokumen Selesai Diparaf',
    'showNotes'     => false,
    'cancelBtnId'   => 'batalKirim',
    'confirmBtnId'  => 'konfirmasiKirim'
])
