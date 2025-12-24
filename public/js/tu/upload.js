document.addEventListener("DOMContentLoaded", function () {
    const dropArea = document.getElementById("drop-area");
    const fileInput = document.getElementById("file-input");
    const submitButtonWrapper = document.getElementById("submit-button-wrapper");
    const judulSuratInput = document.getElementById("judul_surat");

    const initialDropAreaHTML = dropArea.innerHTML;
    fileInput.setAttribute("accept", ".pdf");

    dropArea.addEventListener("click", () => {
        fileInput.value = "";
        fileInput.click();
    });

    dropArea.addEventListener("dragover", (e) => {
        e.preventDefault();
        dropArea.classList.add("drag-over");
    });

    dropArea.addEventListener("dragleave", () => {
        dropArea.classList.remove("drag-over");
    });

    dropArea.addEventListener("drop", (e) => {
        e.preventDefault();
        dropArea.classList.remove("drag-over");
        const files = e.dataTransfer.files;

        if (files.length > 0) {
            try {
                fileInput.files = files;
            } catch (err) {
                console.error("Error file input:", err);
                return;
            }
            handleFile(files[0]);
        }
    });

    fileInput.addEventListener("change", function () {
        if (this.files && this.files.length > 0) {
            handleFile(this.files[0]);
        } else {
            resetFileSelection();
        }
    });

    function handleFile(file) {
        if (!file.name.toLowerCase().endsWith(".pdf")) {
            alert("Hanya file .pdf yang diperbolehkan.");
            resetFileSelection();
            return;
        }

        dropArea.classList.add("has-file");
        dropArea.innerHTML = `
            <div class="selected-file">
                <span class="selected-file-icon">📄</span>
                <span class="selected-file-name" title="${file.name}">${file.name}</span>
            </div>
        `;

        const sanitizedFileName = sanitizeFileName(file.name);
        judulSuratInput.value = sanitizedFileName;

        submitButtonWrapper.style.display = "block";
    }

    function sanitizeFileName(fileName) {
        let sanitized = fileName.replace(/[<>:"\/\\|?*\x00-\x1f]/g, '');
        const textarea = document.createElement('textarea');
        textarea.innerHTML = sanitized;
        sanitized = textarea.value;
        return sanitized.trim();
    }

    // --- WORKFLOW LOGIC ---

    const parafSelect = document.getElementById("parafSelect");
    const ttdSelect = document.getElementById("ttdSelect");
    const parafContainer = document.getElementById("selectedParafContainer");
    const ttdContainer = document.getElementById("selectedTtdContainer");
    const alurInput = document.getElementById("alurInput");
    
    let parafUsers = [];
    let ttdUser = null;

    function getOptionData(option) {
        return {
            id: option.value,
            name: option.dataset.name || option.text, 
            role: option.dataset.role
        };
    }

    function renderListItem(user, indexTotal, isParaf) {
        const listItem = document.createElement("li");
        listItem.classList.add("alur-item");
        
        const stepLabel = document.createElement("span");
        stepLabel.classList.add("alur-step-label");
        
        // PERBAIKAN: Hapus alias role (${user.role}) agar nama langsung saja
        stepLabel.innerHTML = `<span class="alur-step-number">${indexTotal + 1}.</span> ${user.name}`;
        
        const removeButton = document.createElement("button");
        removeButton.classList.add("alur-remove-btn");
        removeButton.textContent = "Hapus";

        removeButton.onclick = function () {
            if (!isParaf) {
                ttdUser = null;
                if (ttdSelect) ttdSelect.value = ttdSelect.querySelector('option[disabled][hidden]').value;
            } else {
                parafUsers = parafUsers.filter(u => u.id !== user.id);
            }
            if (parafSelect && isParaf) parafSelect.value = parafSelect.querySelector('option[disabled][hidden]').value;
            updateAlur();
        };
        
        listItem.appendChild(stepLabel);
        listItem.appendChild(removeButton); 
        return listItem;
    }

    function renderAllLists() {
        parafContainer.innerHTML = '';
        ttdContainer.innerHTML = '';
        const alurUserIds = [];
        let totalIndex = 0; 

        // A. Render Paraf
        parafUsers.forEach((user) => {
            const item = renderListItem(user, totalIndex, true);
            parafContainer.appendChild(item);
            alurUserIds.push(user.id);
            totalIndex++;
        });

        // B. Render TTD
        if (ttdUser) {
            const item = renderListItem(ttdUser, totalIndex, false);
            ttdContainer.appendChild(item);
            alurUserIds.push(ttdUser.id);
            totalIndex++;
        }

        // C. Update hidden input
        alurInput.value = alurUserIds.join(",");
        
        if (!ttdUser) {
            alurInput.value = ""; 
            alurInput.setAttribute('required', 'required');
            submitButtonWrapper.style.display = "none";
        } else {
             alurInput.removeAttribute('required');
             if (judulSuratInput.value) {
                 submitButtonWrapper.style.display = "block";
             }
        }
    }

    if (parafSelect) {
        parafSelect.addEventListener("change", function () {
            const selectedOption = this.options[this.selectedIndex];
            
            if (selectedOption.value) {
                const userData = getOptionData(selectedOption);

                // Tetap pertahankan logika batas maksimal di sistem agar tidak error
                if (parafUsers.length >= 2) {
                      Swal.fire('Info', 'Maksimal hanya 2 Pemaraf yang diperbolehkan.', 'info');
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

    if (ttdSelect) {
        ttdSelect.addEventListener("change", function() {
            const selectedValue = this.value;

            if (selectedValue) {
                 const selectedOption = this.options[this.selectedIndex];
                 const userData = getOptionData(selectedOption);
                 
                if (parafUsers.some(u => u.id === selectedValue)) {
                    Swal.fire('Error', 'Penandatangan tidak boleh merangkap sebagai Pemaraf.', 'error');
                    this.value = ttdSelect.querySelector('option[disabled][hidden]').value;
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
    
    function updateAlur() {
        renderAllLists();
    }

    window.resetFileSelection = function () {
        fileInput.value = "";
        dropArea.classList.remove("has-file");
        dropArea.innerHTML = initialDropAreaHTML;
        submitButtonWrapper.style.display = "none";
        judulSuratInput.value = "";
        
        parafUsers = [];
        ttdUser = null;
        if (parafSelect) parafSelect.value = parafSelect.querySelector('option[disabled][hidden]').value;
        if (ttdSelect) ttdSelect.value = ttdSelect.querySelector('option[disabled][hidden]').value;
        updateAlur();
    };

    updateAlur();
});