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
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        // Endpoint: /paraf-surat/{id}/save-paraf
        // window.suratId is defined in blade
        fetch(`/paraf-surat/${window.suratId}/save-paraf`, {
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

    function deleteParafFromDB() {
        // Since ParafController.saveParaf might not handle nulls yet?
        // Wait, I only updated TandatanganController to handle nulls.
        // I MUST update ParafController to handle nulls too!
        // Or I can use the same logic if I update ParafController.
        // Let's assume I will update ParafController next.
        
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        fetch(`/paraf-surat/${window.suratId}/save-paraf`, {
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
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        fetch('/kaprodi/paraf/upload', {
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
        .catch(err => Swal.fire('Error', 'Gagal upload', 'error'));
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
                    // Reset UI
                    parafImage.src = "";
                    parafImage.style.display = "none";
                    fileInput.value = "";
                    parafBox.classList.remove("has-image");
                    const t = parafBox.querySelector('.paraf-text');
                    if (t) t.style.display = "block";

                    // Remove from Canvas via Signer
                    signer.deletePosition();
                    
                    Swal.fire('Sukses', 'Paraf dihapus', 'success');
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(err => Swal.fire('Error', 'Gagal menghapus', 'error'));
        });
    }
});