// Menjalankan script setelah halaman sepenuhnya dimuat
document.addEventListener("DOMContentLoaded", function () {
    // Mengambil elemen utama untuk sidebar dan kondisi mobile
    const hb = document.getElementById("hb");
    const html = document.documentElement;
    const overlay = document.getElementById("overlay");
    const isMobile = () => window.matchMedia("(max-width: 992px)").matches;

    // Mengatur toggle sidebar untuk desktop dan mobile
    if (hb) {
        hb.addEventListener("click", () => {
            if (isMobile()) document.body.classList.toggle("show-sb");
            else html.classList.toggle("collapsed");
        });
    }

    // Menutup sidebar mobile ketika overlay diklik
    if (overlay) {
        overlay.addEventListener("click", () =>
            document.body.classList.remove("show-sb")
        );
    }

    // Menutup sidebar mobile otomatis ketika layar diperbesar
    window.addEventListener("resize", () => {
        if (!isMobile()) document.body.classList.remove("show-sb");
    });

    // Membuat sistem popup generik untuk membuka & menutup modal
    function setupPopup(triggerId, popupId, closeBtnIds = []) {
        const trigger = document.getElementById(triggerId);
        const popup = document.getElementById(popupId);

        if (trigger && popup) {
            trigger.addEventListener("click", () =>
                popup.classList.add("show")
            );

            closeBtnIds.forEach((btnId) => {
                const btn = document.getElementById(btnId);
                if (btn) {
                    btn.addEventListener("click", () =>
                        popup.classList.remove("show")
                    );
                }
            });

            popup.addEventListener("click", (e) => {
                if (e.target === popup) popup.classList.remove("show");
            });
        }
    }

    // Mengaktifkan semua popup yang digunakan di sistem
    setupPopup("logoutBtn", "logoutPopup", ["cancelLogout"]);
    setupPopup("mintaRevisiBtn", "revisiPopup", ["batalBp", "kirimBp"]);
    setupPopup("kirimNotifikasiBtn", "parafNotifPopup", [
        "batalKirim",
        "konfirmasiKirim",
    ]);
    setupPopup("btnSelesaiTtd", "ttdNotifPopup", ["batalTtd", "kirimTtd"]);

    // Mengambil elemen kontrol zoom dokumen
    const page = document.getElementById("previewPage");
    const zoomInBtn = document.getElementById("zoomInBtn");
    const zoomOutBtn = document.getElementById("zoomOutBtn");
    let currentScale = 1;

    // Mengatur fungsi zoom in & zoom out pada halaman preview
    if (page && zoomInBtn && zoomOutBtn) {
        const applyZoom = () => {
            page.style.transform = `scale(${currentScale})`;
        };

        zoomInBtn.addEventListener("click", () => {
            currentScale = Math.min(currentScale + 0.1, 2);
            applyZoom();
        });

        zoomOutBtn.addEventListener("click", () => {
            currentScale = Math.max(currentScale - 0.1, 0.5);
            applyZoom();
        });
    }
});
