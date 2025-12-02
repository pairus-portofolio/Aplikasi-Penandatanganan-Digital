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
        // Reset file input agar event 'change' ter-trigger meskipun file yang sama dipilih
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
                return;
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

    // --- NEW WORKFLOW LOGIC ---

    const parafSelect = document.getElementById("parafSelect");
    const ttdSelect = document.getElementById("ttdSelect");
    const parafContainer = document.getElementById("selectedParafContainer");
    const ttdContainer = document.getElementById("selectedTtdContainer");
    const alurInput = document.getElementById("alurInput");
    const isRevisionMode = document.getElementById("alurInput").hasAttribute('data-existing-alur');

    // Data internal: Array untuk Pemaraf (Kaprodi)
    let parafUsers = []; // Stores { id: string, name: string, role: string }
    // Data internal: TTD user
    let ttdUser = null; // Stores { id: string, name: string, role: string }

    // Fungsi bantuan untuk mendapatkan data opsi dari select
    function getOptionData(option) {
        return {
            id: option.value,
            // Untuk revisi, kita pakai text jika data-name tidak ada
            name: option.dataset.name || option.text, 
            role: option.dataset.role
        };
    }

    // Fungsi untuk merender list item di containernya
    function renderListItem(user, indexTotal, isParaf) {
        const listItem = document.createElement("li");
        listItem.classList.add("alur-item");
        
        // Penomoran urut (FIX SYNTAX ERROR DI BARIS INI)
        const stepLabel = document.createElement("span");
        stepLabel.classList.add("alur-step-label");
        stepLabel.innerHTML = `<span class="alur-step-number">${indexTotal + 1}.</span> ${user.name} (${user.role})`;
        
        // Tombol Hapus (Teks "Hapus")
        const removeButton = document.createElement("button");
        removeButton.classList.add("alur-remove-btn");
        removeButton.textContent = "Hapus";

        removeButton.onclick = function () {
            // Logika Hapus: TTD atau Paraf
            if (!isParaf) {
                ttdUser = null;
                // Pastikan select TTD kembali ke placeholder
                if (ttdSelect) ttdSelect.value = ttdSelect.querySelector('option[disabled][hidden]').value;
            } else {
                parafUsers = parafUsers.filter(u => u.id !== user.id);
            }
            // Reset select Kaprodi ke placeholder
            if (parafSelect && isParaf) parafSelect.value = parafSelect.querySelector('option[disabled][hidden]').value;
            updateAlur(); // Re-render dan update hidden input
        };
        
        // Susunan elemen: Label + Tombol Hapus
        listItem.appendChild(stepLabel);
        listItem.appendChild(removeButton); 
        return listItem;
    }

    // Fungsi untuk merender kedua list dan update hidden input
    function renderAllLists() {
        parafContainer.innerHTML = '';
        ttdContainer.innerHTML = '';
        const alurUserIds = [];
        let totalIndex = 0; // Index global untuk penomoran urut

        // A. Render Paraf Users
        parafUsers.forEach((user) => {
            const item = renderListItem(user, totalIndex, true);
            parafContainer.appendChild(item);
            alurUserIds.push(user.id);
            totalIndex++;
        });

        // B. Render TTD User
        if (ttdUser) {
            const item = renderListItem(ttdUser, totalIndex, false);
            ttdContainer.appendChild(item);
            alurUserIds.push(ttdUser.id);
            totalIndex++;
        }

        // C. Update hidden input
        alurInput.value = alurUserIds.join(",");
        
        // D. Tentukan status required dan tampilkan tombol submit
        if (!ttdUser) {
            alurInput.value = ""; 
            alurInput.setAttribute('required', 'required');
            submitButtonWrapper.style.display = "none";
        } else {
             alurInput.removeAttribute('required');
             // Hanya tampilkan tombol submit jika ada file upload (judul_surat terisi)
             if (judulSuratInput.value) {
                 submitButtonWrapper.style.display = "block";
             }
        }
    }


    // 1. TAMBAH PEMARAF (Kaprodi) saat dropdown berubah
    if (parafSelect) {
        parafSelect.addEventListener("change", function () {
            const selectedOption = this.options[this.selectedIndex];
            
            if (selectedOption.value) {
                const userData = getOptionData(selectedOption);

                if (parafUsers.length >= 2) {
                     Swal.fire('Info', 'Maksimal hanya 2 Pemaraf (Kaprodi) yang diperbolehkan.', 'info');
                }
                else if (ttdUser && ttdUser.id === userData.id) {
                     Swal.fire('Error', 'Pemaraf tidak boleh merangkap sebagai Penandatangan.', 'error');
                }
                else if (!parafUsers.some(u => u.id === userData.id)) {
                    parafUsers.push(userData);
                }
                
                this.value = parafSelect.querySelector('option[disabled][hidden]').value;
                updateAlur();
            }
        });
    }

    // 2. TTD user berubah (hanya 1)
    if (ttdSelect) {
        ttdSelect.addEventListener("change", function() {
            const selectedValue = this.value;

            if (selectedValue) {
                 const selectedOption = this.options[this.selectedIndex];
                 const userData = getOptionData(selectedOption);
                 
                 // Cek duplikasi dengan Paraf
                if (parafUsers.some(u => u.id === selectedValue)) {
                    Swal.fire('Error', 'Penandatangan tidak boleh merangkap sebagai Pemaraf. Silakan hapus Pemaraf yang sama terlebih dahulu.', 'error');
                    this.value = ttdSelect.querySelector('option[disabled][hidden]').value; // Reset TTD selection
                    ttdUser = null;
                } else {
                    ttdUser = userData;
                }
            } else {
                ttdUser = null;
            }
            updateAlur();
        });
    }
    
    // Alias untuk updateAlur agar mudah dipanggil
    function updateAlur() {
        renderAllLists();
    }

    // Mengembalikan UI upload ke kondisi awal
    window.resetFileSelection = function () {
        fileInput.value = "";
        dropArea.classList.remove("has-file");
        dropArea.innerHTML = initialDropAreaHTML;
        submitButtonWrapper.style.display = "none";
        judulSuratInput.value = "";
        
        // Reset alur
        parafUsers = [];
        ttdUser = null;
        if (parafSelect) parafSelect.value = parafSelect.querySelector('option[disabled][hidden]').value;
        if (ttdSelect) ttdSelect.value = ttdSelect.querySelector('option[disabled][hidden]').value;
        updateAlur();
    };

    // Panggil update alur saat DOM siap
    updateAlur();
});