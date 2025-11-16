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

    {{-- ID: 'mintaRevisiBtn' (tanpa 'a') --}}
    <button type="button" class="pv-primary-btn" id="mintaRevisiBtn">
        Minta Revisi
    </button>
</div>

{{-- HTML untuk Popup Revisi --}}
<div class="popup" id="revisiPopup">
    <div class="popup-content">
        <h3>Kirim Notifikasi Revisi via Email</h3>
        
        <div class="form-group">
            <label for="penerimaBp">Penerima:</label>
            <input type="email" id="penerimaBp" class="form-input" placeholder="Email penerima" value="nama@gmail.com" readonly>
        </div>

        <div class="form-group">
            <label for="subjekBp">Subjek:</label>
            <input type="text" id="subjekBp" class="form-input" placeholder="Subjek email" value="Permintaan Revisi Dokumen">
        </div>

        <div class="form-group">
            <label for="catatanBp">Catatan:</label>
            <textarea id="catatanBp" class="form-textarea" rows="4" placeholder="Masukkan catatan revisi..."></textarea>
        </div>

        <div class="popup-btns">
            <button type="button" class="btn-cancel" id="batalBp">Batal</button>
            <button type="button" class="btn-confirm" id="kirimBp">Kirim</button>
        </div>
    </div>
</div>