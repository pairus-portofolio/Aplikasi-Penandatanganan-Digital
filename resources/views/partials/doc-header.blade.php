<div class="pv-header" style="position: sticky; top: 50px; z-index: 10;">
    <div class="pv-name">{{ $judulSurat ?? 'Nama surat' }}</div>

    <div class="doc-pagination">
        {{-- TAMBAHKAN ID="curr_page" DAN ID="total_pages" DI SINI --}}
        Halaman
        <span id="curr_page">{{ $currentPage }}</span>
        dari
        <span id="total_pages">{{ $totalPages }}</span>
    </div>
</div>