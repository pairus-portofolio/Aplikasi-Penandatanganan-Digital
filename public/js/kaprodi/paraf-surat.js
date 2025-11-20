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

        // ---- TRIGGER UPLOAD ----
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

        // ==============================================
        // HAPUS PARAF (UPDATE + HAPUS PERMANEN SERVER)
        // ==============================================
        if (hapusBtn) {
            hapusBtn.addEventListener("click", (e) => {
                e.stopPropagation();

                if (!confirm("Yakin ingin menghapus paraf ini secara permanen?")) return;

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                if (!csrfToken) {
                    alert("CSRF Token tidak ditemukan.");
                    return;
                }

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
                .catch(err => {
                    console.error("DELETE ERROR", err);
                    alert("Terjadi kesalahan saat hapus data.");
                });
            });
        }

        // ==========================================================
        // UPLOAD PARAF → PREVIEW → KIRIM KE SERVER
        // ==========================================================
        fileInput.addEventListener("change", (e) => {
            if (e.target.files && e.target.files[0]) {
                const file = e.target.files[0];

                // -- PREVIEW --
                const reader = new FileReader();
                reader.onload = (ev) => {
                    parafImage.src = ev.target.result;
                    parafImage.style.display = "block";
                    parafBox.classList.add("has-image");
                    const t = parafBox.querySelector('.paraf-text');
                    if (t) t.style.display = 'none';
                };
                reader.readAsDataURL(file);

                // -- UPLOAD SERVER --
                const formData = new FormData();
                formData.append("image", file);

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                if (!csrfToken) {
                    console.error("CSRF Token tidak ditemukan!");
                    return;
                }

                fetch('/kaprodi/paraf/upload', {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": csrfToken,
                        "Accept": "application/json"
                    },
                    body: formData
                })
                .then(async (response) => {
                    if (!response.ok) {
                        const err = await response.json();
                        throw err;
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.status === "success") {
                        console.log("Paraf berhasil disimpan.");
                    } else {
                        alert("Gagal upload: " + data.message);
                    }
                })
                .catch(err => {
                    console.error("UPLOAD ERROR", err);
                    alert("Gagal Upload:\n" + (err.message || "Terjadi kesalahan."));
                });
            }
        });

        // DRAG PARAF ASAL
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
        pageObserver.disconnect();

        for (let num = 1; num <= pdfDoc.numPages; num++) {
            const canvas = document.createElement("canvas");
            canvas.id = "page-" + num;
            canvas.className = "pdf-page-canvas";
            canvas.style.marginBottom = "20px";

            container.appendChild(canvas);
            renderPage(num, canvas);

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
    // 4. LOGIKA DROP ZONE
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

            const original = document.getElementById("parafImage");
            if (!original || !original.src) return;

            const newParaf = original.cloneNode(true);
            newParaf.id = "paraf-dropped-" + Date.now();
            newParaf.classList.remove("paraf-image-preview");
            newParaf.classList.add("paraf-dropped");

            const rect = dropZone.getBoundingClientRect();
            newParaf.style.position = "absolute";
            newParaf.style.left = (e.clientX - rect.left - 50) + "px";
            newParaf.style.top = (e.clientY - rect.top - 25) + "px";
            newParaf.style.width = "100px";
            newParaf.style.cursor = "grab";
            newParaf.style.zIndex = "100";

            dropZone.appendChild(newParaf);

            makeElementMovable(newParaf);
            makeElementSelectable(newParaf);
        });

        dropZone.addEventListener("click", () => {
            if (selectedParaf) {
                selectedParaf.style.border = "1px dashed transparent";
                selectedParaf = null;
            }
        });
    }

    // ==========================================
    // 5. MOVE & SELECT LOGIC
    // ==========================================
    function makeElementMovable(el) {
        let isDragging = false;
        let startX, startY, startLeft, startTop;

        el.addEventListener("mousedown", (e) => {
            e.stopPropagation();
            isDragging = true;
            selectElement(el);

            startX = e.clientX;
            startY = e.clientY;
            startLeft = el.offsetLeft;
            startTop = el.offsetTop;

            const move = (ev) => {
                if (!isDragging) return;
                let dx = ev.clientX - startX;
                let dy = ev.clientY - startY;
                el.style.left = (startLeft + dx) + "px";
                el.style.top = (startTop + dy) + "px";
            };

            const up = () => {
                isDragging = false;
                document.removeEventListener("mousemove", move);
                document.removeEventListener("mouseup", up);
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
    // 6. LOAD & ZOOM
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
