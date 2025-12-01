// Menjalankan script setelah seluruh halaman siap
document.addEventListener("DOMContentLoaded", function () {
    // Mengambil elemen utama untuk upload file
    const dropArea = document.getElementById("drop-area");
    const fileInput = document.getElementById("file-input");
    const submitButtonWrapper = document.getElementById(
        "submit-button-wrapper"
    );
    const judulSuratInput = document.getElementById("judul_surat");

    // Menyimpan tampilan awal area upload
    const initialDropAreaHTML = dropArea.innerHTML;

    // Mengunci input agar hanya menerima .pdf
    fileInput.setAttribute("accept", ".pdf");

    // Membuka file picker ketika area upload diklik
    dropArea.addEventListener("click", () => {
        fileInput.value = "";
        fileInput.click();
    });

    // Memberi efek saat file sedang di-drag di atas area upload
    dropArea.addEventListener("dragover", (e) => {
        e.preventDefault();
        dropArea.classList.add("drag-over");
    });

    // Menghapus efek drag ketika file keluar dari area upload
    dropArea.addEventListener("dragleave", () => {
        dropArea.classList.remove("drag-over");
    });

    // Memproses file ketika dilepas ke area upload
    dropArea.addEventListener("drop", (e) => {
        e.preventDefault();
        dropArea.classList.remove("drag-over");
        const files = e.dataTransfer.files;

        if (files.length > 0) {
            try {
                fileInput.files = files;
            } catch (err) {
                console.error("Tidak dapat meng-assign fileInput.files:", err);
                alert("Gagal memproses file. Silakan coba lagi atau gunakan tombol 'Pilih File'.");
                return; // FIX BUG #2: Return early jika error
            }
            handleFile(files[0]);
        }
    });

    // Memproses file ketika dipilih melalui file picker
    fileInput.addEventListener("change", function () {
        if (this.files && this.files.length > 0) {
            handleFile(this.files[0]);
        } else {
            resetFileSelection();
        }
    });

    // Mengecek dan menampilkan file yang dipilih
    function handleFile(file) {
        // Memastikan file adalah .pdf
        if (!file.name.toLowerCase().endsWith(".pdf")) {
            alert("Hanya file .pdf yang diperbolehkan.");
            resetFileSelection();
            return;
        }

        // Menampilkan file yang terpilih di dalam area upload
        dropArea.classList.add("has-file");
        dropArea.innerHTML = `
            <div class="selected-file">
                <span class="selected-file-icon">📄</span>
                <span class="selected-file-label"></span>
                <span class="selected-file-name" title="${file.name}">${file.name}</span>
            </div>
        `;

        // FIX BUG #11: Sanitize nama file sebelum assign ke judul
        const sanitizedFileName = sanitizeFileName(file.name);
        judulSuratInput.value = sanitizedFileName;

        // Menampilkan tombol submit setelah file valid
        submitButtonWrapper.style.display = "block";
    }

    // Mengambil elemen untuk input alur penandatanganan
    const userSelect = document.getElementById("userSelect");
    const alurList = document.getElementById("alurList");
    const alurInput = document.getElementById("alurInput");

    // Menambahkan user ke daftar alur ketika dipilih
    userSelect.addEventListener("change", function () {
        const userId = this.value;
        const selectedOption = this.options[this.selectedIndex];
        const userName = selectedOption.text;

        if (userId) {
            // Cek apakah user sudah ada di list (preventif)
            const existingItem = alurList.querySelector(`li[data-user-id="${userId}"]`);
            if (existingItem) {
                alert("User ini sudah ada dalam alur.");
                this.value = "";
                return;
            }

            const listItem = document.createElement("li");
            listItem.classList.add("alur-item");
            listItem.textContent = userName;
            listItem.dataset.userId = userId;

            // Tombol untuk menghapus user dari alur
            const removeButton = document.createElement("button");
            removeButton.classList.add("remove-alur-btn");
            removeButton.textContent = "Hapus";

            removeButton.onclick = function () {
                alurList.removeChild(listItem);
                updateAlurInput();
                
                // Re-enable option di dropdown saat dihapus
                const optionToEnable = userSelect.querySelector(`option[value="${userId}"]`);
                if (optionToEnable) {
                    optionToEnable.disabled = false;
                }
            };

            listItem.appendChild(removeButton);
            alurList.appendChild(listItem);

            updateAlurInput();
            
            // Disable option yang dipilih agar tidak bisa dipilih lagi
            selectedOption.disabled = true;
            
            // Reset dropdown ke default
            this.value = "";
        }
    });

    // Memperbarui input hidden sesuai urutan alur
    function updateAlurInput() {
        const alurUserIds = [];
        alurList.querySelectorAll("li").forEach((item) => {
            alurUserIds.push(item.dataset.userId);
        });
        alurInput.value = alurUserIds.join(",");
    }

    // FIX BUG #11: Fungsi untuk sanitasi nama file
    function sanitizeFileName(fileName) {
        // Hapus karakter berbahaya dan HTML tags
        let sanitized = fileName.replace(/[<>:"\/\\|?*\x00-\x1f]/g, '');
        // Decode HTML entities jika ada
        const textarea = document.createElement('textarea');
        textarea.innerHTML = sanitized;
        sanitized = textarea.value;
        // Trim whitespace
        return sanitized.trim();
    }

    // Mengembalikan UI upload ke kondisi awal
    window.resetFileSelection = function () {
        fileInput.value = "";
        dropArea.classList.remove("has-file");
        dropArea.innerHTML = initialDropAreaHTML;
        submitButtonWrapper.style.display = "none";
        judulSuratInput.value = "";
    };
});
