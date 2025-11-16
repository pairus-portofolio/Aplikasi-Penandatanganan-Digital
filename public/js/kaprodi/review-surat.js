document.addEventListener('DOMContentLoaded', function() {
            
    // --- KODE ZOOM ---
    const page   = document.getElementById('previewPage');
    const zoomIn = document.getElementById('zoomInBtn');
    const zoomOut = document.getElementById('zoomOutBtn');

    if (page && zoomIn && zoomOut) {
        let scale = 1;
        function applyZoom() {
            page.style.transform = 'scale(' + scale + ')';
        }
        zoomIn.addEventListener('click', function () {
            scale = Math.min(scale + 0.1, 2);
            applyZoom();
        });
        zoomOut.addEventListener('click', function () {
            scale = Math.max(scale - 0.1, 0.5);
            applyZoom();
        });
    } else {
        console.warn('Zoom buttons or preview page not found. Zoom functionality disabled.');
    }

    // --- KODE POPUP REVISI ---
    
    // PERBAIKAN DI SINI:
    // Kita cari ID yang benar 'mintaRevisiBtn' (tanpa 'a')
    const revisionBtn = document.getElementById('mintaRevisiBtn'); 
    
    const revisionModal = document.getElementById('revisiPopup'); 
    const cancelRevisionBtn = document.getElementById('batalBp'); 
    const sendRevisionBtn = document.getElementById('kirimBp'); 

    if (revisionBtn && revisionModal && cancelRevisionBtn) {
        // Tampilkan popup saat tombol revisi diklik
        revisionBtn.addEventListener('click', function() {
            revisionModal.classList.add('show');
        });

        // Sembunyikan popup saat tombol 'Batal' diklik
        cancelRevisionBtn.addEventListener('click', function() {
            revisionModal.classList.remove('show');
        });

        // Sembunyikan popup saat klik area gelap di luarnya
        revisionModal.addEventListener('click', function(event) {
            if (event.target === revisionModal) {
                revisionModal.classList.remove('show');
            }
        });

        if (sendRevisionBtn) {
            sendRevisionBtn.addEventListener('click', function() {
                // Popup langsung ditutup
                revisionModal.classList.remove('show'); 
            });
        }

    } else {
        // Error ini akan hilang jika file di bawah ini sudah benar
        console.error('Script tidak bisa menemukan elemen popup revisi. Pastikan ID sudah benar.');
    }

});