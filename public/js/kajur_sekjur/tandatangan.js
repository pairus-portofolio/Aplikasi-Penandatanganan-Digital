document.addEventListener('DOMContentLoaded', function () {
    // ==========================================
    // 1. SETUP CONFIG & VARIABEL PDF
    // ==========================================
    const config = window.reviewConfig;
    
    if (!config || !window.pdfjsLib) {
        console.error("PDF Library atau Config tidak ditemukan!");
        return;
    }

    pdfjsLib.GlobalWorkerOptions.workerSrc = config.workerSrc;

    // Elemen DOM
    const container = document.getElementById('pdf-render-container');
    const scrollContainer = document.getElementById('scrollContainer');
    const zoomText = document.getElementById('zoomLevel'); 
    const btnZoomIn = document.getElementById('zoomInBtn');
    const btnZoomOut = document.getElementById('zoomOutBtn');
    
    // Elemen Header Halaman (Untuk update angka halaman)
    const currPageElem = document.getElementById('curr_page');
    const totalPagesElem = document.getElementById('total_pages');

    // State
    let pdfDoc = null;
    let scale = 1.0;
    const outputScale = window.devicePixelRatio || 1;

    // ==========================================
    // 2. LOGIKA RENDER PDF
    // ==========================================

    // Observer untuk mendeteksi halaman saat ini
    const pageObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const pageNum = entry.target.id.split('-')[1];
                if (currPageElem) currPageElem.innerText = pageNum;
            }
        });
    }, {
        root: scrollContainer,
        threshold: 0.1
    });

    function renderAllPages() {
        container.innerHTML = ''; 
        pageObserver.disconnect(); // Reset observer

        if (!pdfDoc) return;

        for (let num = 1; num <= pdfDoc.numPages; num++) {
            const canvas = document.createElement('canvas');
            canvas.id = 'page-' + num;
            canvas.className = 'pdf-page-canvas';
            canvas.style.marginBottom = "20px";
            canvas.style.display = "block";

            container.appendChild(canvas);
            renderPage(num, canvas);
            
            // Daftarkan ke observer
            pageObserver.observe(canvas);
        }

        if (zoomText) zoomText.innerText = Math.round(scale * 100) + "%";
    }

    function renderPage(num, canvas) {
        pdfDoc.getPage(num).then(function (page) {
            const ctx = canvas.getContext('2d');
            const viewport = page.getViewport({ scale: scale });

            canvas.width = Math.floor(viewport.width * outputScale);
            canvas.height = Math.floor(viewport.height * outputScale);
            canvas.style.width = Math.floor(viewport.width) + "px";
            canvas.style.height = Math.floor(viewport.height) + "px";

            const transform = outputScale !== 1 ? [outputScale, 0, 0, outputScale, 0, 0] : null;

            page.render({
                canvasContext: ctx,
                transform: transform,
                viewport: viewport
            });
        });
    }

    function autoFit() {
        if (!pdfDoc) return;
        // Fallback 80px padding
        const containerWidth = scrollContainer ? (scrollContainer.clientWidth - 80) : 800;

        pdfDoc.getPage(1).then(function (page) {
            const unscaledViewport = page.getViewport({ scale: 1 });
            let newScale = containerWidth / unscaledViewport.width;
            if (newScale < 0.5) newScale = 0.5;
            
            scale = newScale;
            renderAllPages();
        });
    }

    // Load Document
    pdfjsLib.getDocument(config.pdfUrl).promise.then(function (pdfDoc_) {
        pdfDoc = pdfDoc_;
        
        if (totalPagesElem) {
            totalPagesElem.innerText = pdfDoc.numPages;
        }
        
        autoFit();
    }).catch(function (error) {
        console.error('Error loading PDF:', error);
    });

    // Event Listeners Zoom
    if (btnZoomIn) {
        btnZoomIn.addEventListener('click', () => {
            scale += 0.1;
            renderAllPages();
        });
    }

    if (btnZoomOut) {
        btnZoomOut.addEventListener('click', () => {
            if (scale > 0.4) {
                scale -= 0.1;
                renderAllPages();
            }
        });
    }

    window.addEventListener('resize', () => {
        clearTimeout(window.resizeTimer);
        window.resizeTimer = setTimeout(autoFit, 200);
    });


    // ==========================================
    // 3. LOGIKA CAPTCHA & POPUP (LOGIKA ASLI)
    // ==========================================
    
    const btnTrigger = document.getElementById("btnTriggerCaptcha");
    const popupCaptcha = document.getElementById("captchaPopup");
    const btnBatal = document.getElementById("batalCaptcha"); 
    const btnProses = document.getElementById("prosesCaptcha");
    const inputCaptcha = document.getElementById("inputCaptchaCode");

    if (btnTrigger && popupCaptcha) {
        btnTrigger.addEventListener("click", function () {
            popupCaptcha.classList.add("show");
        });

        // Menutup popup dengan klik area luar
        popupCaptcha.addEventListener("click", function (e) {
            if (e.target === popupCaptcha) {
                popupCaptcha.classList.remove("show");
            }
        });

        // Proses captcha
        if (btnProses) {
            btnProses.addEventListener("click", function () {
                // Di sini nanti kamu tambahkan logika validasi captcha ke server
                const val = inputCaptcha ? inputCaptcha.value : '';
                if(val.trim() === "") {
                    alert("Masukkan Captcha terlebih dahulu!");
                    return;
                }
                
                // Contoh sederhana
                alert("Memproses Tanda Tangan Digital...");
                popupCaptcha.classList.remove("show");
                // Lanjut ke form submit via AJAX atau Form
            });
        }
    }
});