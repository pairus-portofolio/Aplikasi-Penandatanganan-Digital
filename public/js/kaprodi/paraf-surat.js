document.addEventListener('DOMContentLoaded', function() {
            
    let selectedParaf = null; 
    const page   = document.getElementById('previewPage');
    const zoomIn = document.getElementById('zoomInBtn');
    const zoomOut = document.getElementById('zoomOutBtn');
    let currentScale = 1; 

    if (page && zoomIn && zoomOut) {
        function applyZoom() { page.style.transform = 'scale(' + currentScale + ')'; }
        zoomIn.addEventListener('click', () => { currentScale = Math.min(currentScale + 0.1, 2); applyZoom(); });
        zoomOut.addEventListener('click', () => { currentScale = Math.max(currentScale - 0.1, 0.5); applyZoom(); });
    }

    // --- KODE POPUP KIRIM NOTIFIKASI ---
    const kirimBtn = document.getElementById('kirimNotifikasiBtn'); 
    const kirimModal = document.getElementById('parafNotifPopup'); 
    const batalKirimBtn = document.getElementById('batalKirim'); 
    const konfirmasiKirimBtn = document.getElementById('konfirmasiKirim');

    if (kirimBtn && kirimModal && batalKirimBtn) {
        kirimBtn.addEventListener('click', () => kirimModal.classList.add('show'));
        batalKirimBtn.addEventListener('click', () => kirimModal.classList.remove('show'));
        kirimModal.addEventListener('click', (e) => { if (e.target === kirimModal) kirimModal.classList.remove('show'); });
        if (konfirmasiKirimBtn) {
            konfirmasiKirimBtn.addEventListener('click', () => kirimModal.classList.remove('show'));
        }
    }
    
    // --- KODE UPLOAD/GANTI/HAPUS PARAF ---
    const parafBox = document.getElementById('parafBox');
    const parafImage = document.getElementById('parafImage');
    const fileInput = document.getElementById('parafImageUpload');
    const gantiBtn = document.getElementById('parafGantiBtn');
    const hapusBtn = document.getElementById('parafHapusBtn');

    if (parafBox && parafImage && fileInput && gantiBtn && hapusBtn) {
        const triggerUpload = () => fileInput.click();
        parafBox.addEventListener('click', triggerUpload);
        gantiBtn.addEventListener('click', (e) => { e.stopPropagation(); triggerUpload(); });
        hapusBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            parafImage.src = '';
            parafBox.classList.remove('has-image');
        });
        fileInput.addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = (ev) => { parafImage.src = ev.target.result; parafBox.classList.add('has-image'); }
                reader.readAsDataURL(e.target.files[0]);
            }
        });
    }
    
    // --- KODE DRAG AND DROP ---
    if (parafImage) {
        parafImage.addEventListener('dragstart', function(e) {
            if (parafImage.src && parafBox.classList.contains('has-image')) {
                e.dataTransfer.setData('text/plain', parafImage.id);
                e.dataTransfer.effectAllowed = 'copy';
            } else { e.preventDefault(); }
        });
    }

    const dropZone = document.getElementById('previewPage');
    if (dropZone) {
        dropZone.addEventListener('dragover', (e) => { e.preventDefault(); e.dataTransfer.dropEffect = 'copy'; dropZone.style.borderColor = '#1e4ed8'; });
        dropZone.addEventListener('dragleave', () => { dropZone.style.borderColor = '#ccc'; });
        dropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            dropZone.style.borderColor = '#ccc';
            const data = e.dataTransfer.getData('text/plain');
            const originalParaf = document.getElementById(data);
            if (!originalParaf || !originalParaf.src) return;

            const newParaf = originalParaf.cloneNode(true);
            newParaf.id = 'paraf-dropped-' + Date.now();
            newParaf.classList.add('paraf-dropped');
            
            const dropRect = dropZone.getBoundingClientRect();
            const x = (e.clientX - dropRect.left) / currentScale;
            const y = (e.clientY - dropRect.top) / currentScale;

            newParaf.style.position = 'absolute';
            newParaf.style.left = `${x}px`;
            newParaf.style.top = `${y}px`;
            newParaf.style.display = 'block';
            newParaf.style.width = '150px';
            newParaf.style.height = 'auto';

            dropZone.appendChild(newParaf);
            makeElementMovable(newParaf);
            makeElementSelectable(newParaf);
        });
    }

    // --- FUNGSI GESER & HAPUS ---
    function makeElementMovable(element) {
        element.addEventListener('mousedown', function(e) {
            e.preventDefault(); e.stopPropagation();
            let initialX = e.clientX, initialY = e.clientY;
            let initialLeft = element.offsetLeft, initialTop = element.offsetTop;
            function onMouseMove(moveEvent) {
                let dx = (moveEvent.clientX - initialX) / currentScale;
                let dy = (moveEvent.clientY - initialY) / currentScale;
                element.style.left = `${initialLeft + dx}px`;
                element.style.top = `${initialTop + dy}px`;
            }
            function onMouseUp() {
                document.removeEventListener('mousemove', onMouseMove);
                document.removeEventListener('mouseup', onMouseUp);
            }
            document.addEventListener('mousemove', onMouseMove);
            document.addEventListener('mouseup', onMouseUp);
        });
    }

    function makeElementSelectable(element) {
        element.addEventListener('click', function(e) {
            e.stopPropagation();
            if (selectedParaf) selectedParaf.classList.remove('selected');
            selectedParaf = element;
            element.classList.add('selected');
        });
    }

    dropZone.addEventListener('click', function() {
        if (selectedParaf) {
            selectedParaf.classList.remove('selected');
            selectedParaf = null;
        }
    });

    document.addEventListener('keydown', function(e) {
        if ((e.key === 'Delete' || e.key === 'Backspace') && selectedParaf) {
            selectedParaf.remove();
            selectedParaf = null;
        }
    });

});