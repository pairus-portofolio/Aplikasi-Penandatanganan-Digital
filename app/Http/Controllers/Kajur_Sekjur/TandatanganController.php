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
        // Step aktif = step dengan urutan terkecil yang belum signed
        $activeStep = WorkflowStep::where('document_id', $documentId)
            ->where('status', '!=', 'signed')
            ->orderBy('urutan')
            ->first();

        // Jika workflow sudah selesai (tidak ada step aktif)
        if (!$activeStep) {
            return false;
        }

        // Cek apakah user login adalah user yang harus tanda tangan
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
        // Step aktif (urutan terkecil yang belum selesai)
        $activeStep = WorkflowStep::where('document_id', $documentId)
            ->whereIn('status', ['Ditinjau', 'Diparaf']) // status yang belum final
            ->orderBy('urutan')
            ->first();

        // Validasi benar atau tidak gilirannya
        if (!$activeStep || $activeStep->user_id != Auth::id()) {
            return back()->withErrors('Bukan giliran Anda untuk menandatangani dokumen ini.');
    }

        // 1. Update step ini menjadi Ditandatangani
        $activeStep->status = 'Ditandatangani';
        $activeStep->tanggal_aksi = now();
        $activeStep->save();

        // 2. CEK apakah masih ada step yang Belum Ditandatangani/Diparaf
        $sisaStep = WorkflowStep::where('document_id', $documentId)
            ->whereIn('status', ['Ditinjau', 'Diparaf'])
            ->count();

        $document = Document::find($documentId);

        if ($sisaStep == 0) {
            // 🔵 3. Semua step selesai → dokumen menjadi Ditandatangani
            $document->status = 'Ditandatangani';
        } else {
            // Masih ada step → ini berarti dokumen diparaf
            $document->status = 'Diparaf';
        }

        $document->save();

        return redirect()->route('kajur.tandatangan.index')
            ->with('success', 'Dokumen berhasil ditandatangani.');
    }   
}
