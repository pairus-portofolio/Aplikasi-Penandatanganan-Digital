<!-- Kontrol bawah halaman -->
<div class="pv-controls">

    {{-- Zoom selalu ada --}}
    @include('partials.shared.zoom-controls')

    {{-- === TAMPILKAN TOMBOL SELESAI HANYA JIKA SAATNYA TTD === --}}
    @if(
        auth()->user()->id === $document->current_signer_id
        && $document->status === 'paraf'  {{-- status dokumen masih menunggu TTD --}}
    )
        <button type="button" class="pv-primary-btn"
                data-bs-toggle="modal"
                data-bs-target="#ttdNotifPopup">
            Selesai
        </button>
    @endif

</div>

{{-- === FORM SUBMIT HIDDEN === --}}
@if(
    auth()->user()->id === $document->current_signer_id
    && $document->status === 'paraf'
)
<form action="{{ route('kajur.tandatangan.submit', $document->id) }}"
      method="POST" id="formTtd">
    @csrf
    <input type="hidden" name="send_notification" id="sendNotifTtd" value="0">
</form>

<!-- === MODAL KONFIRMASI === -->
<div class="modal fade" id="ttdNotifPopup" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-center">Konfirmasi Tanda Tangan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body text-center">
                <p>Tanda tangan tidak bisa diubah setelah Anda konfirmasi.</p>
                <p class="fw-bold">
                    Kirim email notifikasi ke penandatangan selanjutnya?
                </p>
            </div>

            <div class="modal-footer d-flex justify-content-center">
                <button type="button" class="btn btn-success" onclick="submitTtd(1)">Ya</button>
                <button type="button" class="btn btn-danger" onclick="submitTtd(0)">Tidak</button>
            </div>
        </div>

    </div>
</div>

<script>
    function submitTtd(val) {
        document.getElementById('sendNotifTtd').value = val;
        document.getElementById('formTtd').submit();
    }
</script>
@endif
