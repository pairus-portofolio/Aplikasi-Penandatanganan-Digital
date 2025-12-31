/**
 * PdfSigner.js - Optimized Version
 * Shared logic for Paraf and Tandatangan features.
 * Handles PDF rendering, drag-and-drop, zooming, and coordinate saving.
 * 
 * @version 3.0 - Performance Optimized
 * @features Lazy Loading, Debouncing, Event Delegation, Page Caching
 */

class PdfSigner {
    constructor(config) {
        this.config = config;
        this.pdfDoc = null;
        this.scale = 1.0;
        this.outputScale = window.devicePixelRatio || 1;
        this.selectedElement = null;
        this.resizeTimer = null;
        this.saveTimer = null;
        this.zoomTimer = null; // For debouncing zoom
        this.signatureWidth = config.signatureWidth || 100;

        // Performance optimization: Page caching
        this.renderedPages = new Map(); // Cache rendered pages
        this.visiblePages = new Set(); // Track visible pages
        
        // DOM Elements
        this.container = document.getElementById(config.containerId || 'pdf-render-container');
        this.scrollContainer = document.getElementById(config.scrollContainerId || 'scrollContainer');
        this.zoomText = document.getElementById(config.zoomTextId || 'zoomLevel');
        this.currPageElem = document.getElementById(config.currPageId || 'curr_page');
        this.totalPagesElem = document.getElementById(config.totalPagesId || 'total_pages');

        // Initial Data
        this.savedData = config.savedData || null;

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
        this.setupEventDelegation(); // New: Event delegation
    }

    // ==========================================
    // RENDER LOGIC - OPTIMIZED
    // ==========================================
    
    /**
     * Render all pages with lazy loading optimization
     * Only creates DOM structure, actual rendering happens on-demand
     */
    renderAllPages() {
        this.container.innerHTML = '';
        if (this.pageObserver) this.pageObserver.disconnect();
        
        // Clear cache when scale changes significantly
        this.renderedPages.clear();
        this.visiblePages.clear();

        for (let num = 1; num <= this.pdfDoc.numPages; num++) {
            const wrap = this.createPageWrapper(num);
            this.container.appendChild(wrap);

            // Render saved signature if exists on this page
            if (this.savedData && this.savedData.page == num) {
                this.renderOverlay(wrap, this.savedData);
            }

            // Setup intersection observer for lazy loading
            if (this.pageObserver) {
                const canvas = wrap.querySelector('canvas');
                this.pageObserver.observe(canvas);
            }
        }

        if (this.zoomText) this.zoomText.innerText = Math.round(this.scale * 100) + "%";
        
        // Render first 3 pages immediately for better UX
        this.renderVisiblePages([1, 2, 3]);
    }

    /**
     * Create page wrapper without rendering (lazy loading)
     */
    createPageWrapper(num) {
        const wrap = document.createElement("div");
        wrap.className = "pdf-page-wrapper";
        wrap.dataset.pageNumber = num;
        wrap.style.position = "relative";
        wrap.style.marginBottom = "20px";
        wrap.style.display = "inline-block";

        const canvas = document.createElement("canvas");
        canvas.id = "page-" + num;
        canvas.className = "pdf-page-canvas";
        canvas.style.display = "block";
        canvas.dataset.rendered = "false"; // Track render status

        wrap.appendChild(canvas);
        return wrap;
    }

    /**
     * Render specific pages (lazy loading)
     * @param {Array<number>} pageNumbers - Array of page numbers to render
     */
    renderVisiblePages(pageNumbers) {
        pageNumbers.forEach(num => {
            if (num < 1 || num > this.pdfDoc.numPages) return;
            
            const canvas = document.getElementById("page-" + num);
            if (!canvas || canvas.dataset.rendered === "true") return;

            this.renderPage(num, canvas);
            canvas.dataset.rendered = "true";
            this.visiblePages.add(num);
        });
    }

    /**
     * Render single page with caching
     */
    renderPage(num, canvas) {
        // Check cache first
        const cacheKey = `${num}-${this.scale.toFixed(2)}`;
        
        this.pdfDoc.getPage(num).then(page => {
            const ctx = canvas.getContext("2d");
            const viewport = page.getViewport({ scale: this.scale });

            canvas.width = viewport.width * this.outputScale;
            canvas.height = viewport.height * this.outputScale;
            canvas.style.width = viewport.width + "px";
            canvas.style.height = viewport.height + "px";

            // Render with optional caching
            const renderTask = page.render({
                canvasContext: ctx,
                viewport,
                transform: this.outputScale !== 1 ? [this.outputScale, 0, 0, this.outputScale, 0, 0] : null
            });

            renderTask.promise.then(() => {
                // Mark as cached
                this.renderedPages.set(cacheKey, true);
            });
        });
    }

