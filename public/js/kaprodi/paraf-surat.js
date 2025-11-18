// Menjalankan script setelah seluruh DOM siap
document.addEventListener("DOMContentLoaded", function () {
    // Variabel dasar untuk elemen paraf & area drop
    let selectedParaf = null;
    const dropZone = document.getElementById("previewPage");

    // Mengambil nilai zoom/scale saat ini dari dokumen
    function getCurrentScale() {
        const style = window.getComputedStyle(dropZone);
        const matrix = new WebKitCSSMatrix(style.transform);
        return matrix.a;
    }

    // Mengambil elemen terkait upload & preview paraf
    const parafBox = document.getElementById("parafBox");
    const parafImage = document.getElementById("parafImage");
    const fileInput = document.getElementById("parafImageUpload");
    const gantiBtn = document.getElementById("parafGantiBtn");
    const hapusBtn = document.getElementById("parafHapusBtn");

    // Logika upload gambar paraf
    if (parafBox && parafImage && fileInput) {
        // Trigger input file
        const triggerUpload = () => fileInput.click();

        // Klik box untuk upload pertama kali
        parafBox.addEventListener("click", () => {
            if (!parafBox.classList.contains("has-image")) triggerUpload();
        });

        // Mengganti gambar paraf
        if (gantiBtn) {
            gantiBtn.addEventListener("click", (e) => {
                e.stopPropagation();
                triggerUpload();
            });
        }

        // Menghapus gambar paraf
        if (hapusBtn) {
            hapusBtn.addEventListener("click", (e) => {
                e.stopPropagation();
                parafImage.src = "";
                parafImage.style.display = "none";
                fileInput.value = "";
                parafBox.classList.remove("has-image");
            });
        }

        // Menampilkan gambar yang dipilih ke dalam preview
        fileInput.addEventListener("change", (e) => {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = (ev) => {
                    parafImage.src = ev.target.result;
                    parafImage.style.display = "block";
                    parafBox.classList.add("has-image");
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        });

        // Mengaktifkan fitur drag untuk gambar paraf asal
        parafImage.addEventListener("dragstart", (e) => {
            if (parafImage.src && parafBox.classList.contains("has-image")) {
                e.dataTransfer.setData("text/plain", "parafImage");
                e.dataTransfer.effectAllowed = "copy";
            } else {
                e.preventDefault();
            }
        });
    }

    // Aktivasi area drop tanda tangan
    if (dropZone) {
        // Memberi efek saat item di-drag di atas dokumen
        dropZone.addEventListener("dragover", (e) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = "copy";
            dropZone.style.borderColor = "#1e4ed8";
        });

        // Reset border ketika drag keluar area
        dropZone.addEventListener("dragleave", () => {
            dropZone.style.borderColor = "#ccc";
        });

        // Menempatkan paraf baru ke area dokumen
        dropZone.addEventListener("drop", (e) => {
            e.preventDefault();
            dropZone.style.borderColor = "#ccc";

            const data = e.dataTransfer.getData("text/plain");
            if (data !== "parafImage") return;

            const originalParaf = document.getElementById("parafImage");
            if (!originalParaf || !originalParaf.src) return;

            const newParaf = originalParaf.cloneNode(true);
            newParaf.id = "paraf-dropped-" + Date.now();
            newParaf.classList.add("paraf-dropped");
            newParaf.classList.remove("paraf-image-preview");

            const currentScale = getCurrentScale();
            const dropRect = dropZone.getBoundingClientRect();
            const x = (e.clientX - dropRect.left) / currentScale;
            const y = (e.clientY - dropRect.top) / currentScale;

            newParaf.style.position = "absolute";
            newParaf.style.left = `${x}px`;
            newParaf.style.top = `${y}px`;
            newParaf.style.display = "block";
            newParaf.style.width = "150px";
            newParaf.style.zIndex = "100";
            newParaf.style.cursor = "grab";

            dropZone.appendChild(newParaf);
            makeElementMovable(newParaf);
            makeElementSelectable(newParaf);
        });

        // Reset seleksi paraf jika klik area kosong
        dropZone.addEventListener("click", () => {
            if (selectedParaf) {
                selectedParaf.classList.remove("selected");
                selectedParaf = null;
            }
        });
    }

    // Mengaktifkan fitur drag untuk elemen hasil drop
    function makeElementMovable(element) {
        let isDragging = false;

        element.addEventListener("mousedown", (e) => {
            e.preventDefault();
            e.stopPropagation();

            isDragging = true;
            selectElement(element);

            const currentScale = getCurrentScale();
            let startX = e.clientX;
            let startY = e.clientY;
            let startLeft = element.offsetLeft;
            let startTop = element.offsetTop;

            function onMouseMove(moveEvent) {
                if (!isDragging) return;

                let dx = (moveEvent.clientX - startX) / currentScale;
                let dy = (moveEvent.clientY - startY) / currentScale;

                element.style.left = `${startLeft + dx}px`;
                element.style.top = `${startTop + dy}px`;
            }

            function onMouseUp() {
                isDragging = false;
                document.removeEventListener("mousemove", onMouseMove);
                document.removeEventListener("mouseup", onMouseUp);
            }

            document.addEventListener("mousemove", onMouseMove);
            document.addEventListener("mouseup", onMouseUp);
        });
    }

    // Membuat elemen bisa dipilih
    function makeElementSelectable(element) {
        element.addEventListener("click", (e) => {
            e.stopPropagation();
            selectElement(element);
        });
    }

    // Menentukan elemen paraf mana yang sedang aktif
    function selectElement(element) {
        if (selectedParaf) selectedParaf.classList.remove("selected");
        selectedParaf = element;
        element.classList.add("selected");
    }

    // Menghapus paraf yang sedang dipilih dengan tombol Delete / Backspace
    document.addEventListener("keydown", (e) => {
        if ((e.key === "Delete" || e.key === "Backspace") && selectedParaf) {
            selectedParaf.remove();
            selectedParaf = null;
        }
    });
});
