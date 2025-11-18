// Menunggu seluruh elemen DOM siap
document.addEventListener("DOMContentLoaded", function () {
    // Elemen zoom dan nilai awal skala
    const page = document.getElementById("previewPage");
    const zoomIn = document.getElementById("zoomInBtn");
    const zoomOut = document.getElementById("zoomOutBtn");
    let currentScale = 1;

    // Logika zoom dokumen
    if (page && zoomIn && zoomOut) {
        // Menerapkan nilai zoom ke elemen dokumen
        function applyZoom() {
            page.style.transform = "scale(" + currentScale + ")";
        }

        // Zoom in
        zoomIn.addEventListener("click", () => {
            currentScale = Math.min(currentScale + 0.1, 2);
            applyZoom();
        });

        // Zoom out
        zoomOut.addEventListener("click", () => {
            currentScale = Math.max(currentScale - 0.1, 0.5);
            applyZoom();
        });
    }

    // Elemen popup captcha
    const btnTrigger = document.getElementById("btnTriggerCaptcha");
    const popupCaptcha = document.getElementById("captchaPopup");
    const btnBatal = document.getElementById("batalCaptcha");
    const btnProses = document.getElementById("prosesTtd");

    // Logika membuka popup captcha
    if (btnTrigger && popupCaptcha) {
        btnTrigger.addEventListener("click", function () {
            popupCaptcha.classList.add("show");
        });

        // Menutup popup dengan tombol batal
        if (btnBatal) {
            btnBatal.addEventListener("click", function () {
                popupCaptcha.classList.remove("show");
            });
        }

        // Menutup popup dengan klik area luar
        popupCaptcha.addEventListener("click", function (e) {
            if (e.target === popupCaptcha)
                popupCaptcha.classList.remove("show");
        });

        // Proses captcha (simulasi)
        if (btnProses) {
            btnProses.addEventListener("click", function () {
                alert("Captcha terverifikasi! (Simulasi)");
                popupCaptcha.classList.remove("show");
            });
        }
    }
});
