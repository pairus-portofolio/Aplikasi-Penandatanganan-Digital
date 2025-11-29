/* ===============================
   FINALISASI POPUP HANDLER
================================= */

// Buka popup berdasarkan ID
function openPopup(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.style.display = "flex";
}

// Tutup popup berdasarkan ID
function closePopup(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.style.display = "none";
}

/* 
 * Tombol close / cancel pada popup
 * Elemen harus memiliki attribute data-popup-close="#idPopup"
 */
document.addEventListener("click", e => {
    const btn = e.target.closest("[data-popup-close]");
    if (!btn) return;

    const popupId = btn.dataset.popupClose;
    closePopup(popupId);
});

/* 
 * Tombol download file
 * Elemen harus memiliki attribute data-download-url="..."
 */
document.addEventListener("click", e => {
    const btn = e.target.closest("[data-download-url]");
    if (!btn) return;

    const url = btn.dataset.downloadUrl;
    if (url) window.location.href = url;
});
