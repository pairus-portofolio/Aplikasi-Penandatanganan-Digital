{{-- Controls bawah halaman --}}
<div class="pv-controls">
    {{-- Zoom selalu tampil --}}
    @include('partials.shared.zoom-controls')

    {{-- TOMBOL REVISI muncul jika: kaprodi + status review --}}
    @if(auth()->user()->role === 'kaprodi' && $document->status === 'review')
        <button type="button"
            class="pv-primary-btn"
            data-bs-toggle="modal"
            data-bs-target="#revisiPopup">
            Minta Revisi
        </button>
    @endif
</div>

{{-- ========== MODAL REVISI (ONLY IF KAPRODI + REVIEW) ========== --}}
@if(auth()->user()->role === 'kaprodi' && $document->status === 'review')
<div class="modal fade" id="revisiPopup" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">

        <form action="{{ route('kaprodi.review.revise', $document->id) }}"
              method="POST" style="display: contents;">
            @csrf

            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Kirim Permintaan Revisi</h5>
                    <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal">
                    </button>
                </div>

                <div class="modal-body text-start">

                    {{-- Email tujuan --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold">Email Tujuan</label>
                        <input type="email"
                               class="form-control"
                               value="{{ $document->uploader->email ?? '-' }}"
                               readonly
                               style="background:#e9ecef;">
                    </div>

                    {{-- Subjek --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold">Subjek</label>
                        <input type="text"
                               name="subjek"
                               class="form-control"
                               value="Revisi Dokumen: {{ $document->judul_surat }}"
                               required>
                    </div>

                    {{-- Catatan revisi --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold">Catatan Revisi</label>
                        <textarea name="catatan"
                                  class="form-control"
                                  rows="4"
                                  required
                                  placeholder="Berikan catatan perbaikan..."></textarea>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-danger">
                        Kirim Revisi
                    </button>
                </div>

            </div>

        </form>

    </div>
</div>
@endif
