document.addEventListener('DOMContentLoaded', function () {
    // ==========================================
    // 1. CONFIG & VARIABEL UTAMA
    // ==========================================
    const config = window.pdfConfig;
    const container = document.getElementById('pdf-render-container');
    const scrollContainer = document.getElementById('scrollContainer');
    const zoomText = document.getElementById('zoomLevel');
    const currPageElem = document.getElementById('curr_page');
    const totalPagesElem = document.getElementById('total_pages');
    
    // Variabel PDF
    let pdfDoc = null;
    let scale = 1.0;
    let selectedParaf = null;
    const outputScale = window.devicePixelRatio || 1;

    if (!config || !window.pdfjsLib) {
        console.error("PDF Configuration missing!");
        return;
    }
    pdfjsLib.GlobalWorkerOptions.workerSrc = config.workerSrc;

    // ==========================================
    // 2. LOGIKA SIDEBAR (UPLOAD/HAPUS/GANTI)
    // ==========================================
    const parafBox = document.getElementById("parafBox");
    const parafImage = document.getElementById("parafImage");
    const fileInput = document.getElementById("parafImageUpload");
    const gantiBtn = document.getElementById("parafGantiBtn");
    const hapusBtn = document.getElementById("parafHapusBtn");

    if (parafBox && parafImage && fileInput) {
        // Trigger input file
        const triggerUpload = () => fileInput.click();

        // Klik box untuk upload pertama kali
        parafBox.addEventListener("click", () => {
            if (!parafBox.classList.contains("has-image")) triggerUpload();
        });

        // Mengganti gambar paraf
        if (gantiBtn) {
            gantiBtn.addEventListener("click", (e) => {
                e.stopPropagation();
                triggerUpload();
            });
        }

        // Menghapus gambar paraf
        if (hapusBtn) {
            hapusBtn.addEventListener("click", (e) => {
                e.stopPropagation();
                parafImage.src = "";
                parafImage.style.display = "none";
                fileInput.value = "";
                parafBox.classList.remove("has-image");
            });
        }

        // Menampilkan gambar yang dipilih ke dalam preview
        fileInput.addEventListener("change", (e) => {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = (ev) => {
                    parafImage.src = ev.target.result;
                    parafImage.style.display = "block";
                    parafBox.classList.add("has-image");
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        });

        // Mengaktifkan fitur drag untuk gambar paraf asal
        parafImage.addEventListener("dragstart", (e) => {
            if (parafImage.src && parafBox.classList.contains("has-image")) {
                e.dataTransfer.setData("text/plain", "parafImage");
                e.dataTransfer.effectAllowed = "copy";
            } else {
                e.preventDefault();
            }
        });
    }

    // ==========================================
    // 3. LOGIKA RENDER PDF
    // ==========================================
    const pageObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            // Jika halaman terlihat di layar
            if (entry.isIntersecting) {
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

    function renderAllPages() {
        container.innerHTML = ''; 
        
        // Reset Observer setiap kali render ulang (biar gak numpuk)
        pageObserver.disconnect();

        if (!pdfDoc) return;

        for (let num = 1; num <= pdfDoc.numPages; num++) {
            const canvas = document.createElement('canvas');
            canvas.id = 'page-' + num; 
            canvas.className = 'pdf-page-canvas';
            canvas.style.marginBottom = "20px";
            canvas.style.display = "block";

            container.appendChild(canvas);
            renderPage(num, canvas);

            // DAFTARKAN CANVAS KE OBSERVER
            pageObserver.observe(canvas);
        }

        if (zoomText) zoomText.innerText = Math.round(scale * 100) + "%";
    }

    function renderPage(num, canvas) {
        pdfDoc.getPage(num).then(function(page) {
            const ctx = canvas.getContext('2d');
            const viewport = page.getViewport({ scale: scale });

            canvas.width = Math.floor(viewport.width * outputScale);
            canvas.height = Math.floor(viewport.height * outputScale);

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

    // ==========================================
    // 4. LOGIKA DROP ZONE
    // ==========================================
    function setupDropZone(dropZone) {
        
        dropZone.addEventListener("dragover", (e) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = "copy";
            // Efek border saat drag over
            dropZone.style.outline = "2px dashed #1e4ed8"; 
        });

        dropZone.addEventListener("dragleave", () => {
            dropZone.style.outline = "none";
        });

        dropZone.addEventListener("drop", (e) => {
            e.preventDefault();
            dropZone.style.outline = "none";

            const data = e.dataTransfer.getData("text/plain");
            if (data !== "parafImage") return;

            const originalParaf = document.getElementById("parafImage");
            if (!originalParaf || !originalParaf.src) return;

            // Clone & Create Logic
            const newParaf = originalParaf.cloneNode(true);
            newParaf.id = "paraf-dropped-" + Date.now();
            newParaf.classList.add("paraf-dropped");
            newParaf.classList.remove("paraf-image-preview"); 

            // --- PERHITUNGAN POSISI 
            const dropRect = dropZone.getBoundingClientRect();
            const x = e.clientX - dropRect.left;
            const y = e.clientY - dropRect.top;

            // Styling Posisi
            newParaf.style.position = "absolute";
            newParaf.style.left = `${x - 50}px`; // Center cursor (asumsi lebar 100)
            newParaf.style.top = `${y - 25}px`;  // Center cursor (asumsi tinggi 50)
            
            newParaf.style.display = "block";
            newParaf.style.width = "100px"; // Ukuran default paraf di kertas
            newParaf.style.zIndex = "100";
            newParaf.style.cursor = "grab";
            newParaf.style.border = "1px dashed transparent"; // Untuk seleksi

            dropZone.appendChild(newParaf);
            
            // Pasang Logic Move & Select (Persis Kode Lama)
            makeElementMovable(newParaf);
            makeElementSelectable(newParaf);
        });

        // Reset seleksi jika klik area kosong
        dropZone.addEventListener("click", () => {
            if (selectedParaf) {
                selectedParaf.style.border = "1px dashed transparent";
                selectedParaf.classList.remove("selected");
                selectedParaf = null;
            }
        });
    }

    // ==========================================
    // 5. LOGIKA MOVE & SELECT
    // ==========================================
    function makeElementMovable(element) {
        let isDragging = false;
        let startX, startY, startLeft, startTop;

        element.addEventListener("mousedown", (e) => {
            e.preventDefault();
            e.stopPropagation();

            isDragging = true;
            selectElement(element);
            element.style.cursor = "grabbing";

            startX = e.clientX;
            startY = e.clientY;
            startLeft = element.offsetLeft;
            startTop = element.offsetTop;

            function onMouseMove(moveEvent) {
                if (!isDragging) return;

                // Hitung Delta
                let dx = moveEvent.clientX - startX;
                let dy = moveEvent.clientY - startY;

                element.style.left = `${startLeft + dx}px`;
                element.style.top = `${startTop + dy}px`;
            }

            function onMouseUp() {
                isDragging = false;
                element.style.cursor = "grab";
                document.removeEventListener("mousemove", onMouseMove);
                document.removeEventListener("mouseup", onMouseUp);
            }

            document.addEventListener("mousemove", onMouseMove);
            document.addEventListener("mouseup", onMouseUp);
        });
    }

    function makeElementSelectable(element) {
        element.addEventListener("click", (e) => {
            e.stopPropagation();
            selectElement(element);
        });
    }

    function selectElement(element) {
        if (selectedParaf && selectedParaf !== element) {
            selectedParaf.style.border = "1px dashed transparent";
            selectedParaf.classList.remove("selected");
        }
        selectedParaf = element;
        selectedParaf.classList.add("selected");
        selectedParaf.style.border = "2px dashed #007bff"; 
    }

    document.addEventListener("keydown", (e) => {
        if ((e.key === "Delete" || e.key === "Backspace") && selectedParaf) {
            selectedParaf.remove();
            selectedParaf = null;
        }
    });

    // ==========================================
    // 6. LOAD & ZOOM
    // ==========================================
    function autoFit() {
        if(!pdfDoc) return;
        
        // Ambil lebar scrollContainer (dikurangi sedikit padding biar aman)
        const containerWidth = scrollContainer.clientWidth - 40; 

        pdfDoc.getPage(1).then(function(page) {
            const unscaledViewport = page.getViewport({ scale: 1 });
            // Hitung skala agar pas lebar
            let newScale = containerWidth / unscaledViewport.width;

            // Limit minimal dan maksimal zoom agar masuk akal
            if (newScale < 0.5) newScale = 0.5;
            if (newScale > 2.0) newScale = 2.0;
            
            scale = newScale;
            renderAllPages();
        });
    }

    pdfjsLib.getDocument(config.pdfUrl).promise.then(function (pdfDoc_) {
        pdfDoc = pdfDoc_;
        
        // Update Total Halaman di Header
        if (totalPagesElem) {
            totalPagesElem.innerText = pdfDoc.numPages;
        }
        
        autoFit();
    }).catch(function (error) {
        console.error(error);
    });

    // Zoom Buttons
    const btnZoomIn = document.getElementById('zoomInBtn');
    const btnZoomOut = document.getElementById('zoomOutBtn');

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