<div class="pv-controls">
    {{-- Tombol zoom --}}
    <div class="pv-zoom">
        <button type="button" class="pv-ctrl-btn" id="zoomOutBtn">
            <i class="fa-solid fa-magnifying-glass-minus"></i>
        </button>
        <button type="button" class="pv-ctrl-btn" id="zoomInBtn">
            <i class="fa-solid fa-magnifying-glass-plus"></i>
        </button>
    </div>

    {{-- Tombol Kirim Notifikasi (Biru) --}}
    <button type="button" class="pv-primary-btn btn-blue" id="kirimNotifikasiBtn">
        Kirim Notifikasi
    </button>
</div>

{{-- Popup paraf "Penerima" dan "Subjek".--}}
<div class="popup form-popup" id="parafNotifPopup">
    <div class="popup-content">
        <h3>Kirim Notifikasi Email</h3>
        
        <div class="form-group">
            <label for="penerimaParaf">Penerima:</label>
            <input type="email" id="penerimaParaf" class="form-input" placeholder="Email penerima" value="nama@gmail.com" readonly>
        </div>

        <div class="form-group">
            <label for="subjekParaf">Subjek:</label>
            <input type="text" id="subjekParaf" class="form-input" placeholder="Subjek email" value="Dokumen Selesai Diparaf">
        </div>

        {{-- BLOK CATATAN YANG DIHAPUS --}}
        {{-- 
        <div class="form-group">
            <label for="catatanParaf">Catatan:</label>
            <textarea id="catatanParaf" class="form-textarea" rows="4" placeholder="Masukkan catatan (opsional)..."></textarea>
        </div>
        --}}

        <div class="popup-btns">
            <button type="button" class="btn-cancel" id="batalKirim">Batal</button>
            <button type="button" class="btn-confirm" id="konfirmasiKirim">Kirim</button>
        </div>
    </div>
</div>