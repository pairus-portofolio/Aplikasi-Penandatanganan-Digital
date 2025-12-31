document.addEventListener('DOMContentLoaded', function () {
    const config = window.reviewConfig;
    if (!config) return;

    // Definisikan elemen utama
    const slider = document.getElementById('ttdSize');
    const sizeLabel = document.getElementById('sizeLabel');
    const parafImage = document.getElementById("parafImage");
    const parafBox = document.getElementById("parafBox");
    const fileInput = document.getElementById("parafImageUpload");
    const gantiBtn = document.getElementById("parafGantiBtn");
    const hapusBtn = document.getElementById("parafHapusBtn");

    // ==========================================
    // 1. SETUP SLIDER (RESIZE LOGIC)
    // ==========================================
    if (slider && sizeLabel) {
        // Load ukuran awal
        let initialWidth = 100;
        if (config.savedSignature && config.savedSignature.width) {
            initialWidth = config.savedSignature.width;
        }
        
        // Set UI Slider
        slider.value = initialWidth;
        sizeLabel.innerText = initialWidth + 'px';
        if(parafImage) {
            parafImage.style.width = initialWidth + 'px';
            parafImage.style.height = 'auto';
        }

        // Listener: Saat slider digeser
        slider.addEventListener('input', function() {
            const val = this.value;
            sizeLabel.innerText = val + 'px';
            
            // A. Resize Gambar Sidebar
            if(parafImage) {
                parafImage.style.width = val + 'px';
                parafImage.style.height = 'auto';
            }

            // B. Resize di PDF (Real-time)
            const pdfContainer = document.getElementById('pdf-render-container');
            if (pdfContainer) {
                const droppedItems = Array.from(pdfContainer.querySelectorAll('img')); 
                droppedItems.forEach(item => {
                    if (!item.classList.contains('pdf-page-canvas')) {
                        item.style.width = val + 'px';
                        item.style.height = 'auto'; 
                    }
                });
            }

            // C. Update Config & Auto Save
            if (config.savedSignature) {
                config.savedSignature.width = val;
                clearTimeout(window.resizeTimer);
                window.resizeTimer = setTimeout(() => {
                    saveSignatureToDB(config.savedSignature);
                }, 500);
            }
        });
    }

    // ==========================================
    // 2. INITIALIZE PDF SIGNER
    // ==========================================
    const signer = new PdfSigner({
        pdfUrl: config.pdfUrl,
        workerSrc: config.workerSrc,
        savedData: config.savedSignature, 

        containerId: 'pdf-render-container',
        scrollContainerId: 'scrollContainer',
        zoomTextId: 'zoomLevel',
        sourceImageId: 'parafImage', 
        dragSourceId: 'parafImage',

        // Callback Save
        onSave: (data) => {
            const currentWidth = slider ? slider.value : 100;
            const finalData = { ...data, width: currentWidth, height: 0 };
            config.savedSignature = finalData;
            saveSignatureToDB(finalData);
        },
        
        // Callback Delete (X pada item di PDF)
        onDelete: () => {
            // Panggil fungsi reset posisi saja (Sidebar tetap aman)
            deleteSignatureFromDB();
        }
    });

    // ==========================================
    // 3. MUTATION OBSERVER (Pengawas Drop)
    // ==========================================
    const pdfContainer = document.getElementById('pdf-render-container');
    if (pdfContainer) {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === 1 && node.tagName === 'IMG' && !node.classList.contains('pdf-page-canvas')) {
                        const currentSize = slider ? slider.value : 100;
                        node.style.width = currentSize + 'px';
                        node.style.height = 'auto'; 
                        node.style.position = 'absolute'; 
                    }
                });
            });
        });
        observer.observe(pdfContainer, { childList: true, subtree: true });
    }

    // ==========================================
    // 4. SIDEBAR LOGIC
    // ==========================================
    if (parafBox && parafImage && fileInput) {
        parafBox.addEventListener("click", () => {
            if (!parafBox.classList.contains("has-image")) {
                if (typeof showCaptchaModal === 'function') showCaptchaModal();
            }
        });

        if (gantiBtn) {
            gantiBtn.addEventListener("click", (e) => {
                e.stopPropagation();
                if (typeof showCaptchaModal === 'function') showCaptchaModal();
            });
        }

        if (hapusBtn) {
            hapusBtn.addEventListener("click", (e) => {
                e.stopPropagation();
                confirmDelete(); // Ini untuk hapus permanen (Trash Icon)
            });
        }

        fileInput.addEventListener("change", (e) => handleFileUpload(e.target.files[0]));
        
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
    // 5. API CALLS
    // ==========================================

    // A. Simpan Posisi
    function saveSignatureToDB(data) {
        const csrfElement = document.querySelector('meta[name="csrf-token"]');
        if (!csrfElement) return;

        const widthToSend = data.width || (slider ? slider.value : 100);

        fetch(config.saveUrl, {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": csrfElement.content,
                "Content-Type": "application/json",
                "Accept": "application/json"
            },
            body: JSON.stringify({
                posisi_x: data.x,
                posisi_y: data.y,
                halaman: data.page,
                width: widthToSend,
                height: 0
            })
        })
        .then(res => res.json())
        .then(resData => {
            if (config.debug) console.log("DB Updated:", resData);
        })
        .catch(err => console.error("Save Failed:", err));
    }

    // B. Reset Posisi (Hapus dari PDF SAJA) - Dipanggil oleh PdfSigner
    function deleteSignatureFromDB() {
        const csrfElement = document.querySelector('meta[name="csrf-token"]');
        if (!csrfElement) return;

        fetch(config.saveUrl, { // Hit ke SaveURL dengan null untuk reset posisi
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": csrfElement.content,
                "Content-Type": "application/json",
                "Accept": "application/json"
            },
            body: JSON.stringify({
                posisi_x: null, posisi_y: null, halaman: null, width: null, height: null
            })
        })
        .then(res => res.json())
        .then(data => {
             // 1. Hapus visual di PDF
             if(signer) signer.deletePosition(false); 

             // 2. Reset Config Lokal
             config.savedSignature = null;

             // 3. Reset Slider ke default (Opsional, biar rapi)
             if(slider) {
                 slider.value = 100;
                 sizeLabel.innerText = "100px";
                 if(parafImage) parafImage.style.width = "100px";
             }

             // PENTING: KITA TIDAK MENGHAPUS parafImage.src DI SINI
             // Agar gambar di sidebar tetap ada dan bisa di-drag lagi.

             Swal.fire('Sukses', 'Posisi tanda tangan direset.', 'success');
        })
        .catch(err => {
            console.error("Clear Failed:", err);
            Swal.fire('Error', 'Gagal reset posisi', 'error');
        });
    }

    // C. Hapus Permanen (File Hilang) - Dipanggil tombol Sampah Sidebar
    function handlePermanentDelete() {
        const csrfElement = document.querySelector('meta[name="csrf-token"]');
        if (!csrfElement) return;

        Swal.fire({
            title: 'Menghapus...', text: 'Mohon tunggu', allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        // Gunakan deleteUrl yang baru ditambahkan di blade
        const url = config.deleteUrl || '/kajur/tandatangan/delete';

        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfElement.content,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            Swal.close();
            if (data.status === 'success') {
                // 1. Hapus Gambar Sidebar
                parafImage.src = "";
                parafImage.style.display = "none";
                fileInput.value = "";
                parafBox.classList.remove("has-image");
                const t = parafBox.querySelector('.paraf-text');
                if (t) t.style.display = "block";

                // 2. Hapus juga dari PDF Canvas jika ada
                if(signer) signer.deletePosition(false);
                
                // 3. Reset config
                config.savedSignature = null;

                Swal.fire('Sukses', 'Tanda tangan dihapus permanen.', 'success');
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(err => {
            Swal.close();
            console.error('Delete error:', err);
            Swal.fire('Error', 'Gagal menghapus file', 'error');
        });
    }

    function handleFileUpload(file) {
        if (!file) return;
        const maxSize = 2 * 1024 * 1024;
        if (file.size > maxSize) { Swal.fire('Error', 'File max 2MB', 'error'); return; }
        
        const formData = new FormData();
        formData.append("image", file);
        if (window.lastCaptchaToken) formData.append("g-recaptcha-response", window.lastCaptchaToken);
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        
        Swal.fire({ title: 'Mengupload...', didOpen: () => Swal.showLoading() });

        fetch('/kajur/tandatangan/upload', {
            method: "POST",
            headers: { "X-CSRF-TOKEN": csrfToken, "Accept": "application/json" },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
           Swal.close();
           if(data.status === 'success') {
               parafImage.src = URL.createObjectURL(file);
               parafImage.style.display = 'block';
               parafBox.classList.add("has-image");
               
               if(slider) {
                   slider.value = 100;
                   sizeLabel.innerText = "100px";
                   parafImage.style.width = "100px";
               }
               Swal.fire('Berhasil!', 'Tanda tangan diupload', 'success');
           } else {
               Swal.fire('Error', data.message, 'error');
           }
        })
        .finally(() => {
             if (window.grecaptcha) grecaptcha.reset();
        });
    }

    function confirmDelete() {
        Swal.fire({
            title: 'Hapus File Tanda Tangan?',
            text: 'File tanda tangan akan dihapus permanen dari sistem.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus'
        }).then((result) => {
            if (result.isConfirmed) {
                handlePermanentDelete(); // Panggil fungsi hapus permanen
            }
        });
    }
});