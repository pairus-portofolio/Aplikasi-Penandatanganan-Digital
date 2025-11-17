// public/js/app.js

(function () {
    const hb = document.getElementById("hb"); // Tombol hamburger
    const html = document.documentElement; // Elemen HTML
    const overlay = document.getElementById("overlay"); // Overlay hitam untuk mobile

    // Fungsi cek apakah tampilan adalah mode mobile
    const isMobile = () => window.matchMedia("(max-width: 992px)").matches;

    // Event: klik tombol hamburger → toggle sidebar
    if (hb) {
        hb.addEventListener("click", () => {
            if (isMobile()) {
                document.body.classList.toggle("show-sb"); // Mode mobile
            } else {
                html.classList.toggle("collapsed"); // Mode desktop
            }
        });
    }

    // Event: klik overlay → tutup sidebar mobile
    if (overlay) {
        overlay.addEventListener("click", () => {
            document.body.classList.remove("show-sb");
        });
    }

    // Event: saat browser di-resize → pastikan sidebar mobile tidak nge-bug
    window.addEventListener("resize", () => {
        if (!isMobile()) {
            document.body.classList.remove("show-sb");
        }
    });

    // === Logika Popup Logout ===
    const logoutBtn = document.getElementById("logoutBtn"); // Tombol untuk membuka popup logout
    const popup = document.getElementById("logoutPopup"); // Elemen popup logout
    const cancelBtn = document.getElementById("cancelLogout"); // Tombol untuk menutup popup

    if (logoutBtn && popup) {
        // Event: buka popup logout
        logoutBtn.addEventListener("click", () => {
            popup.classList.add("show");
        });
    }

    if (cancelBtn && popup) {
        // Event: tutup popup logout
        cancelBtn.addEventListener("click", () => {
            popup.classList.remove("show");
        });
    }
})();
