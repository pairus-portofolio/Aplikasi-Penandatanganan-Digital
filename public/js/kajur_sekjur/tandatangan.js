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
        // Klik Box -> Captcha
        parafBox.addEventListener("click", () => {
            if (!parafBox.classList.contains("has-image")) {
                document.getElementById("captchaBox").style.display = "block";
            }
        });

        // Tombol Ganti -> Captcha
        if (gantiBtn) {
            gantiBtn.addEventListener("click", (e) => {
                e.stopPropagation();
                document.getElementById("captchaBox").style.display = "block";
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
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
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
        .then(resData => console.log("DB Updated:", resData))
        .catch(err => console.error("Save Failed:", err));
    }

    function deleteSignatureFromDB() {
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
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
        .then(resData => console.log("DB Cleared:", resData))
        .catch(err => console.error("Clear Failed:", err));
    }

    function handleFileUpload(file) {
        if (!file) return;

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

        fetch('/kajur/tandatangan/upload', {
            method: "POST",
            headers: { "X-CSRF-TOKEN": csrfToken, "Accept": "application/json" },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status !== "success") {
                Swal.fire('Error', data.message || 'Upload gagal', 'error');
            }
        })
        .catch(err => Swal.fire('Error', 'Gagal upload', 'error'))
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
            confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus'
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
                    // Reset UI
                    parafImage.src = "";
                    parafImage.style.display = "none";
                    fileInput.value = "";
                    parafBox.classList.remove("has-image");
                    const t = parafBox.querySelector('.paraf-text');
                    if (t) t.style.display = "block";

                    // Remove from Canvas via Signer
                    signer.deletePosition(); 
                    
                    Swal.fire('Sukses', 'Tanda tangan dihapus', 'success');
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(err => Swal.fire('Error', 'Gagal menghapus', 'error'));
        });
    }
});