<div id="download-confirmation-popup" class="popup-overlay" style="display:none;">
    <div class="popup-card">

        <h3 class="popup-title">Apakah Anda ingin mengunduh file?</h3>

        <div class="popup-file">
            {{-- ICON PDF INLINE SVG --}}
            <span class="icon-pdf">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16c0 1.1.9 2 2 2h12a2 2 0 0 0 2-2V8l-6-6z"
                          stroke="#0F172A" stroke-width="1.8"/>
                    <path d="M14 2v6h6" stroke="#0F172A" stroke-width="1.8"/>
                    <text x="8" y="17" fill="#0F172A" font-size="7" font-weight="600">PDF</text>
                </svg>
            </span>

            <div class="file-name">
                {{ $document->original_name ?? $document->judul_surat }}
            </div>
        </div>

        <div class="popup-actions">
            <button class="btn-cancel" data-popup-close="#download-confirmation-popup">
                Batal
            </button>

            <button
                class="btn-download"
                data-download-url="{{ route('tu.finalisasi.download', $document->id) }}"
            >
                Download
            </button>
        </div>

    </div>
</div>