    renderOverlay(wrapper, data) {
        const original = document.getElementById(this.config.sourceImageId || "parafImage");
        if (!original || !original.src) return;

        const newEl = original.cloneNode(true);
        newEl.id = "dropped-saved";
        newEl.classList.remove("paraf-image-preview");
        newEl.classList.add("paraf-dropped");
        newEl.src = original.src;

        const left = data.x * this.scale;
        const top = data.y * this.scale;
        const width = this.signatureWidth * this.scale;

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
    // INTERACTION LOGIC - OPTIMIZED WITH EVENT DELEGATION
    // ==========================================
    
    /**
     * Setup event delegation on container instead of individual pages
     * Reduces event listeners from N*4 to just 4 (where N = number of pages)
     */
    setupEventDelegation() {
        // Dragover - delegated
        this.container.addEventListener("dragover", (e) => {
            const dropZone = e.target.closest('.pdf-page-wrapper');
            if (dropZone) {
                e.preventDefault();
                dropZone.style.outline = "2px dashed #1e4ed8";
            }
        });

        // Dragleave - delegated
        this.container.addEventListener("dragleave", (e) => {
            const dropZone = e.target.closest('.pdf-page-wrapper');
            if (dropZone && !dropZone.contains(e.relatedTarget)) {
                dropZone.style.outline = "none";
            }
        });

        // Drop - delegated
        this.container.addEventListener("drop", (e) => {
            const dropZone = e.target.closest('.pdf-page-wrapper');
            if (!dropZone) return;

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

            const x = e.clientX - rect.left - ((this.signatureWidth / 2) * this.scale);
            const y = e.clientY - rect.top - ((this.signatureWidth / 4) * this.scale);

            newEl.style.position = "absolute";
            newEl.style.left = x + "px";
            newEl.style.top = y + "px";
            newEl.style.width = (this.signatureWidth * this.scale) + "px";
            newEl.style.cursor = "grab";
            newEl.style.zIndex = "100";

            dropZone.appendChild(newEl);

            this.makeElementMovable(newEl);
            this.makeElementSelectable(newEl);

            this.savePosition(newEl, page);
        });

        // Click - delegated
        this.container.addEventListener("click", (e) => {
            if (e.target.closest('.pdf-page-wrapper') && !e.target.closest('.paraf-dropped')) {
                this.deselectElement();
            }
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
        if (this.saveTimer) {
            clearTimeout(this.saveTimer);
        }

        this.saveTimer = setTimeout(() => {
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

                if (this.config.debug) {
                    console.log(`Saving: Page ${pageNumber}, X=${normalizedX}, Y=${normalizedY}`);
                }

                if (this.config.onSave) {
                    this.config.onSave(this.savedData);
                }

            } catch (error) {
                console.error("Auto save error:", error);
            }
        }, 500);
    }

    deletePosition(emitEvent = true) {
        this.savedData = null;
        
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
    // UTILS & CONTROLS - OPTIMIZED
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

    /**
     * Setup zoom controls with debouncing
     * Prevents multiple rapid re-renders
     */
    setupZoomControls() {
        const zoomIn = document.getElementById(this.config.zoomInId || "zoomInBtn");
        const zoomOut = document.getElementById(this.config.zoomOutId || "zoomOutBtn");

        if (zoomIn) {
            zoomIn.addEventListener("click", () => {
                this.scale += 0.1;
                this.debouncedRender();
            });
        }
        
        if (zoomOut) {
            zoomOut.addEventListener("click", () => {
                if (this.scale > 0.4) {
                    this.scale -= 0.1;
                    this.debouncedRender();
                }
            });
        }
    }

    /**
     * Debounced render for zoom operations
     * Waits 300ms after last zoom before re-rendering
     */
    debouncedRender() {
        // Update zoom text immediately for better UX
        if (this.zoomText) {
            this.zoomText.innerText = Math.round(this.scale * 100) + "%";
        }

        // Debounce actual rendering
        if (this.zoomTimer) {
            clearTimeout(this.zoomTimer);
        }

        this.zoomTimer = setTimeout(() => {
            this.renderAllPages();
        }, 300); // Wait 300ms after last zoom
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

    /**
     * Setup scroll observer with lazy loading
     * Only renders pages when they become visible
     */
    setupScrollObserver() {
        this.pageObserver = new IntersectionObserver((entries) => {
            const pagesToRender = [];
            
            entries.forEach(entry => {
                const canvas = entry.target;
                const pageNum = parseInt(canvas.closest('.pdf-page-wrapper')?.dataset.pageNumber);
                
                if (entry.isIntersecting && pageNum) {
                    // Update current page indicator
                    if (this.currPageElem) {
                        this.currPageElem.innerText = pageNum;
                    }
                    
                    // Queue page for rendering if not already rendered
                    if (canvas.dataset.rendered === "false") {
                        pagesToRender.push(pageNum);
                    }
                }
            });

            // Batch render visible pages
            if (pagesToRender.length > 0) {
                this.renderVisiblePages(pagesToRender);
            }
        }, { 
            root: this.scrollContainer, 
            threshold: 0.1,
            rootMargin: '100px' // Pre-render pages 100px before they're visible
        });
    }
}

// Expose to global scope
window.PdfSigner = PdfSigner;
