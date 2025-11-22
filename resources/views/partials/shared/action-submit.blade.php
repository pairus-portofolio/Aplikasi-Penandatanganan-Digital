<!-- Kontrol bawah halaman: zoom + tombol selesai -->
<div class="pv-controls">

    @include('partials.shared.zoom-controls')

    <!-- Tombol SELESAI langsung submit Workflow -->
    <form id="workflowSubmitForm"
          action="{{ $actionUrl }}"
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
    'modalId'       => $modalId,
    'title'         => 'Kirim Notifikasi Email',
    'inputIdPrefix' => $inputIdPrefix,
    'defaultSubject'=> $defaultSubject,
    'showNotes'     => false,
    'cancelBtnId'   => $cancelBtnId,
    'confirmBtnId'  => $confirmBtnId
])
