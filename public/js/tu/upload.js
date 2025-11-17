// public/js/tu/upload_surat.js

document.addEventListener("DOMContentLoaded", function () {
    // === Element utama ===
    const dropArea = document.getElementById("drop-area");
    const fileInput = document.getElementById("file-input");
    const submitButtonWrapper = document.getElementById(
        "submit-button-wrapper"
    );
    const judulSuratInput = document.getElementById("judul_surat");

    // Simpan HTML awal upload-box untuk kebutuhan reset
    const initialDropAreaHTML = dropArea.innerHTML;

    // Set agar hanya file docx diterima
    fileInput.setAttribute("accept", ".docx");

    // ======================================================
    // === Klik area upload untuk membuka file dialog     ===
    // ======================================================
    dropArea.addEventListener("click", () => {
        fileInput.value = "";
        fileInput.click();
    });

    // ======================================================
    // === Drag & Drop file ke area upload                 ===
    // ======================================================

    // Tambahkan efek saat file di-drag ke area upload
    dropArea.addEventListener("dragover", (e) => {
        e.preventDefault();
        dropArea.classList.add("drag-over");
    });

    // Hilangkan efek saat drag keluar area
    dropArea.addEventListener("dragleave", () => {
        dropArea.classList.remove("drag-over");
    });

    // Jika file di-drop, tangani file tersebut
    dropArea.addEventListener("drop", (e) => {
        e.preventDefault();
        dropArea.classList.remove("drag-over");
        const files = e.dataTransfer.files;

        if (files.length > 0) {
            try {
                fileInput.files = files;
            } catch (err) {
                // Beberapa browser mungkin tidak mengizinkan assignment langsung
                console.error("Tidak dapat meng-assign fileInput.files:", err);
            }
            handleFile(files[0]);
        }
    });

    // ======================================================
    // === Upload file menggunakan file dialog             ===
    // ======================================================
    fileInput.addEventListener("change", function () {
        if (this.files && this.files.length > 0) {
            handleFile(this.files[0]);
        } else {
            resetFileSelection();
        }
    });

    // ======================================================
    // === Fungsi menangani file yang diupload             ===
    // ======================================================
    function handleFile(file) {
        // Cek file valid (harus .docx)
        if (!file.name.toLowerCase().endsWith(".docx")) {
            alert("Hanya file .docx yang diperbolehkan.");
            resetFileSelection();
            return;
        }

        // Ubah area upload menjadi tampilan file terpilih
        dropArea.classList.add("has-file");
        dropArea.innerHTML = `
            <div class="selected-file">
                <span class="selected-file-icon">📄</span>
                <span class="selected-file-label"></span>
                <span class="selected-file-name" title="${file.name}">${file.name}</span>
            </div>
        `;

        // Isi judul surat berdasarkan nama file
        judulSuratInput.value = file.name;

        // Tampilkan tombol submit
        submitButtonWrapper.style.display = "block";
    }

    // ======================================================
    // === Alur penandatangan surat                       ===
    // ======================================================
    const userSelect = document.getElementById("userSelect");
    const alurList = document.getElementById("alurList");
    const alurInput = document.getElementById("alurInput");

    // Tambahkan user ke daftar alur ketika dipilih
    userSelect.addEventListener("change", function () {
        const userId = this.value;
        const userName = this.options[this.selectedIndex].text;

        if (userId) {
            // Buat elemen list baru
            const listItem = document.createElement("li");
            listItem.classList.add("alur-item");
            listItem.textContent = userName;
            listItem.dataset.userId = userId;

            // Buat tombol hapus setiap alur
            const removeButton = document.createElement("button");
            removeButton.classList.add("remove-alur-btn");
            removeButton.textContent = "Hapus";

            // Event tombol hapus
            removeButton.onclick = function () {
                alurList.removeChild(listItem);
                updateAlurInput();
            };

            // Tempelkan tombol hapus
            listItem.appendChild(removeButton);
            alurList.appendChild(listItem);

            // Update input hidden
            updateAlurInput();

            // Reset dropdown
            this.value = "";
        }
    });

    // Simpan urutan ID penandatangan ke input hidden
    function updateAlurInput() {
        const alurUserIds = [];

        alurList.querySelectorAll("li").forEach((item) => {
            alurUserIds.push(item.dataset.userId);
        });

        alurInput.value = alurUserIds.join(",");
    }

    // ======================================================
    // === Reset file upload                              ===
    // ======================================================
    window.resetFileSelection = function () {
        fileInput.value = "";
        dropArea.classList.remove("has-file");
        dropArea.innerHTML = initialDropAreaHTML;
        submitButtonWrapper.style.display = "none";
        judulSuratInput.value = "";
    };
});
