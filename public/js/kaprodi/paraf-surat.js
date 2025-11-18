// public/js/kaprodi/paraf-surat.js

document.addEventListener('DOMContentLoaded', function() {
    
    // Variabel Global untuk Halaman Ini
    let selectedParaf = null; 
    const dropZone = document.getElementById('previewPage'); // Drop zone area

    // Untuk keperluan kalkulasi posisi drop (mengambil nilai scale dari style elemen)
    function getCurrentScale() {
        const style = window.getComputedStyle(dropZone);
        const matrix = new WebKitCSSMatrix(style.transform);
        return matrix.a; // Mengambil nilai scale X (biasanya sama dengan scale Y)
    }

    /* ==========================================
       1. UPLOAD & MANAJEMEN GAMBAR PARAF
       ========================================== */
    const parafBox = document.getElementById('parafBox');
    const parafImage = document.getElementById('parafImage');
    const fileInput = document.getElementById('parafImageUpload');
    const gantiBtn = document.getElementById('parafGantiBtn');
    const hapusBtn = document.getElementById('parafHapusBtn');

    if (parafBox && parafImage && fileInput) {
        const triggerUpload = () => fileInput.click();

        // Klik box untuk upload
        parafBox.addEventListener('click', () => {
            if (!parafBox.classList.contains('has-image')) triggerUpload();
        });

        // Tombol Ganti
        if(gantiBtn) {
            gantiBtn.addEventListener('click', (e) => { 
                e.stopPropagation(); 
                triggerUpload(); 
            });
        }

        // Tombol Hapus
        if(hapusBtn) {
            hapusBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                parafImage.src = '';
                parafImage.style.display = 'none';
                fileInput.value = ''; 
                parafBox.classList.remove('has-image');
            });
        }

        // Handle File Input Change
        fileInput.addEventListener('change', (e) => {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = (ev) => { 
                    parafImage.src = ev.target.result; 
                    parafImage.style.display = 'block'; 
                    parafBox.classList.add('has-image'); 
                }
                reader.readAsDataURL(e.target.files[0]);
            }
        });

        // Setup Drag Start dari Sidebar
        parafImage.addEventListener('dragstart', (e) => {
            if (parafImage.src && parafBox.classList.contains('has-image')) {
                e.dataTransfer.setData('text/plain', 'parafImage'); 
                e.dataTransfer.effectAllowed = 'copy';
            } else { 
                e.preventDefault(); 
            }
        });
    }

    /* ==========================================
       2. DROP ZONE & DRAGGABLE ELEMENTS
       ========================================== */
    if (dropZone) {
        dropZone.addEventListener('dragover', (e) => { 
            e.preventDefault(); 
            e.dataTransfer.dropEffect = 'copy'; 
            dropZone.style.borderColor = '#1e4ed8'; 
        });

        dropZone.addEventListener('dragleave', () => { 
            dropZone.style.borderColor = '#ccc'; 
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.style.borderColor = '#ccc';
            
            const data = e.dataTransfer.getData('text/plain');
            if (data !== 'parafImage') return;

            const originalParaf = document.getElementById('parafImage');
            if (!originalParaf || !originalParaf.src) return;

            // Clone Paraf
            const newParaf = originalParaf.cloneNode(true);
            newParaf.id = 'paraf-dropped-' + Date.now();
            newParaf.classList.add('paraf-dropped');
            newParaf.classList.remove('paraf-image-preview'); 
            
            // Kalkulasi Posisi
            const currentScale = getCurrentScale();
            const dropRect = dropZone.getBoundingClientRect();
            const x = (e.clientX - dropRect.left) / currentScale;
            const y = (e.clientY - dropRect.top) / currentScale;

            // Set Style
            newParaf.style.position = 'absolute';
            newParaf.style.left = `${x}px`;
            newParaf.style.top = `${y}px`;
            newParaf.style.display = 'block';
            newParaf.style.width = '150px';
            newParaf.style.height = 'auto';
            newParaf.style.zIndex = '100';
            newParaf.style.cursor = 'grab';

            dropZone.appendChild(newParaf);
            makeElementMovable(newParaf);
            makeElementSelectable(newParaf);
        });

        // Klik Dropzone untuk Deselect
        dropZone.addEventListener('click', () => {
            if (selectedParaf) {
                selectedParaf.classList.remove('selected');
                selectedParaf = null;
            }
        });
    }

    /* ==========================================
       3. UTILITY FUNCTIONS (Move & Select)
       ========================================== */
    function makeElementMovable(element) {
        let isDragging = false;
        
        element.addEventListener('mousedown', (e) => {
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
                document.removeEventListener('mousemove', onMouseMove);
                document.removeEventListener('mouseup', onMouseUp);
            }

            document.addEventListener('mousemove', onMouseMove);
            document.addEventListener('mouseup', onMouseUp);
        });
    }

    function makeElementSelectable(element) {
        element.addEventListener('click', (e) => {
            e.stopPropagation();
            selectElement(element);
        });
    }

    function selectElement(element) {
        if (selectedParaf) selectedParaf.classList.remove('selected');
        selectedParaf = element;
        element.classList.add('selected');
    }

    // Hapus dengan tombol Delete
    document.addEventListener('keydown', (e) => {
        if ((e.key === 'Delete' || e.key === 'Backspace') && selectedParaf) {
            selectedParaf.remove();
            selectedParaf = null;
        }
    });
});