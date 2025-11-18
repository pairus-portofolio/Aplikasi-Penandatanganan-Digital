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

    // Mengunci input agar hanya menerima .docx
    fileInput.setAttribute("accept", ".docx");

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
        // Memastikan file adalah .docx
        if (!file.name.toLowerCase().endsWith(".docx")) {
            alert("Hanya file .docx yang diperbolehkan.");
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

        // Mengisi judul otomatis berdasarkan nama file
        judulSuratInput.value = file.name;

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
        const userName = this.options[this.selectedIndex].text;

        if (userId) {
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
            };

            listItem.appendChild(removeButton);
            alurList.appendChild(listItem);

            updateAlurInput();
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

    // Mengembalikan UI upload ke kondisi awal
    window.resetFileSelection = function () {
        fileInput.value = "";
        dropArea.classList.remove("has-file");
        dropArea.innerHTML = initialDropAreaHTML;
        submitButtonWrapper.style.display = "none";
        judulSuratInput.value = "";
    };
});
