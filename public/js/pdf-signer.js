/**
 * PdfSigner.js
 * Shared logic for Paraf and Tandatangan features.
 * Handles PDF rendering, drag-and-drop, zooming, and coordinate saving.
 */

class PdfSigner {
    constructor(config) {
        this.config = config;
        this.pdfDoc = null;
        this.scale = 1.0;
        this.outputScale = window.devicePixelRatio || 1;
        this.selectedElement = null;
        this.resizeTimer = null;

        // DOM Elements
        this.container = document.getElementById(config.containerId || 'pdf-render-container');
        this.scrollContainer = document.getElementById(config.scrollContainerId || 'scrollContainer');
        this.zoomText = document.getElementById(config.zoomTextId || 'zoomLevel');
        this.currPageElem = document.getElementById(config.currPageId || 'curr_page');
        this.totalPagesElem = document.getElementById(config.totalPagesId || 'total_pages');

        // Initial Data
        this.savedData = config.savedData || null; // { page: 1, x: 100, y: 200 }

        this.init();
    }

    init() {
        if (!this.config.pdfUrl || !window.pdfjsLib) {
            console.error("PDF Configuration or PDF.js missing!");
            return;
        }

        pdfjsLib.GlobalWorkerOptions.workerSrc = this.config.workerSrc;

        // Load PDF
        pdfjsLib.getDocument(this.config.pdfUrl).promise.then((doc) => {
            this.pdfDoc = doc;
            if (this.totalPagesElem) this.totalPagesElem.innerText = doc.numPages;
            this.autoFit();
        });

        // Event Listeners
        this.setupZoomControls();
        this.setupWindowResize();
        this.setupKeyboardEvents();
        this.setupScrollObserver();
    }

    // ==========================================
    // RENDER LOGIC
    // ==========================================
    renderAllPages() {
        this.container.innerHTML = '';
        if (this.pageObserver) this.pageObserver.disconnect();

        for (let num = 1; num <= this.pdfDoc.numPages; num++) {
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
            this.container.appendChild(wrap);

            this.renderPage(num, canvas);
            this.setupDropZone(wrap);

            // Render saved signature if exists on this page
            if (this.savedData && this.savedData.page == num) {
                this.renderOverlay(wrap, this.savedData);
            }

            if (this.pageObserver) this.pageObserver.observe(canvas);
        }

        if (this.zoomText) this.zoomText.innerText = Math.round(this.scale * 100) + "%";
    }

    renderPage(num, canvas) {
        this.pdfDoc.getPage(num).then(page => {
            const ctx = canvas.getContext("2d");
            const viewport = page.getViewport({ scale: this.scale });

            canvas.width = viewport.width * this.outputScale;
            canvas.height = viewport.height * this.outputScale;
            canvas.style.width = viewport.width + "px";
            canvas.style.height = viewport.height + "px";

            page.render({
                canvasContext: ctx,
                viewport,
                transform: this.outputScale !== 1 ? [this.outputScale, 0, 0, this.outputScale, 0, 0] : null
            });
        });
    }

    renderOverlay(wrapper, data) {
        const original = document.getElementById(this.config.sourceImageId || "parafImage");
        if (!original || !original.src) return;

        const newEl = original.cloneNode(true);
        newEl.id = "dropped-saved";
        newEl.classList.remove("paraf-image-preview"); // Assuming common class, or make configurable
        newEl.classList.add("paraf-dropped");

        const left = data.x * this.scale;
        const top = data.y * this.scale;
        const width = 100 * this.scale; // Hardcoded base width 100px

        newEl.style.position = "absolute";
        newEl.style.left = left + "px";
        newEl.style.top = top + "px";
        newEl.style.width = width + "px";
        newEl.style.cursor = "grab";
        newEl.style.zIndex = "100";

        wrapper.appendChild(newEl);
        this.makeElementMovable(newEl);
        this.makeElementSelectable(newEl);
    }

