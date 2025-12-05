// Simple Modal Manager (FINAL)
// Usage:
// ModalManager.show({title, body, footer})
// ModalManager.loadAndShow(url, options)
// Handles built-in keys including upload-confirm and paraf-confirm (Ya / Tidak)

(function (window) {
    const modalEl = document.getElementById('globalModal');
    const titleEl = document.getElementById('globalModalTitle');
    const bodyEl = document.getElementById('globalModalBody');
    const footerEl = document.getElementById('globalModalFooter');
    let bsModal = null;

    function ensureInstance() {
        if (!bsModal && modalEl) bsModal = new bootstrap.Modal(modalEl, { backdrop: 'static' });
    }

    function clear() {
        if (titleEl) titleEl.innerHTML = '';
        if (bodyEl) bodyEl.innerHTML = '';
        if (footerEl) footerEl.innerHTML = '';
        // Reset close button visibility
        showCloseButton();
    }

    function hideCloseButton() {
        const closeBtn = document.getElementById('globalModalCloseBtn');
        if (closeBtn) closeBtn.style.display = 'none';
    }

    function showCloseButton() {
        const closeBtn = document.getElementById('globalModalCloseBtn');
        if (closeBtn) closeBtn.style.display = '';
    }

    function show({ title = '', body = '', footer = '', modalSize = 'sm' } = {}) {
        if (!titleEl || !bodyEl || !footerEl) return;
        titleEl.innerHTML = title || 'Informasi';
        bodyEl.innerHTML = body || '';
        footerEl.innerHTML = footer || '';

        // Update modal size
        const modalDialog = modalEl?.querySelector('.modal-dialog');
        if (modalDialog) {
            modalDialog.classList.remove('modal-sm', 'modal-md', 'modal-lg');
            if (modalSize && modalSize !== 'default') {
                modalDialog.classList.add('modal-' + modalSize);
            }
        }

        ensureInstance();
        bsModal.show();
    }

    async function loadAndShow(url, options = {}) {
        try {
            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!res.ok) throw new Error('Network error');
            const html = await res.text();
            show({ title: options.title || '', body: html, footer: options.footer || '' });
        } catch (err) {
            console.error('Modal load error', err);
            show({ title: 'Error', body: '<p>Gagal memuat konten.</p>' });
        }
    }

    function hide() {
        if (bsModal) {
            bsModal.hide();
        }
        // bersihkan kelas custom bila ada
        if (modalEl && modalEl.classList.contains('modal-custom-applied')) {
            modalEl.classList.remove('modal-custom-applied');
        }
        // reset modal size to default (sm)
        const modalDialog = modalEl?.querySelector('.modal-dialog');
        if (modalDialog) {
            modalDialog.classList.remove('modal-md', 'modal-lg');
            modalDialog.classList.add('modal-sm');
        }
    }

    // Convenience confirm dialog (Bootstrap-only styling)
    function confirm(options = {}) {
        const title = options.title || 'Konfirmasi';
        const message = options.message || '';
        const okText = options.okText || 'Ya';
        const cancelText = options.cancelText || 'Tidak';
        const onOk = options.onOk || function () { };
        const onCancel = options.onCancel || function () { };

        const footer = `
            <div class="d-flex justify-content-center gap-2 w-100">
                <button type="button" class="btn btn-success rounded-pill px-3 fw-semibold btn-sm" id="modalOkBtn">${okText}</button>
                <button type="button" class="btn btn-danger rounded-pill px-3 fw-semibold btn-sm" id="modalCancelBtn">${cancelText}</button>
            </div>
        `;

        // Body center aligned; caller can include <p class="fw-bold"> untuk pertanyaan tebal
        const bodyHtml = `<div class="text-center"><p class="mb-2">${message}</p></div>`;

        // Tampilkan modal
        show({ title: `<span class="fs-6 fw-bold">${title}</span>`, body: bodyHtml, footer });

        if (modalEl) modalEl.classList.add('modal-custom-applied');

        // Pasang listener setelah render
        setTimeout(() => {
            const okBtn = document.getElementById('modalOkBtn');
            const cancelBtn = document.getElementById('modalCancelBtn');

            if (okBtn) {
                okBtn.addEventListener('click', function (ev) {
                    ev.preventDefault();
                    try { onOk(); } catch (e) { console.error(e); }
                    hide();
                }, { once: true });
            }

            if (cancelBtn) {
                cancelBtn.addEventListener('click', function (ev) {
                    ev.preventDefault();
                    try { onCancel(); } catch (e) { console.error(e); }
                    hide();
                }, { once: true });
            }
        }, 60);
    }

    // Helper to get CSRF token from meta
    function getCsrf() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    // Event delegation: open modal from elements with data-modal-* attributes
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-modal]');
        if (!btn) return;
        e.preventDefault();

        const modalKey = btn.getAttribute('data-modal');
        const url = btn.getAttribute('data-modal-url');
        const title = btn.getAttribute('data-modal-title') || '';
        const bodyContent = btn.getAttribute('data-modal-body');

        // If data-modal-url present, load remote html
        if (url) {
            loadAndShow(url, { title });
            return;
        }

        // --- HANDLE: Upload confirm (Ya / Tidak) ---
        if (modalKey === 'upload-confirm') {
            const isEdit = btn.getAttribute('data-is-edit') === 'true';
            const message = isEdit ? 'Dokumen lama akan diganti dengan yang baru.' : 'Surat akan disimpan ke sistem.';

            confirm({
                title: 'Konfirmasi',
                message: message + '<p class="fw-bold mt-3">Kirim notifikasi email ke penandatangan pertama?</p>',
                okText: 'Ya',
                cancelText: 'Tidak',
                onOk: function () {
                    const hidden = document.getElementById('sendNotificationValue');
                    if (hidden) {
                        hidden.value = '1';
                        const f = hidden.closest('form');
                        if (f) f.submit();
                    }
                },
                onCancel: function () {
                    const hidden = document.getElementById('sendNotificationValue');
                    if (hidden) {
                        hidden.value = '0';
                        const f = hidden.closest('form');
                        if (f) f.submit();
                    }
                }
            });
            return;
        }

        // Specific built-in keys
        if (modalKey === 'logout') {
            // Build logout form with CSRF
            const csrf = getCsrf();
            const form = `
                <form method="POST" action="/logout" id="logoutForm">
                    <input type="hidden" name="_token" value="${csrf}">
                </form>
            `;
            const footer = `
                <div class="d-flex justify-content-end gap-2 w-100">
                    <button type="button" class="btn btn-secondary rounded-pill px-3" id="modalCancelBtn">Batal</button>
                    <button type="button" class="btn btn-danger rounded-pill px-3" id="modalLogoutConfirm">Logout</button>
                </div>
            `;
            show({ title: title || '<span class="fs-6 fw-bold">Konfirmasi Logout</span>', body: '<p class="mb-2">Apakah Anda yakin ingin logout?</p>' + form, footer });
            hideCloseButton();

            setTimeout(() => {
                const cancelBtn = document.getElementById('modalCancelBtn');
                const logoutBtn = document.getElementById('modalLogoutConfirm');
                if (cancelBtn) cancelBtn.addEventListener('click', hide, { once: true });
                if (logoutBtn) logoutBtn.addEventListener('click', () => {
                    const f = document.getElementById('logoutForm');
                    if (f) f.submit();
                }, { once: true });
            }, 60);
            return;
        }

        // Paraf confirmation (Ya / Tidak only)
        if (modalKey === 'paraf-confirm') {
            const body = '<p>Paraf tidak bisa diubah lagi setelah Anda konfirmasi.</p><p class="fw-bold mt-3">Apakah Anda ingin mengirim email notifikasi ke orang selanjutnya?</p>';

            const footer = `
                <div class="d-flex justify-content-center gap-2 w-100">
                    <button type="button" class="btn btn-success rounded-pill px-3 btn-sm" id="modalParafYes">Ya</button>
                    <button type="button" class="btn btn-danger rounded-pill px-3 btn-sm" id="modalParafNo">Tidak</button>
                </div>
            `;

            show({ title: title || '<span class="fs-6 fw-bold">Konfirmasi Paraf</span>', body, footer });

            setTimeout(() => {
                const yesBtn = document.getElementById('modalParafYes');
                const noBtn = document.getElementById('modalParafNo');

                if (yesBtn) yesBtn.addEventListener('click', () => {
                    const input = document.getElementById('sendNotifParaf');
                    if (input) input.value = '1';
                    const f = document.getElementById('formParaf');
                    if (f) f.submit();
                }, { once: true });

                if (noBtn) noBtn.addEventListener('click', () => {
                    const input = document.getElementById('sendNotifParaf');
                    if (input) input.value = '0';
                    const f = document.getElementById('formParaf');
                    if (f) f.submit();
                }, { once: true });
            }, 60);
            return;
        }

        // If inline body provided
        if (bodyContent) {
            const footer = btn.getAttribute('data-modal-footer') || '';
            show({ title, body: bodyContent, footer });
            return;
        }

        // Review -> open revise form and submit to provided action URL
        if (modalKey === 'review-revise') {
            const actionUrl = btn.getAttribute('data-action-url');
            const uploaderEmail = btn.getAttribute('data-uploader-email') || '';
            const defaultSubject = btn.getAttribute('data-default-subject') || '';

            const body = `
                <div class="mb-3">
                    <label class="form-label fw-bold">Email Tujuan</label>
                    <input type="email" class="form-control" value="${uploaderEmail}" readonly style="background-color: #e9ecef;">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Subjek</label>
                    <input type="text" name="subjek" id="modalReviseSubjek" class="form-control" value="${defaultSubject}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Catatan Revisi</label>
                    <textarea name="catatan" id="modalReviseCatatan" class="form-control" rows="4" required placeholder="Jelaskan perbaikan..."></textarea>
                </div>
            `;

            const footer = `
                <div class="d-flex justify-content-end gap-2 w-100">
                    <button type="button" class="btn btn-secondary rounded-pill px-3" id="modalCancelBtn">Batal</button>
                    <button type="button" class="btn btn-danger rounded-pill px-3" id="modalSendRevise">Kirim Revisi</button>
                </div>
            `;

            show({ title: title || '<span class="fs-6 fw-bold">Kirim Permintaan Revisi</span>', body, footer });
            hideCloseButton();

            setTimeout(() => {
                const cancelBtn = document.getElementById('modalCancelBtn');
                const sendBtn = document.getElementById('modalSendRevise');
                if (cancelBtn) cancelBtn.addEventListener('click', hide, { once: true });
                if (sendBtn) sendBtn.addEventListener('click', () => {
                    // Build temporary form and submit
                    const csrf = getCsrf();
                    const subjek = document.getElementById('modalReviseSubjek')?.value || '';
                    const catatan = document.getElementById('modalReviseCatatan')?.value || '';

                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = actionUrl;
                    form.style.display = 'none';

                    const token = document.createElement('input');
                    token.type = 'hidden'; token.name = '_token'; token.value = csrf;
                    const s = document.createElement('input'); s.type = 'hidden'; s.name = 'subjek'; s.value = subjek;
                    const c = document.createElement('input'); c.type = 'hidden'; c.name = 'catatan'; c.value = catatan;

                    form.appendChild(token);
                    form.appendChild(s);
                    form.appendChild(c);
                    document.body.appendChild(form);
                    form.submit();
                }, { once: true });
            }, 60);
            return;
        }

        // Download confirmation for finalisasi
        if (modalKey === 'finalisasi-download') {
            const downloadUrl = btn.getAttribute('data-download-url');
            const fileName = btn.getAttribute('data-file-name') || 'Document';

            const body = `
                <div class="text-center mb-3">
                    <svg width="50" height="50" viewBox="0 0 24 24" fill="none" style="margin: 0 auto;">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16c0 1.1.9 2 2 2h12a2 2 0 0 0 2-2V8l-6-6z" stroke="#0F172A" stroke-width="1.8"/>
                        <path d="M14 2v6h6" stroke="#0F172A" stroke-width="1.8"/>
                        <text x="8" y="17" fill="#0F172A" font-size="7" font-weight="600">PDF</text>
                    </svg>
                </div>
                <p class="text-center"><strong>${fileName}</strong></p>
                <p class="text-center">Apakah Anda ingin mengunduh file ini?</p>
            `;

            const footer = `
                <div class="d-flex justify-content-center gap-2 w-100">
                    <button type="button" class="btn btn-secondary rounded-pill px-3" id="modalCancelBtn">Batal</button>
                    <button type="button" class="btn btn-primary rounded-pill px-3" id="modalDownloadBtn">Download</button>
                </div>
            `;

            show({ title: '<span class="fs-6 fw-bold">Unduh File</span>', body, footer });
            hideCloseButton();

            setTimeout(() => {
                const cancelBtn = document.getElementById('modalCancelBtn');
                const downloadBtn = document.getElementById('modalDownloadBtn');
                if (cancelBtn) cancelBtn.addEventListener('click', hide, { once: true });
                if (downloadBtn) downloadBtn.addEventListener('click', () => {
                    window.location.href = downloadUrl;
                    hide();
                }, { once: true });
            }, 60);
            return;
        }

        // Submission notification - ask to send email
        if (modalKey === 'submission-notification') {
            const actionUrl = btn.getAttribute('data-action-url');
            const defaultSubject = btn.getAttribute('data-default-subject') || 'Pemberitahuan Proses Alur';

            const body = `
                <div class="mb-3">
                    <label class="form-label fw-bold">Email Tujuan</label>
                    <input type="email" id="modalSubmitEmail" class="form-control" placeholder="recipient@example.com" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Subjek</label>
                    <input type="text" id="modalSubmitSubjek" class="form-control" value="${defaultSubject}" required>
                </div>
            `;

            const footer = `
                <div class="d-flex justify-content-end gap-2 w-100">
                    <button type="button" class="btn btn-secondary rounded-pill px-3" id="modalCancelBtn">Batal</button>
                    <button type="button" class="btn btn-success rounded-pill px-3" id="modalSubmitSend">Kirim</button>
                </div>
            `;

            show({ title: '<span class="fs-6 fw-bold">Kirim Notifikasi</span>', body, footer });

            setTimeout(() => {
                const cancelBtn = document.getElementById('modalCancelBtn');
                const sendBtn = document.getElementById('modalSubmitSend');
                if (cancelBtn) cancelBtn.addEventListener('click', hide, { once: true });
                if (sendBtn) sendBtn.addEventListener('click', () => {
                    const csrf = getCsrf();
                    const email = document.getElementById('modalSubmitEmail')?.value || '';
                    const subjek = document.getElementById('modalSubmitSubjek')?.value || '';

                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = actionUrl;
                    form.style.display = 'none';

                    const token = document.createElement('input');
                    token.type = 'hidden'; token.name = '_token'; token.value = csrf;
                    const e = document.createElement('input'); e.type = 'hidden'; e.name = 'email'; e.value = email;
                    const s = document.createElement('input'); s.type = 'hidden'; s.name = 'subjek'; s.value = subjek;

                    form.appendChild(token);
                    form.appendChild(e);
                    form.appendChild(s);
                    document.body.appendChild(form);
                    form.submit();
                }, { once: true });
            }, 60);
            return;
        }

        // Finalisasi confirmation (download + finalize)
        if (modalKey === 'finalisasi-confirm') {
            const finalizeUrl = btn.getAttribute('data-finalize-url');
            const downloadUrl = btn.getAttribute('data-download-url');
            const csrf = getCsrf();

            const body = `
                <div class="mb-3">
                    <a href="${downloadUrl}" class="btn btn-success w-100 mb-2 rounded-pill">Download Dokumen</a>
                </div>
            `;

            const footer = `
                <div class="d-flex justify-content-end gap-2 w-100">
                    <button type="button" class="btn btn-secondary rounded-pill px-3" id="modalCancelBtn">Batalkan</button>
                    <button type="button" class="btn btn-danger rounded-pill px-3" id="modalFinalizeBtn">Ya, Finalisasi Sekarang</button>
                </div>
            `;

            show({ title: '<span class="fs-6 fw-bold">Finalisasi Dokumen</span>', body, footer, modalSize: 'md' });
            hideCloseButton();

            setTimeout(() => {
                const cancelBtn = document.getElementById('modalCancelBtn');
                const finalBtn = document.getElementById('modalFinalizeBtn');
                if (cancelBtn) cancelBtn.addEventListener('click', hide, { once: true });
                if (finalBtn) finalBtn.addEventListener('click', () => {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = finalizeUrl;
                    form.style.display = 'none';

                    const token = document.createElement('input');
                    token.type = 'hidden'; token.name = '_token'; token.value = csrf;
                    const aksi = document.createElement('input');
                    aksi.type = 'hidden'; aksi.name = 'aksi'; aksi.value = 'final';

                    form.appendChild(token);
                    form.appendChild(aksi);
                    document.body.appendChild(form);
                    form.submit();
                }, { once: true });
            }, 60);
            return;
        }

        // Fallback: simple confirm with message data-modal-message
        const message = btn.getAttribute('data-modal-message') || '';
        if (message) {
            confirm({
                title,
                message,
                onOk: function () {
                    // nothing by default; caller can override
                }
            });
            return;
        }
    });

    // Export
    window.ModalManager = {
        show, loadAndShow, hide, confirm
    };

})(window);
