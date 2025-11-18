<div class="pv-controls">
    {{-- 1. Panggil Fungsi Zoom (REUSE) --}}
    @include('partials.shared.zoom-controls')

    {{-- 
       PERBAIKAN: 
       1. Pastikan class "pv-primary-btn btn-blue" ada. Class 'btn-blue' inilah yang membuatnya biru.
       2. ID tetap 'btnSelesaiTtd' agar dikenali JS, tapi teksnya bisa kita ubah jadi "Kirim Notifikasi" jika mau.
    --}}
    <button type="button" class="pv-primary-btn btn-blue" id="btnSelesaiTtd">
        Kirim Notifikasi
    </button>
</div>

{{-- 2. Panggil Popup Notifikasi (SAMA SEPERTI PARAF) --}}
@include('partials.shared.popup-modal', [
    'modalId'       => 'ttdNotifPopup',
    'title'         => 'Kirim Notifikasi Email', 
    'inputIdPrefix' => 'ttd',
    'defaultSubject'=> 'Dokumen Telah Ditandatangani',
    'showNotes'     => false,    
    'cancelBtnId'   => 'batalTtd',
    'confirmBtnId'  => 'kirimTtd'
])