    // ==========================================
    // INTERACTION LOGIC
    // ==========================================
    setupDropZone(dropZone) {
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

            const dragId = e.dataTransfer.getData("text/plain");
            if (dragId !== (this.config.dragSourceId || "parafImage")) return;

            this.removeExistingSignature();

            const original = document.getElementById(this.config.sourceImageId || "parafImage");
            if (!original || !original.src) return;

            const newEl = original.cloneNode(true);
            newEl.id = "dropped-" + Date.now();
            newEl.classList.remove("paraf-image-preview");
            newEl.classList.add("paraf-dropped");

            const page = parseInt(dropZone.dataset.pageNumber, 10);
            const rect = dropZone.getBoundingClientRect();

            const x = e.clientX - rect.left - (50 * this.scale);
            const y = e.clientY - rect.top - (25 * this.scale);

            newEl.style.position = "absolute";
            newEl.style.left = x + "px";
            newEl.style.top = y + "px";
            newEl.style.width = (100 * this.scale) + "px";
            newEl.style.cursor = "grab";
            newEl.style.zIndex = "100";

            dropZone.appendChild(newEl);

            this.makeElementMovable(newEl);
            this.makeElementSelectable(newEl);

            this.savePosition(newEl, page);
        });

        dropZone.addEventListener("click", () => {
            this.deselectElement();
        });
    }

    makeElementMovable(el) {
        let isDragging = false;
        let startX, startY, startLeft, startTop;

        el.addEventListener("mousedown", (e) => {
            e.stopPropagation();
            isDragging = true;
            this.selectElement(el);
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
                    this.savePosition(el, page);
                }
            };

            document.addEventListener("mousemove", move);
            document.addEventListener("mouseup", up);
        });
    }

    makeElementSelectable(el) {
        el.addEventListener("click", (e) => {
            e.stopPropagation();
            this.selectElement(el);
        });
    }

    selectElement(el) {
        if (this.selectedElement && this.selectedElement !== el) {
            this.selectedElement.style.border = "1px dashed transparent";
        }
        this.selectedElement = el;
        el.style.border = "2px dashed #007bff";
    }

    deselectElement() {
        if (this.selectedElement) {
            this.selectedElement.style.border = "1px dashed transparent";
            this.selectedElement = null;
        }
    }

    removeExistingSignature() {
        const existing = document.querySelector('.paraf-dropped');
        if (existing) existing.remove();
    }

    // ==========================================
    // DATA & STATE
    // ==========================================
    savePosition(element, pageNumber) {
        try {
            const rawLeft = parseFloat(element.style.left);
            const rawTop = parseFloat(element.style.top);

            const normalizedX = Math.round(rawLeft / this.scale);
            const normalizedY = Math.round(rawTop / this.scale);

            this.savedData = {
                page: pageNumber,
                x: normalizedX,
                y: normalizedY
            };

            console.log(`Saving: Page ${pageNumber}, X=${normalizedX}, Y=${normalizedY}`);

            if (this.config.onSave) {
                this.config.onSave(this.savedData);
            }

        } catch (error) {
            console.error("Auto save error:", error);
        }
    }

    deletePosition(emitEvent = true) {
        this.savedData = null;
        
        // Remove ANY dropped signature, not just the selected one
        const dropped = document.querySelector('.paraf-dropped');
        if (dropped) dropped.remove();

        if (this.selectedElement) {
            this.selectedElement.remove();
            this.selectedElement = null;
        }
        
        if (emitEvent && this.config.onDelete) {
            this.config.onDelete();
        }
    }

    // ==========================================
    // UTILS & CONTROLS
    // ==========================================
    autoFit() {
        if (!this.pdfDoc) return;
        const w = this.scrollContainer.clientWidth - 40;
        this.pdfDoc.getPage(1).then(page => {
            const v = page.getViewport({ scale: 1 });
            let newScale = w / v.width;
            if (newScale < 0.5) newScale = 0.5;
            if (newScale > 2) newScale = 2;
            this.scale = newScale;
            this.renderAllPages();
        });
    }

    setupZoomControls() {
        const zoomIn = document.getElementById(this.config.zoomInId || "zoomInBtn");
        const zoomOut = document.getElementById(this.config.zoomOutId || "zoomOutBtn");

        if (zoomIn) zoomIn.addEventListener("click", () => { this.scale += 0.1; this.renderAllPages(); });
        if (zoomOut) zoomOut.addEventListener("click", () => { if (this.scale > 0.4) this.scale -= 0.1; this.renderAllPages(); });
    }

    setupWindowResize() {
        window.addEventListener("resize", () => {
            clearTimeout(this.resizeTimer);
            this.resizeTimer = setTimeout(() => this.autoFit(), 200);
        });
    }

    setupKeyboardEvents() {
        document.addEventListener("keydown", (e) => {
            if ((e.key === "Delete" || e.key === "Backspace") && this.selectedElement) {
                this.deletePosition();
            }
        });
    }

    setupScrollObserver() {
        this.pageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const pageNum = entry.target.dataset.pageNumber;
                    if (this.currPageElem) this.currPageElem.innerText = pageNum;
                }
            });
        }, { root: this.scrollContainer, threshold: 0.1 });
    }
}

// Expose to global scope
window.PdfSigner = PdfSigner;
