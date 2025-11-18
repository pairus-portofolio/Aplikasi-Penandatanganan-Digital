<!-- Kontrol bawah halaman: zoom + tombol kirim notifikasi -->
<div class="pv-controls">

    <!-- Include kontrol zoom -->
    @include('partials.shared.zoom-controls')

    <!-- Tombol untuk membuka popup notifikasi email -->
    <button type="button" class="pv-primary-btn btn-blue" id="kirimNotifikasiBtn">
        Kirim Notifikasi
    </button>
</div>

<!-- Include popup modal pengiriman notifikasi email -->
@include('partials.shared.popup-modal', [
    'modalId'       => 'parafNotifPopup',
    'title'         => 'Kirim Notifikasi Email',
    'inputIdPrefix' => 'paraf',
    'defaultSubject'=> 'Dokumen Selesai Diparaf',
    'showNotes'     => false,
    'cancelBtnId'   => 'batalKirim',
    'confirmBtnId'  => 'konfirmasiKirim'
])
