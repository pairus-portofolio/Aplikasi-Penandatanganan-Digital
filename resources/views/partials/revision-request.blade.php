<div id="revisionRequestModal" class="custom-popup-modal">
    <div class="custom-popup-content">
        <h3 class="custom-popup-title">Kirim notifikasi revisi via email ?</h3>
        <div class="custom-popup-form">
            <div class="form-group">
                <label for="recipientEmail" class="form-label">Penerima:</label>
                <input type="email" id="recipientEmail" class="form-input" value="nama@gmail.com" readonly>
            </div>
            <div class="form-group">
                <label for="subjectEmail" class="form-label">Subjek:</label>
                <input type="text" id="subjectEmail" class="form-input" placeholder="Subjek email...">
            </div>
            <div class="form-group">
                <label for="notesEmail" class="form-label">Catatan:</label>
                <textarea id="notesEmail" class="form-textarea" placeholder="Catatan email..."></textarea>
            </div>
        </div>
        <div class="custom-popup-actions">
            <button id="cancelRevisionBtn" class="btn-custom-cancel">Batal</button>
            <button id="sendRevisionBtn" class="btn-custom-send">Kirim</button>
        </div>
    </div>
</div>