document.addEventListener('DOMContentLoaded', function () {
    // ==========================================
    // 1. CONFIG & VARIABEL UTAMA
    // ==========================================
    const config = window.reviewConfig;
    const container = document.getElementById('pdf-render-container');
    const scrollContainer = document.getElementById('scrollContainer');
    const zoomText = document.getElementById('zoomLevel');
    const currPageElem = document.getElementById('curr_page');
    const totalPagesElem = document.getElementById('total_pages');

    let pdfDoc = null;
    let scale = 1.0; 
    let selectedParaf = null;
    const outputScale = window.devicePixelRatio || 1;

    // DATA TANDA TANGAN (In-Memory State)
    // Format: { page: 1, x: 100, y: 200 } (Normalized Coordinates)
    let signatureData = config.savedSignature || null;

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

    // KLIK PARAFBOX → tampilkan captcha, TIDAK langsung upload
    parafBox.addEventListener("click", () => {
        if (!parafBox.classList.contains("has-image")) {
            document.getElementById("captchaBox").style.display = "block";
        }
    });

    // Tombol ganti tetap sama, tapi diarahkan ke captcha
    if (gantiBtn) {
        gantiBtn.addEventListener("click", (e) => {
            e.stopPropagation();
            document.getElementById("captchaBox").style.display = "block";
        });
    }

        // HAPUS TTD PERMANEN
        if (hapusBtn) {
            hapusBtn.addEventListener("click", (e) => {
                e.stopPropagation();
                
                Swal.fire({
                    title: 'Hapus Tanda Tangan?',
                    text: 'Tanda tangan akan dihapus secara permanen dari sistem.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Hapus',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (!result.isConfirmed) return;
                    
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                    
                    fetch('/kajur/tandatangan/delete', {
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
                            // Reset UI Sidebar
                            parafImage.src = "";
                            parafImage.style.display = "none";
                            fileInput.value = "";
                            parafBox.classList.remove("has-image");
                            const t = parafBox.querySelector('.paraf-text');
                            if (t) t.style.display = "block";
                            
                            // Hapus dari Canvas & State
                            removeSignatureFromCanvas();
                            signatureData = null; 

                            // Success notification
                            Swal.fire({
                                toast: true,
                                icon: 'success',
                                title: 'Tanda tangan berhasil dihapus',
                                position: 'top-end',
                                timer: 2500,
                                timerProgressBar: true,
                                showConfirmButton: false,
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal Menghapus',
                                text: data.message || 'Terjadi kesalahan saat menghapus tanda tangan'
                            });
                        }
                    })
                    .catch(err => {
                        console.error('Delete error:', err);
                        Swal.fire({
                            icon: 'error',
                            title: 'Kesalahan Jaringan',
                            text: 'Tidak dapat terhubung ke server. Periksa koneksi internet Anda.'
                        });
                    });
                });
            });
        }

        // UPLOAD TTD
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
                
                // Tambahkan Token Captcha
                if (window.lastCaptchaToken) {
                    formData.append("g-recaptcha-response", window.lastCaptchaToken);
                }
                
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

                fetch('/kajur/tandatangan/upload', {
                    method: "POST",
                    headers: { "X-CSRF-TOKEN": csrfToken, "Accept": "application/json" },
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status !== "success") {
                        Swal.fire({
                            icon: 'error',
                            title: 'Upload Gagal',
                            text: data.message || 'Terjadi kesalahan saat mengupload tanda tangan'
                        });
                    }
                })
                .catch(err => {
                    console.error("UPLOAD ERROR", err);
                    Swal.fire({
                        icon: 'error',
                        title: 'Kesalahan Jaringan',
                        text: 'Tidak dapat mengupload file. Periksa koneksi internet Anda.'
                    });
                })
                .finally(() => {
                    if (window.grecaptcha) {
                        grecaptcha.reset();
                        window.lastCaptchaToken = null;
                    }
                });
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
    // 3. HELPER: STATE MANAGEMENT & DB SAVE
    // ==========================================
    
    function removeSignatureFromCanvas() {
        const existing = document.querySelector('.paraf-dropped');
        if (existing) existing.remove();
    }

    function saveParafPosition(element, pageNumber) {
        try {
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            
            // Ambil posisi CSS saat ini (dalam pixel Zoomed)
            const rawLeft = parseFloat(element.style.left);
            const rawTop = parseFloat(element.style.top);

            // NORMALISASI: Kembalikan ke skala 1.0
            const normalizedX = Math.round(rawLeft / scale);
            const normalizedY = Math.round(rawTop / scale);

            // UPDATE LOCAL STATE (Agar tidak hilang saat zoom)
            signatureData = {
                page: pageNumber,
                x: normalizedX,
                y: normalizedY
            };

            console.log(`Saving TTD: Page ${pageNumber}, X=${normalizedX}, Y=${normalizedY} (Scale: ${scale})`);

            fetch(config.saveUrl, {
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

    function deleteParafPositionFromDB() {
        try {
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            
            console.log("Clearing TTD from DB...");

            fetch(config.saveUrl, {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": csrf,
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                },
                body: JSON.stringify({
                    posisi_x: null,
                    posisi_y: null,
                    halaman: null
                })
            })
            .then(res => res.json())
            .then(data => console.log("DB Cleared:", data))
            .catch(err => console.error("Clear Failed:", err));

        } catch (error) {
            console.error("Delete error:", error);
        }
    }

    // ==========================================
    // 4. LOGIKA RENDER PDF & OVERLAY
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
            
            // RENDER SIGNATURE IF EXISTS ON THIS PAGE
            if (signatureData && signatureData.page == num) {
                renderSignatureOverlay(wrap, signatureData);
            }

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

    function renderSignatureOverlay(wrapper, data) {
        const original = document.getElementById("parafImage");
        if (!original || !original.src) return; // Tidak ada gambar master

        const newParaf = original.cloneNode(true);
        newParaf.id = "ttd-dropped-saved";
        newParaf.classList.remove("paraf-image-preview");
        newParaf.classList.add("paraf-dropped");

        // Hitung posisi berdasarkan Scale saat ini
        const left = data.x * scale;
        const top = data.y * scale;
        
        // Ukuran juga harus di-scale jika ingin responsif, 
        // tapi requirement bilang hardcode 100px dulu? 
        // Idealnya: width = 100 * scale. Mari kita coba fix 100px dulu sesuai existing, 
        // atau kita scale juga biar konsisten dengan PDF.
        // Existing code: width = "100px".
        // Masalah: kalau zoom out, 100px jadi terlihat besar. Kalau zoom in, terlihat kecil.
        // Solusi: Scale width juga.
        // Asumsi base width = 100px pada scale 1.0
        const width = 100 * scale; 

        newParaf.style.position = "absolute";
        newParaf.style.left = left + "px";
        newParaf.style.top = top + "px";
        newParaf.style.width = width + "px"; 
        newParaf.style.cursor = "grab";
        newParaf.style.zIndex = "100";

        wrapper.appendChild(newParaf);
        makeElementMovable(newParaf);
        makeElementSelectable(newParaf);
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

            // Hapus TTD lama jika ada
            removeSignatureFromCanvas();

            const original = document.getElementById("parafImage");
            if (!original || !original.src) return;

            const newParaf = original.cloneNode(true);
            newParaf.id = "ttd-dropped-" + Date.now();
            newParaf.classList.remove("paraf-image-preview");
            newParaf.classList.add("paraf-dropped");

            const page = parseInt(dropZone.dataset.pageNumber, 10);
            const rect = dropZone.getBoundingClientRect();
            
            // Hitung posisi relatif
            const x = e.clientX - rect.left - (50 * scale); // Center cursor
            const y = e.clientY - rect.top - (25 * scale);

            newParaf.style.position = "absolute";
            newParaf.style.left = x + "px";
            newParaf.style.top = y + "px";
            newParaf.style.width = (100 * scale) + "px"; // Scale width
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
    // 6. LOGIKA MOVE & SELECT
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
            // Hapus dari state
            signatureData = null;
            // Hapus dari UI
            selectedParaf.remove();
            selectedParaf = null;
            
            // Hapus dari DB
            deleteParafPositionFromDB();
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