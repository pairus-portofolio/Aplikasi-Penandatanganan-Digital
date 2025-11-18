<!-- Kontrol dokumen: zoom + tombol permintaan revisi -->
<div class="pv-controls">

    <!-- Include kontrol zoom -->
    @include('partials.shared.zoom-controls')

    <!-- Tombol membuka popup permintaan revisi -->
    <button type="button" class="pv-primary-btn" id="mintaRevisiBtn">
        Minta Revisi
    </button>
</div>

<!-- Include popup modal untuk mengirim notifikasi revisi -->
@include('partials.shared.popup-modal', [
    'modalId'       => 'revisiPopup',
    'title'         => 'Kirim Notifikasi Revisi via Email',
    'inputIdPrefix' => 'revisi',
    'defaultSubject'=> 'Permintaan Revisi Dokumen',
    'showNotes'     => true,
    'cancelBtnId'   => 'batalBp',
    'confirmBtnId'  => 'kirimBp'
])
