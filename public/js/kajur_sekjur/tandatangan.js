document.addEventListener('DOMContentLoaded', function () {
    const config = window.reviewConfig;
    if (!config) return;

    // Initialize Shared PDF Signer
    const signer = new PdfSigner({
        pdfUrl: config.pdfUrl,
        workerSrc: config.workerSrc,
        savedData: config.savedSignature, // { page, x, y }

        // Element IDs (Defaults are fine, but being explicit)
        containerId: 'pdf-render-container',
        scrollContainerId: 'scrollContainer',
        zoomTextId: 'zoomLevel',
        sourceImageId: 'parafImage', // The image in sidebar
        dragSourceId: 'parafImage',

        // Callbacks
        onSave: (data) => {
            saveSignatureToDB(data);
        },
        onDelete: () => {
            deleteSignatureFromDB();
        }
    });

    // ==========================================
    // SIDEBAR LOGIC (Upload / Delete / Captcha)
    // ==========================================
    const parafBox = document.getElementById("parafBox");
    const parafImage = document.getElementById("parafImage");
    const fileInput = document.getElementById("parafImageUpload");
    const gantiBtn = document.getElementById("parafGantiBtn");
    const hapusBtn = document.getElementById("parafHapusBtn");

    if (parafBox && parafImage && fileInput) {
        // Klik Box -> Captcha Modal
        parafBox.addEventListener("click", () => {
            if (!parafBox.classList.contains("has-image")) {
                if (typeof showCaptchaModal === 'function') {
                    showCaptchaModal();
                }
            }
        });

        // Tombol Ganti -> Captcha Modal
        if (gantiBtn) {
            gantiBtn.addEventListener("click", (e) => {
                e.stopPropagation();
                if (typeof showCaptchaModal === 'function') {
                    showCaptchaModal();
                }
            });
        }

        // Hapus Permanen
        if (hapusBtn) {
            hapusBtn.addEventListener("click", (e) => {
                e.stopPropagation();
                confirmDelete();
            });
        }

        // Upload File
        fileInput.addEventListener("change", (e) => {
            handleFileUpload(e.target.files[0]);
        });

        // Drag Start (Sidebar)
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
    function saveSignatureToDB(data) {
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
                Swal.fire('Error', 'Gagal menyimpan posisi tanda tangan', 'error');
            });
    }

    function deleteSignatureFromDB() {
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
                Swal.fire('Error', 'Gagal menghapus posisi tanda tangan', 'error');
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
        if (window.lastCaptchaToken) {
            formData.append("g-recaptcha-response", window.lastCaptchaToken);
        }

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

        fetch('/kajur/tandatangan/upload', {
            method: "POST",
            headers: { "X-CSRF-TOKEN": csrfToken, "Accept": "application/json" },
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                Swal.close();
                if (data.status === "success") {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Tanda tangan berhasil diupload',
                        confirmButtonColor: '#10b981',
                        confirmButtonText: 'OK'
                    });
                } else {
                    Swal.fire('Error', data.message || 'Upload gagal', 'error');
                }
            })
            .catch(err => {
                Swal.close();
                console.error('Upload error:', err);
                Swal.fire('Error', 'Gagal upload tanda tangan', 'error');
            })
            .finally(() => {
                if (window.grecaptcha) {
                    grecaptcha.reset();
                    window.lastCaptchaToken = null;
                }
            });
    }

    function confirmDelete() {
        Swal.fire({
            title: 'Hapus Tanda Tangan?',
            text: 'Tanda tangan akan dihapus secara permanen.',
            icon: 'warning',
            showCancelButton: true,
            showCloseButton: false,
            confirmButtonColor: '#d33',
            cancelButtonText: 'Batal',
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

                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: 'Tanda tangan berhasil dihapus',
                            confirmButtonColor: '#10b981',
                            confirmButtonText: 'OK'
                        });
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                })
                .catch(err => {
                    Swal.close();
                    console.error('Delete error:', err);
                    Swal.fire('Error', 'Gagal menghapus tanda tangan', 'error');
                });
        });
    }
});