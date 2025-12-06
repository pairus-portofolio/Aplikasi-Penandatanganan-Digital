/**
 * Paraf Surat - Main Script
 * Mengelola upload, delete, dan penempatan paraf pada dokumen PDF
 * 
 * @author Digital Signature Team
 * @version 2.0
 */

// ==========================================
// CONFIGURATION & CONSTANTS
// ==========================================
const PARAF_CONFIG = {
    // File validation
    MAX_FILE_SIZE: 2 * 1024 * 1024, // 2MB in bytes
    ALLOWED_TYPES: ['image/png', 'image/jpeg', 'image/jpg'],

    // Messages
    MESSAGES: {
        FILE_TOO_LARGE: 'File Terlalu Besar',
        INVALID_FORMAT: 'Format file harus PNG, JPG, atau JPEG',
        CSRF_MISSING: 'CSRF token tidak ditemukan. Silakan refresh halaman.',
        CONFIG_INCOMPLETE: 'Konfigurasi PDF tidak lengkap. Silakan refresh halaman.',
        UPLOAD_SUCCESS: 'Paraf berhasil diupload',
        UPLOAD_FAILED: 'Gagal upload paraf',
        DELETE_SUCCESS: 'Paraf berhasil dihapus',
        DELETE_FAILED: 'Gagal menghapus paraf',
        SAVE_FAILED: 'Gagal menyimpan posisi paraf',
        OPERATION_IN_PROGRESS: 'Mohon tunggu operasi sebelumnya selesai'
    }
};

// ==========================================
// HELPER FUNCTIONS
// ==========================================

/**
 * Mendapatkan CSRF token dari meta tag
 * @returns {string|null} CSRF token atau null jika tidak ditemukan
 */
function getCsrfToken() {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    if (!token) {
        Swal.fire('Error', PARAF_CONFIG.MESSAGES.CSRF_MISSING, 'error');
        console.error('CSRF token missing from page');
    }
    return token;
}

/**
 * Menampilkan loading indicator dengan SweetAlert2
 * @param {string} title - Judul loading
 * @param {string} text - Teks loading
 */
