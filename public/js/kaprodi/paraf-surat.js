document.addEventListener('DOMContentLoaded', function () {
    const config = window.pdfConfig; // Note: Variable name might be different in blade, checking...
    // In paraf-surat.blade.php (not visible here but usually it's window.pdfConfig based on previous file view)
    // Wait, let me check the previous file view of paraf-surat.js. 
    // Line 5: const config = window.pdfConfig;
    
    if (!config) return;

    // Initialize Shared PDF Signer
    const signer = new PdfSigner({
        pdfUrl: config.pdfUrl,
        workerSrc: config.workerSrc,
        savedData: config.savedParaf, // { page, x, y }
        
        // Element IDs
        containerId: 'pdf-render-container',
        scrollContainerId: 'scrollContainer',
        zoomTextId: 'zoomLevel',
        sourceImageId: 'parafImage',
        dragSourceId: 'parafImage',

        // Callbacks
        onSave: (data) => {
            saveParafToDB(data);
        },
        onDelete: () => {
            deleteParafFromDB();
        }
    });

    // ==========================================
    // SIDEBAR LOGIC (Upload / Delete)
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

        if (hapusBtn) {
            hapusBtn.addEventListener("click", (e) => {
                e.stopPropagation();
                confirmDelete();
            });
        }

        fileInput.addEventListener("change", (e) => {
            handleFileUpload(e.target.files[0]);
        });

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
    // API CALLS
    // ==========================================
    function saveParafToDB(data) {
        const csrfElement = document.querySelector('meta[name="csrf-token"]');
        
        if (!csrfElement || !csrfElement.content) {
            Swal.fire('Error', 'CSRF token tidak ditemukan. Silakan refresh halaman.', 'error');
            console.error('CSRF token missing from page');
            return;
        }
        
        const csrf = csrfElement.content;
        
        fetch(config.saveUrl, {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": csrf,
                "Content-Type": "application/json",
                "Accept": "application/json"
            },
            body: JSON.stringify({
                posisi_x: data.x,
                posisi_y: data.y,
                halaman: data.page
            })
        })
        .then(res => res.json())
        .then(resData => {
            if (config.debug) console.log("DB Updated:", resData);
        })
        .catch(err => {
            console.error("Save Failed:", err);
            Swal.fire('Error', 'Gagal menyimpan posisi paraf', 'error');
        });
    }

    function deleteParafFromDB() {
        const csrfElement = document.querySelector('meta[name="csrf-token"]');
        
        if (!csrfElement || !csrfElement.content) {
            Swal.fire('Error', 'CSRF token tidak ditemukan. Silakan refresh halaman.', 'error');
            console.error('CSRF token missing from page');
            return;
        }
        
        const csrf = csrfElement.content;
        
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
        .then(resData => {
            if (config.debug) console.log("DB Cleared:", resData);
        })
        .catch(err => {
            console.error("Clear Failed:", err);
            Swal.fire('Error', 'Gagal menghapus posisi paraf', 'error');
        });
    }

    function handleFileUpload(file) {
        if (!file) return;

        // Validasi ukuran file (max 2MB)
        const maxSize = 2 * 1024 * 1024; // 2MB in bytes
        if (file.size > maxSize) {
            Swal.fire({
                icon: 'error',
                title: 'File Terlalu Besar',
                text: `Ukuran file maksimal 2MB. File Anda: ${(file.size / 1024 / 1024).toFixed(2)}MB`,
            });
            return;
        }

        // Validasi tipe file
        const allowedTypes = ['image/png', 'image/jpeg', 'image/jpg'];
        if (!allowedTypes.includes(file.type)) {
            Swal.fire('Error', 'Format file harus PNG, JPG, atau JPEG', 'error');
            return;
        }

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

        if (!csrfToken) {
            Swal.fire('Error', 'CSRF token tidak ditemukan. Silakan refresh halaman.', 'error');
            return;
        }

        // Show loading
        Swal.fire({
            title: 'Mengupload...',
            text: 'Mohon tunggu',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        fetch('/kaprodi/paraf/upload', {
            method: "POST",
            headers: { "X-CSRF-TOKEN": csrfToken, "Accept": "application/json" },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            Swal.close();
            if (data.status === "success") {
                Swal.fire('Sukses', 'Paraf berhasil diupload', 'success');
            } else {
                Swal.fire('Error', data.message || 'Upload gagal', 'error');
            }
        })
        .catch(err => {
            Swal.close();
            console.error('Upload error:', err);
            Swal.fire('Error', 'Gagal upload paraf', 'error');
        });
    }

    function confirmDelete() {
        Swal.fire({
            title: 'Hapus Paraf?',
            text: 'Paraf akan dihapus secara permanen.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus'
        }).then((result) => {
            if (!result.isConfirmed) return;

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            
            if (!csrfToken) {
                Swal.fire('Error', 'CSRF token tidak ditemukan. Silakan refresh halaman.', 'error');
                return;
            }

            // Show loading
            Swal.fire({
                title: 'Menghapus...',
                text: 'Mohon tunggu',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

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
                Swal.close();
                if (data.status === 'success') {
                    // Reset UI
                    parafImage.src = "";
                    parafImage.style.display = "none";
                    fileInput.value = "";
                    parafBox.classList.remove("has-image");
                    const t = parafBox.querySelector('.paraf-text');
                    if (t) t.style.display = "block";

                    // Remove from Canvas via Signer (Silent, no callback)
                    signer.deletePosition(false);
                    
                    Swal.fire('Sukses', 'Paraf berhasil dihapus', 'success');
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(err => {
                Swal.close();
                console.error('Delete error:', err);
                Swal.fire('Error', 'Gagal menghapus paraf', 'error');
            });
        });
    }
});