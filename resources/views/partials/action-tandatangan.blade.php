<!-- Kontrol halaman: zoom + tombol kirim notifikasi selesai tanda tangan -->
<div class="pv-controls">

    <!-- Include kontrol zoom -->
    @include('partials.shared.zoom-controls')

    <!-- Tombol membuka popup notifikasi tanda tangan -->
    <button type="button" class="pv-primary-btn btn-blue" id="btnSelesaiTtd">
        Kirim Notifikasi
    </button>
</div>

<!-- Include popup modal notifikasi email setelah dokumen ditandatangani -->
@include('partials.shared.popup-modal', [
    'modalId'       => 'ttdNotifPopup',
    'title'         => 'Kirim Notifikasi Email', 
    'inputIdPrefix' => 'ttd',
    'defaultSubject'=> 'Dokumen Telah Ditandatangani',
    'showNotes'     => false,    
    'cancelBtnId'   => 'batalTtd',
    'confirmBtnId'  => 'kirimTtd'
])
