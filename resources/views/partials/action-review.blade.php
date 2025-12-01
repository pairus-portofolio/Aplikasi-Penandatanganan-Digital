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
<div class="modal fade" id="revisiPopup" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        
        <form action="{{ route('kaprodi.review.revise', $document->id) }}" method="POST" style="display: contents;">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Kirim Permintaan Revisi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body text-start">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Email Tujuan</label>
                        <input type="email" class="form-control" 
                               value="{{ $document->uploader->email ?? '-' }}" 
                               readonly style="background-color: #e9ecef;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Subjek</label>
                        <input type="text" name="subjek" class="form-control" 
                               value="Revisi Dokumen: {{ $document->judul_surat }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Catatan Revisi</label>
                        <textarea name="catatan" class="form-control" rows="4" required placeholder="Jelaskan perbaikan..."></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Kirim Revisi</button>
                </div>
            </div>
        </form>
    </div>
</div>