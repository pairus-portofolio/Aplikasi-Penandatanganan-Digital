<div class="popup" id="{{ $modalId }}">
    <div class="popup-content">
        <h3>{{ $title }}</h3>
        
        {{-- Form Group: Penerima --}}
        <div class="form-group">
            <label for="{{ $inputIdPrefix }}_penerima">Penerima:</label>
            <input type="email" id="{{ $inputIdPrefix }}_penerima" class="form-input" value="nama@gmail.com" readonly>
        </div>

        {{-- Form Group: Subjek --}}
        <div class="form-group">
            <label for="{{ $inputIdPrefix }}_subjek">Subjek:</label>
            <input type="text" id="{{ $inputIdPrefix }}_subjek" class="form-input" value="{{ $defaultSubject }}">
        </div>

        {{-- Form Group: Catatan (Opsional) --}}
        @if(isset($showNotes) && $showNotes)
        <div class="form-group">
            <label for="{{ $inputIdPrefix }}_catatan">Catatan:</label>
            <textarea id="{{ $inputIdPrefix }}_catatan" class="form-textarea" rows="4" placeholder="Masukkan catatan..."></textarea>
        </div>
        @endif

        {{-- Tombol Aksi --}}
        <div class="popup-btns">
            <button type="button" class="btn-cancel" id="{{ $cancelBtnId }}">Batal</button>
            <button type="button" class="btn-confirm" id="{{ $confirmBtnId }}">Kirim</button>
        </div>
    </div>
</div>