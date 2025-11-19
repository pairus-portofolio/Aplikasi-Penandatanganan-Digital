/**
 * File: public/js/kaprodi/review-surat.js
 */

document.addEventListener('DOMContentLoaded', function () {
    // --- 1. SETUP CONFIG & VARIABEL ---
    const config = window.reviewConfig; // Mengambil data dari Blade
    
    // Validasi Library & Config
    if (!config || !window.pdfjsLib) {
        console.error("PDF Library atau Konfigurasi tidak ditemukan!");
        return;
    }

    // Set Worker PDF.js
    pdfjsLib.GlobalWorkerOptions.workerSrc = config.workerSrc;

    // Ambil Elemen DOM
    const container = document.getElementById('pdf-render-container');
    const scrollContainer = document.getElementById('scrollContainer');
    const zoomText = document.getElementById('zoomLevel');
    const btnZoomIn = document.getElementById('zoomInBtn');
    const btnZoomOut = document.getElementById('zoomOutBtn');
    const totalPagesElem = document.getElementById('total_pages');
    const currPageElem = document.getElementById('curr_page');

    // Variabel State
    let pdfDoc = null;
    let scale = 1.0;
    const outputScale = window.devicePixelRatio || 1; // HiDPI Support

    // Fungsi ini mendeteksi halaman mana yang sedang terlihat di layar
    const pageObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            // Jika halaman terlihat di layar (walaupun cuma 10%)
            if (entry.isIntersecting) {
                // Ambil ID (contoh: "page-5" -> "5")
                const pageNum = entry.target.id.split('-')[1];
                
                // Update teks di header
                if (currPageElem) {
                    currPageElem.innerText = pageNum;
                }
            }
        });
    }, {
        root: scrollContainer, 
        rootMargin: '0px',
        threshold: 0.1 
    });

    // --- 2. FUNGSI RENDER SEMUA HALAMAN ---
    function renderAllPages() {
        container.innerHTML = ''; 

        if (!pdfDoc) return;

        for (let num = 1; num <= pdfDoc.numPages; num++) {
            // ... (Kode pembuatan canvas sama seperti sebelumnya) ...
            const canvas = document.createElement('canvas');
            canvas.id = 'page-' + num; // ID ini penting untuk Observer
            canvas.className = 'pdf-page-canvas';
            canvas.style.marginBottom = "20px";
            canvas.style.display = "block";

            container.appendChild(canvas);
            renderPage(num, canvas);

            // PENTING: Suruh Observer memantau canvas ini
            pageObserver.observe(canvas);
        }

        if (zoomText) zoomText.innerText = Math.round(scale * 100) + "%";
    }

    // --- 3. FUNGSI RENDER SATU HALAMAN ---
    function renderPage(num, canvas) {
        pdfDoc.getPage(num).then(function (page) {
            const ctx = canvas.getContext('2d');
            const viewport = page.getViewport({ scale: scale });

            // Set resolusi canvas (agar tajam di layar retina)
            canvas.width = Math.floor(viewport.width * outputScale);
            canvas.height = Math.floor(viewport.height * outputScale);

            // Set ukuran tampilan CSS
            canvas.style.width = Math.floor(viewport.width) + "px";
            canvas.style.height = Math.floor(viewport.height) + "px";

            const transform = outputScale !== 1
                ? [outputScale, 0, 0, outputScale, 0, 0]
                : null;

            const renderContext = {
                canvasContext: ctx,
                transform: transform,
                viewport: viewport
            };
            page.render(renderContext);
        });
    }

    // --- 4. AUTO FIT (RESPONSIVE) ---
    function autoFit() {
        if (!pdfDoc) return;

        // Ambil lebar container dikurangi padding (fallback 80px)
        const containerWidth = scrollContainer ? (scrollContainer.clientWidth - 80) : 800;

        pdfDoc.getPage(1).then(function (page) {
            const unscaledViewport = page.getViewport({ scale: 1 });
            let newScale = containerWidth / unscaledViewport.width;

            // Batas minimum zoom agar tidak terlalu kecil
            if (newScale < 0.5) newScale = 0.5;
            
            scale = newScale;
            renderAllPages();
        });
    }

    // --- 5. EKSEKUSI LOAD PDF ---
    pdfjsLib.getDocument(config.pdfUrl).promise.then(function (pdfDoc_) {
        pdfDoc = pdfDoc_;
        
        // Update Teks Total Halaman di Header
        if (totalPagesElem) {
            totalPagesElem.innerText = pdfDoc.numPages;
        }
        
        autoFit(); // Render halaman
    }).catch(function (error) {
        console.error('Error:', error);
    });

    // --- 6. EVENT LISTENERS (ZOOM & RESIZE) ---
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
});