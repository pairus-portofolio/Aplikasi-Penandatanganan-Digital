<div class="pv-controls">
    {{-- Panggil Zoom dari folder shared --}}
    @include('partials.shared.zoom-controls')

    <button type="button" class="pv-primary-btn" id="mintaRevisiBtn">
        Minta Revisi
    </button>
</div>

{{-- Panggil Popup dari folder shared --}}
@include('partials.shared.popup-modal', [
    'modalId'       => 'revisiPopup',
    'title'         => 'Kirim Notifikasi Revisi via Email',
    'inputIdPrefix' => 'revisi',
    'defaultSubject'=> 'Permintaan Revisi Dokumen',
    'showNotes'     => true,
    'cancelBtnId'   => 'batalBp',
    'confirmBtnId'  => 'kirimBp'
])