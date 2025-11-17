// public/js/app.js
(function () {

    /* ===============================
       SIDEBAR LOGIC (MOBILE & DESKTOP)
       =============================== */
    const hb = document.getElementById("hb");
    const html = document.documentElement;
    const overlay = document.getElementById("overlay");

    const isMobile = () =>globalThis.matchMedia("(max-width: 992px)").matches;

    const toggleSidebar = () => {
        if (isMobile()) document.body.classList.toggle("show-sb");
        else html.classList.toggle("collapsed");
    };

    const closeSidebar = () => document.body.classList.remove("show-sb");

    if (hb) hb.addEventListener("click", toggleSidebar);
    if (overlay) overlay.addEventListener("click", closeSidebar);

    window.addEventListener("resize", () => {
        if (!isMobile()) closeSidebar();
    });


    /* ===============================
       POPUP LOGOUT
       =============================== */
    const logoutBtn = document.getElementById("logoutBtn");
    const logoutPopup = document.getElementById("logoutPopup");
    const cancelLogout = document.getElementById("cancelLogout");

    if (logoutBtn && logoutPopup) {
        logoutBtn.addEventListener("click", () => logoutPopup.classList.add("show"));
    }
    if (cancelLogout && logoutPopup) {
        cancelLogout.addEventListener("click", () => logoutPopup.classList.remove("show"));
    }


    /* ===============================
       POPUP NOTIFIKASI
       =============================== */
    const kirimNotifikasiBtn = document.getElementById("kirimNotifikasiBtn");
    const popupNotifikasi = document.getElementById("parafNotifPopup");
    const cancelNotifikasi = document.getElementById("batalKirim");

    if (kirimNotifikasiBtn && popupNotifikasi) {
        kirimNotifikasiBtn.addEventListener("click", () => popupNotifikasi.classList.add("show"));
    }
    if (cancelNotifikasi && popupNotifikasi) {
        cancelNotifikasi.addEventListener("click", () => popupNotifikasi.classList.remove("show"));
    }


    /* ===============================
       POPUP MINTA REVISI
       =============================== */
    const mintaRevisiBtn = document.getElementById("mintaRevisiBtn");
    const revisiPopup = document.getElementById("revisiPopup");
    const batalBp = document.getElementById("batalBp");

    if (mintaRevisiBtn && revisiPopup) {
        mintaRevisiBtn.addEventListener("click", () => revisiPopup.classList.add("show"));
    }
    if (batalBp && revisiPopup) {
        batalBp.addEventListener("click", () => revisiPopup.classList.remove("show"));
    }


    /* ===============================
       ZOOM PREVIEW
       =============================== */
    let zoomLevel = 1;
    const preview = document.getElementById("previewPage");

    const zoomIn = () => {
        zoomLevel += 0.1;
        preview.style.transform = `scale(${zoomLevel})`;
    };

    const zoomOut = () => {
        if (zoomLevel <= 0.3) return;
        zoomLevel -= 0.1;
        preview.style.transform = `scale(${zoomLevel})`;
    };

    const zoomInBtn = document.getElementById("zoomInBtn");
    const zoomOutBtn = document.getElementById("zoomOutBtn");

    if (zoomInBtn && zoomOutBtn && preview) {
        zoomInBtn.addEventListener("click", zoomIn);
        zoomOutBtn.addEventListener("click", zoomOut);
    }


    /* ===============================
       PARAF UPLOAD
       =============================== */
    document.addEventListener('DOMContentLoaded', () => {
        const parafBox = document.getElementById("parafBox");
        const parafImage = document.getElementById("parafImage");
        const fileInput = document.getElementById("parafImageUpload");
        const gantiBtn = document.getElementById("parafGantiBtn");
        const hapusBtn = document.getElementById("parafHapusBtn");

        if (!parafBox || !parafImage || !fileInput || !gantiBtn || !hapusBtn) return;

        const triggerUpload = () => fileInput.click();

        parafBox.addEventListener('click', triggerUpload);

        gantiBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            triggerUpload();
        });

        hapusBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            parafImage.src = '';
            parafBox.classList.remove('has-image');
            parafBox.querySelector(".paraf-text").style.display = "block";
        });

        fileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function(ev) {
                parafImage.src = ev.target.result;
                parafImage.style.display = "block";
                parafBox.querySelector(".paraf-text").style.display = "none";
                parafBox.classList.add('has-image');
            };
            reader.readAsDataURL(file);
        });
    });

})();