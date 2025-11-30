<!-- Kontrol dokumen: zoom + tombol permintaan revisi -->
<div class="pv-controls">
    <!-- Include kontrol zoom (pastikan file ini ada) -->
    @include('partials.shared.zoom-controls')

    <!-- Tombol pemicu modal -->
    <!-- type="button" sangat penting agar tidak dianggap submit -->
    <button type="button" class="pv-primary-btn" data-bs-toggle="modal" data-bs-target="#revisiPopup">
        Minta Revisi
    </button>
</div>

<!-- Modal Revisi -->
<!-- ID "revisiPopup" harus sama dengan data-bs-target di tombol pemicu -->
<div class="modal fade" id="revisiPopup" tabindex="-1" aria-labelledby="revisiLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        
        <!-- FORM: Action mengarah ke route revise di ReviewController -->
        <form action="{{ route('kaprodi.review.revise', $document->id) }}" method="POST" style="display: contents;">
            @csrf
            
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="revisiLabel">Kirim Permintaan Revisi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body" style="text-align: left;">
                    <!-- 1. Email TU (Otomatis & Readonly) -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Email Tujuan (TU)</label>
                        <!-- Mengambil email uploader otomatis -->
                        <input type="email" class="form-control" 
                               value="{{ $document->uploader->email ?? 'Email tidak ditemukan' }}" 
                               readonly 
                               style="background-color: #e9ecef; cursor: not-allowed;">
                    </div>

                    <!-- 2. Subjek (Otomatis) -->
                    <div class="mb-3">
                        <label for="subjek" class="form-label fw-bold">Subjek</label>
                        <input type="text" name="subjek" class="form-control" id="subjek" 
                               value="Revisi Dokumen: {{ $document->judul_surat }}" required>
                    </div>

                    <!-- 3. Catatan Revisi -->
                    <div class="mb-3">
                        <label for="catatan" class="form-label fw-bold">Catatan Revisi</label>
                        <textarea name="catatan" class="form-control" id="catatan" rows="4" 
                                  placeholder="Tuliskan bagian yang perlu diperbaiki oleh TU..." required></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <!-- Tombol Batal: data-bs-dismiss="modal" menutup pop-up tanpa submit -->
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    
                    <!-- Tombol Kirim: type="submit" mengirim form -->
                    <button type="submit" class="btn btn-danger">Kirim & Minta Revisi</button>
                </div>
            </div>
        </form>
        
    </div>
</div>