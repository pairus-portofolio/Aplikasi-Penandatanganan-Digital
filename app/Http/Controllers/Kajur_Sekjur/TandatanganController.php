<?php

namespace App\Http\Controllers\Kajur_Sekjur;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Document;
use App\Models\WorkflowStep;   // ← Tambahkan
use Illuminate\Support\Facades\Auth; // ← Tambahkan
use App\Http\Controllers\Dashboard\TableController;

class TandatanganController extends Controller
{
    // Function untuk mengecek apakah user mendapat giliran menandatangani
    private function checkWorkflowAccess($documentId)
    {
        // Step aktif = step dengan status Ditinjau
        $activeStep = WorkflowStep::where('document_id', $documentId)
            ->where('status', 'Ditinjau')
            ->orderBy('urutan')
            ->first();

        if (!$activeStep) {
            return false;
        }

        return $activeStep->user_id == Auth::id();
    }


    // 1. Halaman tabel daftar surat
    public function index()
    {
        $daftarSurat = TableController::getData();
        return view('kajur_sekjur.tandatangan.index', compact('daftarSurat'));
    }

    // 2. Halaman detail tanda tangan (tampilan dokumen)
    public function show($id)
    {
        $document = Document::findOrFail($id);

        // Cek giliran workflow
        if (!$this->checkWorkflowAccess($document->id)) {
            return redirect()->route('kajur.tandatangan.index')
                ->withErrors('Belum giliran Anda untuk menandatangani dokumen ini.');
        }

        // Jika sudah giliran → tampilkan halaman tanda tangan
        return view('kajur_sekjur.tandatangan-surat', compact('document'));
    }

     public function submit(Request $request, $documentId)
    {
        $document = Document::findOrFail($documentId);

        // Step aktif = yang statusnya Ditinjau dan urutan terkecil
        $activeStep = WorkflowStep::where('document_id', $documentId)
            ->where('status', 'Ditinjau')
            ->orderBy('urutan')
            ->first();

        // Validasi giliran user
        if (!$activeStep || $activeStep->user_id != Auth::id()) {
            return back()->withErrors('Bukan giliran Anda untuk menandatangani dokumen ini.');
        }

        // ===============================
        // 1. Update step aktif → Ditandatangani
        // ===============================
        $activeStep->status = 'Ditandatangani';
        $activeStep->tanggal_aksi = now();
        $activeStep->save();

        // ===============================
        // 2. Cek apakah masih ada step lain yg statusnya Ditinjau
        // ===============================
        $nextStep = WorkflowStep::where('document_id', $documentId)
            ->where('status', 'Ditinjau')
            ->orderBy('urutan')
            ->first();

        if ($nextStep) {
            // Masih ada step (berarti ada kajur/sekjur berikutnya)
            $document->status = 'Diparaf'; 
        } else {
            // Semua step selesai → dokumen final
            $document->status = 'Ditandatangani';
        }

        $document->save();

        return redirect()->route('kajur.tandatangan.index')
            ->with('success', 'Dokumen berhasil ditandatangani.');
    }

}
