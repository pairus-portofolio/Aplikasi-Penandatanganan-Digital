<div class="search-filter-container w-100 mb-4">
    <form action="{{ url()->current() }}" method="GET" class="d-flex gap-2 w-100">
        <!-- Search Input (Flex Grow to fill space) -->
        <div class="input-group flex-grow-1">
            <span class="input-group-text bg-white border-end-0">
                <i class="fas fa-search text-muted"></i>
            </span>
            <input type="text"
                   name="search"
                   class="form-control border-start-0 ps-0"
                   placeholder="Cari surat atau pengunggah..."
                   value="{{ request('search') }}">
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn btn-primary">Cari</button>
        
        {{-- Tombol Reset hanya muncul jika ada keyword pencarian --}}
        @if(request('search'))
            <a href="{{ url()->current() }}" class="btn btn-outline-secondary">Reset</a>
        @endif
    </form>
</div>
