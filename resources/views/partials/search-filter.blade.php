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

        <!-- Filter Status (Fixed width or auto) -->
        <select name="status" class="form-select" style="width: 200px; flex-shrink: 0;">
            <option value="">Semua Status</option>
            <option value="Ditinjau" {{ request('status') == 'Ditinjau' ? 'selected' : '' }}>Ditinjau</option>
            <option value="Diparaf" {{ request('status') == 'Diparaf' ? 'selected' : '' }}>Diparaf</option>
            <option value="Ditandatangani" {{ request('status') == 'Ditandatangani' ? 'selected' : '' }}>Ditandatangani</option>
            <option value="Perlu Revisi" {{ request('status') == 'Perlu Revisi' ? 'selected' : '' }}>Perlu Revisi</option>
            <option value="Selesai" {{ request('status') == 'Selesai' ? 'selected' : '' }}>Final</option>
        </select>

        <!-- Submit Button -->
        <button type="submit" class="btn btn-primary">Cari</button>
        
        @if(request('search') || request('status'))
            <a href="{{ url()->current() }}" class="btn btn-outline-secondary">Reset</a>
        @endif
    </form>
</div>
