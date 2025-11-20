<?php

namespace App\Http\Controllers\Kajur_Sekjur;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Document;
use App\Models\WorkflowStep;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Dashboard\TableController;

class TandatanganController extends Controller
{
    // =========================
    // CEK AKSES WORKFLOW
    // =========================
    private function checkWorkflowAccess($documentId)
    {
        // Step aktif = step dengan urutan terkecil yg belum selesai
        $activeStep = WorkflowStep::where('document_id', $documentId)
            ->whereIn('status', ['Ditinjau']) 
            ->orderBy('urutan')
            ->first();

        if (!$activeStep) {
            return false;
        }

        return $activeStep->user_id == Auth::id();
    }

    // ======================================================
    // 1. LIST TABEL
    // ======================================================
    public function index()
    {
        $daftarSurat = TableController::getData();
        return view('kajur_sekjur.tandatangan.index', compact('daftarSurat'));
    }

    // ======================================================
    // 2. HALAMAN DETAIL TTD
    // ======================================================
    public function show($id)
    {
        $document = Document::findOrFail($id);

        if (!$this->checkWorkflowAccess($document->id)) {
            return redirect()->route('kajur.tandatangan.index')
                ->withErrors('Belum giliran Anda untuk menandatangani dokumen ini.');
        }

        return view('kajur_sekjur.tandatangan-surat', compact('document'));
    }

    // ======================================================
    // 3. SUBMIT TANDA TANGAN
    // ======================================================
    public function submit(Request $request, $documentId)
    {
        $document = Document::findOrFail($documentId);

        // Step aktif tanda tangan
        $activeStep = WorkflowStep::where('document_id', $documentId)
            ->where('status', 'Ditinjau')
            ->orderBy('urutan')
            ->first();

        if (!$activeStep || $activeStep->user_id != Auth::id()) {
            return back()->withErrors('Bukan giliran Anda untuk menandatangani dokumen ini.');
        }

        // 1. UPDATE STEP
        $activeStep->status = 'Ditandatangani';
        $activeStep->tanggal_aksi = now();
        $activeStep->save();

        // 2. CEK APAKAH MASIH ADA STEP TTD LAIN YG BELUM SELESAI
        $nextTtd = WorkflowStep::where('document_id', $documentId)
            ->where('status', 'Ditinjau') // hanya step tanda tangan berikutnya
            ->whereHas('user.role', function ($q) {
                $q->whereIn('nama_role', ['Kajur', 'Sekjur']);
            })
            ->count();

        if ($nextTtd > 0) {
            // Masih ada Kajur/Sekjur lain → dokumen tetap "Diparaf"
            $document->status = 'Diparaf';
        } else {
            // Semua tanda tangan selesai → FINAL
            $document->status = 'Ditandatangani';
        }

        $document->save();

        return redirect()
            ->route('kajur.tandatangan.index')
            ->with('success', 'Dokumen berhasil ditandatangani.');
    }
}
