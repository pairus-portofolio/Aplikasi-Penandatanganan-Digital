<!-- Kontrol bawah halaman untuk tanda tangan -->
<div class="pv-controls">

    @include('partials.shared.zoom-controls')

    <!-- Tombol SELESAI untuk TTD -->
    <form id="workflowSubmitForm"
          action="{{ route('kajur.tandatangan.submit', $document->id) }}"
          method="POST"
          style="display: inline;">
        @csrf
        <button type="submit" class="pv-primary-btn btn-blue">
            Selesai
        </button>
    </form>

</div>

<!-- Popup notifikasi -->
@include('partials.shared.popup-modal', [
    'modalId'       => 'ttdNotifPopup',
    'title'         => 'Kirim Notifikasi Email',
    'inputIdPrefix' => 'ttd',
    'defaultSubject'=> 'Dokumen Telah Ditandatangani',
    'showNotes'     => false,
    'cancelBtnId'   => 'batalKirimTTD',
    'confirmBtnId'  => 'konfirmasiKirimTTD'
])
