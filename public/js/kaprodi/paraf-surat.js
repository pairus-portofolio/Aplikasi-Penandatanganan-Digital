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
    // 2. LOGIKA SIDEBAR (UPLOAD / HAPUS / GANTI)
    // ==========================================
    const parafBox = document.getElementById("parafBox");
    const parafImage = document.getElementById("parafImage");
    const fileInput = document.getElementById("parafImageUpload");
    const gantiBtn = document.getElementById("parafGantiBtn");
    const hapusBtn = document.getElementById("parafHapusBtn");

    if (parafBox && parafImage && fileInput) {
        const triggerUpload = () => fileInput.click();

        parafBox.addEventListener("click", () => {
            if (!parafBox.classList.contains("has-image")) triggerUpload();
        });

        if (gantiBtn) {
            gantiBtn.addEventListener("click", (e) => {
                e.stopPropagation();
                triggerUpload();
            });
        }

        // HAPUS PARAF PERMANEN
        if (hapusBtn) {
            hapusBtn.addEventListener("click", (e) => {
                e.stopPropagation();
                if (!confirm("Yakin ingin menghapus paraf ini secara permanen?")) return;

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                
                fetch('/kaprodi/paraf/delete', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        parafImage.src = "";
                        parafImage.style.display = "none";
                        fileInput.value = "";
                        parafBox.classList.remove("has-image");
                        const t = parafBox.querySelector('.paraf-text');
                        if (t) t.style.display = "block";
                    } else {
                        alert("Gagal menghapus: " + data.message);
                    }
                })
                .catch(err => alert("Terjadi kesalahan saat hapus data."));
            });
        }

        // UPLOAD PARAF
        fileInput.addEventListener("change", (e) => {
            if (e.target.files && e.target.files[0]) {
                const file = e.target.files[0];
                const reader = new FileReader();
                
                reader.onload = (ev) => {
                    parafImage.src = ev.target.result;
                    parafImage.style.display = "block";
                    parafBox.classList.add("has-image");
                    const t = parafBox.querySelector('.paraf-text');
                    if (t) t.style.display = 'none';
                };
                reader.readAsDataURL(file);

                const formData = new FormData();
                formData.append("image", file);
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

                fetch('/kaprodi/paraf/upload', {
                    method: "POST",
                    headers: { "X-CSRF-TOKEN": csrfToken, "Accept": "application/json" },
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status !== "success") alert("Gagal upload: " + data.message);
                })
                .catch(err => console.error("UPLOAD ERROR", err));
            }
        });

        // DRAG START DARI SIDEBAR
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
    // 3. HELPER: FUNGSI SIMPAN KE DB (PENTING!)
    // ==========================================
    function saveParafPosition(element, pageNumber) {
        try {
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            
            // Ambil posisi CSS saat ini (dalam pixel Zoomed)
            const rawLeft = parseFloat(element.style.left);
            const rawTop = parseFloat(element.style.top);

            // NORMALISASI: Kembalikan ke skala 1.0
            // Ini KUNCI agar posisi tidak melenceng saat zoom
            const normalizedX = Math.round(rawLeft / scale);
            const normalizedY = Math.round(rawTop / scale);

            console.log(`Saving Paraf: Page ${pageNumber}, X=${normalizedX}, Y=${normalizedY} (Scale: ${scale})`);

            fetch(`/paraf-surat/${window.suratId}/save-paraf`, {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": csrf,
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                },
                body: JSON.stringify({
                    posisi_x: normalizedX,
                    posisi_y: normalizedY,
                    halaman: pageNumber
                })
            })
            .then(res => res.json())
            .then(data => console.log("DB Updated:", data))
            .catch(err => console.error("Save Failed:", err));

        } catch (error) {
            console.error("Auto save error:", error);
        }
    }

    // ==========================================
    // 4. LOGIKA RENDER PDF
    // ==========================================
    const pageObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const pageNum = entry.target.id.split('-')[1];
                if (currPageElem) currPageElem.innerText = pageNum;
            }
        });
    }, { root: scrollContainer, threshold: 0.1 });

    function renderAllPages() {
        container.innerHTML = '';
        pageObserver.disconnect();

        for (let num = 1; num <= pdfDoc.numPages; num++) {
            // Wrapper
            const wrap = document.createElement("div");
            wrap.className = "pdf-page-wrapper";
            wrap.dataset.pageNumber = num;
            wrap.style.position = "relative";
            wrap.style.marginBottom = "20px";
            wrap.style.display = "inline-block";

            // Canvas
            const canvas = document.createElement("canvas");
            canvas.id = "page-" + num;
            canvas.className = "pdf-page-canvas";
            canvas.style.display = "block";

            wrap.appendChild(canvas);
            container.appendChild(wrap);

            renderPage(num, canvas);
            setupDropZone(wrap);
            pageObserver.observe(canvas);
        }

        if (zoomText) zoomText.innerText = Math.round(scale * 100) + "%";
    }

    function renderPage(num, canvas) {
        pdfDoc.getPage(num).then(page => {
            const ctx = canvas.getContext("2d");
            const viewport = page.getViewport({ scale });

            canvas.width = viewport.width * outputScale;
            canvas.height = viewport.height * outputScale;
            canvas.style.width = viewport.width + "px";
            canvas.style.height = viewport.height + "px";

            page.render({
                canvasContext: ctx,
                viewport,
                transform: outputScale !== 1 ? [outputScale, 0, 0, outputScale, 0, 0] : null
            });
        });
    }

    // ==========================================
    // 5. LOGIKA DROP ZONE
    // ==========================================
    function setupDropZone(dropZone) {
        dropZone.addEventListener("dragover", (e) => {
            e.preventDefault();
            dropZone.style.outline = "2px dashed #1e4ed8";
        });

        dropZone.addEventListener("dragleave", () => {
            dropZone.style.outline = "none";
        });

        dropZone.addEventListener("drop", (e) => {
            e.preventDefault();
            dropZone.style.outline = "none";

            if (e.dataTransfer.getData("text/plain") !== "parafImage") return;

            // Hapus paraf lama jika ada (agar cuma 1 paraf per dokumen/user)
            const existing = document.querySelector('.paraf-dropped');
            if (existing) existing.remove();

            const original = document.getElementById("parafImage");
            if (!original || !original.src) return;

            const newParaf = original.cloneNode(true);
            newParaf.id = "paraf-dropped-" + Date.now();
            newParaf.classList.remove("paraf-image-preview");
            newParaf.classList.add("paraf-dropped");

            const page = parseInt(dropZone.dataset.pageNumber, 10);
            const rect = dropZone.getBoundingClientRect();
            
            // Hitung posisi relatif mouse terhadap canvas
            const x = e.clientX - rect.left - 50; 
            const y = e.clientY - rect.top - 25;

            newParaf.style.position = "absolute";
            newParaf.style.left = x + "px";
            newParaf.style.top = y + "px";
            newParaf.style.width = "100px";
            newParaf.style.cursor = "grab";
            newParaf.style.zIndex = "100";

            dropZone.appendChild(newParaf);

            makeElementMovable(newParaf);
            makeElementSelectable(newParaf);

            // SIMPAN DATA PERTAMA KALI
            saveParafPosition(newParaf, page);
        });

        dropZone.addEventListener("click", () => {
            if (selectedParaf) {
                selectedParaf.style.border = "1px dashed transparent";
                selectedParaf = null;
            }
        });
    }

    // ==========================================
    // 6. LOGIKA MOVE & SELECT (UPDATE SAAT DILEPAS)
    // ==========================================
    function makeElementMovable(el) {
        let isDragging = false;
        let startX, startY, startLeft, startTop;

        el.addEventListener("mousedown", (e) => {
            e.stopPropagation();
            isDragging = true;
            selectElement(el);
            el.style.cursor = "grabbing";

            startX = e.clientX;
            startY = e.clientY;
            startLeft = el.offsetLeft;
            startTop = el.offsetTop;

            const move = (ev) => {
                if (!isDragging) return;
                const dx = ev.clientX - startX;
                const dy = ev.clientY - startY;
                el.style.left = (startLeft + dx) + "px";
                el.style.top = (startTop + dy) + "px";
            };

            const up = () => {
                if (isDragging) {
                    isDragging = false;
                    el.style.cursor = "grab";
                    document.removeEventListener("mousemove", move);
                    document.removeEventListener("mouseup", up);

                    // Panggil fungsi simpan saat mouse dilepas (selesai geser)
                    const wrapper = el.closest('.pdf-page-wrapper');
                    const page = wrapper ? parseInt(wrapper.dataset.pageNumber, 10) : 1;
                    saveParafPosition(el, page);
                }
            };

            document.addEventListener("mousemove", move);
            document.addEventListener("mouseup", up);
        });
    }

    function makeElementSelectable(el) {
        el.addEventListener("click", (e) => {
            e.stopPropagation();
            selectElement(el);
        });
    }

    function selectElement(el) {
        if (selectedParaf && selectedParaf !== el) {
            selectedParaf.style.border = "1px dashed transparent";
        }
        selectedParaf = el;
        el.style.border = "2px dashed #007bff";
    }

    document.addEventListener("keydown", (e) => {
        if ((e.key === "Delete" || e.key === "Backspace") && selectedParaf) {
            selectedParaf.remove();
            selectedParaf = null;
        }
    });

    // ==========================================
    // 7. LOAD & ZOOM
    // ==========================================
    function autoFit() {
        if (!pdfDoc) return;
        const w = scrollContainer.clientWidth - 40;
        pdfDoc.getPage(1).then(page => {
            const v = page.getViewport({ scale: 1 });
            let newScale = w / v.width;
            if (newScale < 0.5) newScale = 0.5;
            if (newScale > 2) newScale = 2;
            scale = newScale;
            renderAllPages();
        });
    }

    pdfjsLib.getDocument(config.pdfUrl).promise.then((doc) => {
        pdfDoc = doc;
        if (totalPagesElem) totalPagesElem.innerText = doc.numPages;
        autoFit();
    });

    const zoomIn = document.getElementById("zoomInBtn");
    const zoomOut = document.getElementById("zoomOutBtn");

    if (zoomIn) zoomIn.addEventListener("click", () => { scale += 0.1; renderAllPages(); });
    if (zoomOut) zoomOut.addEventListener("click", () => { if (scale > 0.4) scale -= 0.1; renderAllPages(); });

    window.addEventListener("resize", () => {
        clearTimeout(window.resizeTimer);
        window.resizeTimer = setTimeout(autoFit, 200);
    });
});