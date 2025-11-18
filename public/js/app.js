// public/js/app.js

document.addEventListener("DOMContentLoaded", function () {
    
    /* ==========================================
       1. SIDEBAR LOGIC
       ========================================== */
    const hb = document.getElementById("hb");
    const html = document.documentElement;
    const overlay = document.getElementById("overlay");
    const isMobile = () => window.matchMedia("(max-width: 992px)").matches;

    if (hb) {
        hb.addEventListener("click", () => {
            if (isMobile()) document.body.classList.toggle("show-sb");
            else html.classList.toggle("collapsed");
        });
    }
    
    if (overlay) {
        overlay.addEventListener("click", () => document.body.classList.remove("show-sb"));
    }
    
    window.addEventListener("resize", () => {
        if (!isMobile()) document.body.classList.remove("show-sb");
    });

    /* ==========================================
       2. POPUP SYSTEM (Generic Handler)
       ========================================== */
    function setupPopup(triggerId, popupId, closeBtnIds = []) {
        const trigger = document.getElementById(triggerId);
        const popup = document.getElementById(popupId);

        if (trigger && popup) {
            // Buka Popup
            trigger.addEventListener("click", () => popup.classList.add("show"));

            // Tutup Popup via Tombol (Batal/Kirim)
            closeBtnIds.forEach(btnId => {
                const btn = document.getElementById(btnId);
                if (btn) {
                    btn.addEventListener("click", () => popup.classList.remove("show"));
                }
            });

            // Tutup Popup via Klik Background (Overlay)
            popup.addEventListener("click", (e) => {
                if (e.target === popup) popup.classList.remove("show");
            });
        }
    }

    // Inisialisasi Popup Logout
    setupPopup("logoutBtn", "logoutPopup", ["cancelLogout"]);

    // Inisialisasi Popup Revisi (Review Surat)
    setupPopup("mintaRevisiBtn", "revisiPopup", ["batalBp", "kirimBp"]);

    // Inisialisasi Popup Notifikasi (Paraf Surat)
    setupPopup("kirimNotifikasiBtn", "parafNotifPopup", ["batalKirim", "konfirmasiKirim"]);

    // Inisialisasi Popup Tanda Tangan (Kajur/Sekjur)
    setupPopup("btnSelesaiTtd", "ttdNotifPopup", ["batalTtd", "kirimTtd"]);


    /* ==========================================
       3. ZOOM CONTROLS
       ========================================== */
    const page = document.getElementById("previewPage");
    const zoomInBtn = document.getElementById("zoomInBtn");
    const zoomOutBtn = document.getElementById("zoomOutBtn");
    let currentScale = 1;

    if (page && zoomInBtn && zoomOutBtn) {
        const applyZoom = () => {
            page.style.transform = `scale(${currentScale})`;
        };

        zoomInBtn.addEventListener("click", () => {
            currentScale = Math.min(currentScale + 0.1, 2); // Max zoom 2x
            applyZoom();
        });

        zoomOutBtn.addEventListener("click", () => {
            currentScale = Math.max(currentScale - 0.1, 0.5); // Min zoom 0.5x
            applyZoom();
        });
    }
});