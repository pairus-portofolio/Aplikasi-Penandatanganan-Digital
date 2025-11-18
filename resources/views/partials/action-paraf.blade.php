<div class="pv-controls">
    {{-- Panggil Zoom dari folder shared --}}
    @include('partials.shared.zoom-controls')

    <button type="button" class="pv-primary-btn btn-blue" id="kirimNotifikasiBtn">
        Kirim Notifikasi
    </button>
</div>

{{-- Panggil Popup dari folder shared --}}
@include('partials.shared.popup-modal', [
    'modalId'       => 'parafNotifPopup',
    'title'         => 'Kirim Notifikasi Email',
    'inputIdPrefix' => 'paraf',
    'defaultSubject'=> 'Dokumen Selesai Diparaf',
    'showNotes'     => false,
    'cancelBtnId'   => 'batalKirim',
    'confirmBtnId'  => 'konfirmasiKirim'
])