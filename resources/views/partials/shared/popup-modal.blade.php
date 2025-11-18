<!-- Popup utama -->
<div class="popup" id="{{ $modalId }}">

    <!-- Konten badan popup -->
    <div class="popup-content">

        <!-- Judul popup -->
        <h3>{{ $title }}</h3>

        <!-- Input penerima (readonly) -->
        <div class="form-group">
            <label for="{{ $inputIdPrefix }}_penerima">Penerima:</label>
            <input type="email" id="{{ $inputIdPrefix }}_penerima" class="form-input" value="nama@gmail.com" readonly>
        </div>

        <!-- Input subjek pesan -->
        <div class="form-group">
            <label for="{{ $inputIdPrefix }}_subjek">Subjek:</label>
            <input type="text" id="{{ $inputIdPrefix }}_subjek" class="form-input" value="{{ $defaultSubject }}">
        </div>

        <!-- Input catatan opsional -->
        @if(isset($showNotes) && $showNotes)
        <div class="form-group">
            <label for="{{ $inputIdPrefix }}_catatan">Catatan:</label>
            <textarea id="{{ $inputIdPrefix }}_catatan" class="form-textarea" rows="4" placeholder="Masukkan catatan..."></textarea>
        </div>
        @endif

        <!-- Tombol aksi popup -->
        <div class="popup-btns">
            <button type="button" class="btn-cancel" id="{{ $cancelBtnId }}">Batal</button>
            <button type="button" class="btn-confirm" id="{{ $confirmBtnId }}">Kirim</button>
        </div>

    </div>
</div>
