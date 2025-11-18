document.addEventListener('DOMContentLoaded', function() {
    
    // 1. LOGIKA ZOOM (Tetap digunakan agar user bisa baca surat)
    const page   = document.getElementById('previewPage');
    const zoomIn = document.getElementById('zoomInBtn');
    const zoomOut = document.getElementById('zoomOutBtn');
    let currentScale = 1; 

    if (page && zoomIn && zoomOut) {
        function applyZoom() { page.style.transform = 'scale(' + currentScale + ')'; }
        zoomIn.addEventListener('click', () => { currentScale = Math.min(currentScale + 0.1, 2); applyZoom(); });
        zoomOut.addEventListener('click', () => { currentScale = Math.max(currentScale - 0.1, 0.5); applyZoom(); });
    }

    // 2. LOGIKA TOMBOL TANDA TANGAN -> BUKA POPUP CAPTCHA
    const btnTrigger = document.getElementById('btnTriggerCaptcha');
    const popupCaptcha = document.getElementById('captchaPopup');
    const btnBatal = document.getElementById('batalCaptcha');
    const btnProses = document.getElementById('prosesTtd'); // Tombol Verifikasi (Dummy dulu)

    if (btnTrigger && popupCaptcha) {
        // Buka Popup saat tombol sidebar diklik
        btnTrigger.addEventListener('click', function() {
            popupCaptcha.classList.add('show');
        });

        // Tutup Popup (Batal)
        if (btnBatal) {
            btnBatal.addEventListener('click', function() {
                popupCaptcha.classList.remove('show');
            });
        }

        // Tutup Popup (Klik Overlay)
        popupCaptcha.addEventListener('click', function(e) {
            if (e.target === popupCaptcha) {
                popupCaptcha.classList.remove('show');
            }
        });

        // Logika Tombol Verifikasi (Hanya visual, menutup popup)
        if (btnProses) {
            btnProses.addEventListener('click', function() {
                alert('Captcha terverifikasi! (Simulasi)');
                popupCaptcha.classList.remove('show');
                // Nanti di sini logika kirim TTD ke server
            });
        }
    }

});