function showLoading(title = 'Memproses...', text = 'Mohon tunggu') {
    Swal.fire({
        title,
        text,
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
}

/**
 * Handle fetch response dengan error checking
 * @param {Response} response - Fetch response object
 * @returns {Promise<any>} Parsed JSON response
 * @throws {Error} Jika HTTP status tidak OK
 */
async function handleFetchResponse(response) {
    if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }
    return response.json();
}

/**
 * Validasi file sebelum upload
 * @param {File} file - File yang akan divalidasi
 * @returns {Object} { valid: boolean, error: string|null }
 */
function validateFile(file) {
    if (!file) {
        return { valid: false, error: 'File tidak ditemukan' };
    }

    // Validasi ukuran file
    if (file.size > PARAF_CONFIG.MAX_FILE_SIZE) {
        const fileSizeMB = (file.size / 1024 / 1024).toFixed(2);
        return {
            valid: false,
            error: `Ukuran file maksimal 2MB. File Anda: ${fileSizeMB}MB`
        };
    }

    // Validasi tipe file
    if (!PARAF_CONFIG.ALLOWED_TYPES.includes(file.type)) {
        return {
            valid: false,
            error: PARAF_CONFIG.MESSAGES.INVALID_FORMAT
        };
    }

    return { valid: true, error: null };
}

// ==========================================
// MAIN APPLICATION
// ==========================================
document.addEventListener('DOMContentLoaded', function () {
    const config = window.pdfConfig;

    // Validasi konfigurasi lengkap
    if (!config || !config.pdfUrl || !config.saveUrl || !config.uploadUrl || !config.deleteUrl) {
        console.error('PDF configuration incomplete:', config);
        Swal.fire('Error', PARAF_CONFIG.MESSAGES.CONFIG_INCOMPLETE, 'error');
        return;
    }

    // Cache CSRF token
    const csrfToken = getCsrfToken();
    if (!csrfToken) return;

    // Rate limiting flags
    let isUploading = false;
    let isDeleting = false;

    // Initialize Shared PDF Signer
    const signer = new PdfSigner({
        pdfUrl: config.pdfUrl,
        workerSrc: config.workerSrc,
        savedData: config.savedParaf,

        // Element IDs
        containerId: 'pdf-render-container',
        scrollContainerId: 'scrollContainer',
        zoomTextId: 'zoomLevel',
        sourceImageId: 'parafImage',
        dragSourceId: 'parafImage',

        // Callbacks
        onSave: (data) => saveParafToDB(data),
        onDelete: () => deleteParafFromDB()
    });

    // ==========================================
    // DOM ELEMENTS
    // ==========================================
    const parafBox = document.getElementById("parafBox");
    const parafImage = document.getElementById("parafImage");
    const fileInput = document.getElementById("parafImageUpload");
    const gantiBtn = document.getElementById("parafGantiBtn");
    const hapusBtn = document.getElementById("parafHapusBtn");

    // ==========================================
    // EVENT LISTENERS
    // ==========================================
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
    // API FUNCTIONS
    // ==========================================

   /**
     * Menyimpan posisi paraf ke database
     * @param {Object} data - Data posisi paraf
     * @param {number|null} data.x - Koordinat X
     * @param {number|null} data.y - Koordinat Y
     * @param {number|null} data.page - Nomor halaman
     */
    function saveParafToDB(data) {
        // Menggunakan const csrfToken yang sudah diinisialisasi
        if (!csrfToken) return;

        // [REVISI]: Menggunakan operator ?? null untuk memastikan nilai adalah null jika 0 atau falsy lainnya.
        const payload = {
            posisi_x: data.x ?? null, 
            posisi_y: data.y ?? null,
            halaman: data.page ?? null
        };
        
        // Cek jika data.x, data.y, atau data.page adalah 0 (nol).
        // Pada JS, 0 || null akan menghasilkan null, yang kita inginkan agar validasi Laravel lolos.
        // Namun, jika nilai asli adalah 0 (koordinat), kita harus mengirim 0.

        // Perbaiki payload agar 0 tetap 0 (koordinat valid)
        payload.posisi_x = data.x !== undefined && data.x !== null ? data.x : null;
        payload.posisi_y = data.y !== undefined && data.y !== null ? data.y : null;
        payload.halaman = data.page !== undefined && data.page !== null ? data.page : null;
        
        if (payload.posisi_x === 0) payload.posisi_x = 0; // Pastikan 0 tidak jadi null
        if (payload.posisi_y === 0) payload.posisi_y = 0;
        if (payload.halaman === 0) payload.halaman = 1; // Halaman minimal 1

        fetch(config.saveUrl, {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": csrfToken,
                "Content-Type": "application/json", 
                "Accept": "application/json"
            },
            body: JSON.stringify(payload) // Menggunakan payload yang sudah dipastikan tipenya
        })
            .then(handleFetchResponse)
            .then(resData => {
                if (config.debug) console.log("DB Updated:", resData);
            })
            .catch(err => {
                console.error("Save Failed:", err);
                Swal.fire('Error', PARAF_CONFIG.MESSAGES.SAVE_FAILED, 'error');
            });
    }
    /**
     * Menghapus posisi paraf dari database
     */
    function deleteParafFromDB() {
        fetch(config.saveUrl, {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": csrfToken,
                "Content-Type": "application/json",
                "Accept": "application/json"
            },
            body: JSON.stringify({
                posisi_x: null,
                posisi_y: null,
                halaman: null
            })
        })
            .then(handleFetchResponse)
            .then(resData => {
                if (config.debug) console.log("DB Cleared:", resData);
            })
            .catch(err => {
                console.error("Clear Failed:", err);
                Swal.fire('Error', PARAF_CONFIG.MESSAGES.SAVE_FAILED, 'error');
            });
    }

    /**
     * Handle upload file paraf
     * @param {File} file - File gambar paraf yang akan diupload
     */
    function handleFileUpload(file) {
        // Rate limiting check
        if (isUploading) {
            Swal.fire('Info', PARAF_CONFIG.MESSAGES.OPERATION_IN_PROGRESS, 'info');
            return;
        }

        // Validasi file
        const validation = validateFile(file);
        if (!validation.valid) {
            Swal.fire({
                icon: 'error',
                title: validation.error.includes('maksimal') ? PARAF_CONFIG.MESSAGES.FILE_TOO_LARGE : 'Error',
                text: validation.error
            });
            fileInput.value = ""; // Reset input
            return;
        }

        isUploading = true;
        showLoading('Mengupload...', 'Mohon tunggu');

        const formData = new FormData();
        formData.append("image", file);

        fetch(config.uploadUrl, {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": csrfToken,
                "Accept": "application/json"
            },
            body: formData
        })
            .then(handleFetchResponse)
            .then(data => {
                Swal.close();
                if (data.status === "success") {
                    // Update UI hanya setelah upload berhasil (fix race condition)
                    const objectUrl = URL.createObjectURL(file);
                    parafImage.src = objectUrl;
                    parafImage.style.display = "block";
                    parafBox.classList.add("has-image");
                    const t = parafBox.querySelector('.paraf-text');
                    if (t) t.style.display = 'none';

                    // Cleanup object URL setelah load
                    //parafImage.onload = () => URL.revokeObjectURL(objectUrl);


                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: PARAF_CONFIG.MESSAGES.UPLOAD_SUCCESS,
                        confirmButtonColor: '#10b981',
                        confirmButtonText: 'OK'
                    });
                } else {
                    throw new Error(data.message || 'Upload gagal');
                }
            })
            .catch(err => {
                Swal.close();
                console.error('Upload error:', err);
                Swal.fire('Error', err.message || PARAF_CONFIG.MESSAGES.UPLOAD_FAILED, 'error');
                fileInput.value = ""; // Reset input on error
            })
            .finally(() => {
                isUploading = false;
            });
    }

    /**
     * Konfirmasi dan handle delete paraf
     */
    function confirmDelete() {
        // Rate limiting check
        if (isDeleting) {
            Swal.fire('Info', PARAF_CONFIG.MESSAGES.OPERATION_IN_PROGRESS, 'info');
            return;
        }

        Swal.fire({
            title: 'Hapus Paraf?',
            text: 'Paraf akan dihapus secara permanen.',
            icon: 'warning',
            showCancelButton: true,
            showCloseButton: false,
            confirmButtonColor: '#d33',
            cancelButtonText: 'Batal',
            confirmButtonText: 'Ya, Hapus'
        }).then((result) => {
            if (!result.isConfirmed) return;

            isDeleting = true;

            // Simpan state UI untuk rollback jika gagal
            const previousState = {
                src: parafImage.src,
                display: parafImage.style.display,
                hasImage: parafBox.classList.contains("has-image")
            };

            showLoading('Menghapus...', 'Mohon tunggu');

            fetch(config.deleteUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
                .then(handleFetchResponse)
                .then(data => {
                    Swal.close();
                    if (data.status === 'success') {
                        // Reset UI hanya setelah delete berhasil
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
                            text: PARAF_CONFIG.MESSAGES.DELETE_SUCCESS,
                            confirmButtonColor: '#10b981',
                            confirmButtonText: 'OK'
                        });
                    } else {
                        throw new Error(data.message || 'Delete gagal');
                    }
                })
                .catch(err => {
                    Swal.close();
                    console.error('Delete error:', err);

                    // Rollback UI ke state sebelumnya
                    parafImage.src = previousState.src;
                    parafImage.style.display = previousState.display;
                    if (previousState.hasImage) {
                        parafBox.classList.add("has-image");
                    }

                    Swal.fire('Error', err.message || PARAF_CONFIG.MESSAGES.DELETE_FAILED, 'error');
                })
                .finally(() => {
                    isDeleting = false;
                });
        });
    }
});