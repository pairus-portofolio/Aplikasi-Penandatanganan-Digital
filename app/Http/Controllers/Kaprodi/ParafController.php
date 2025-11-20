<?php

namespace App\Http\Controllers\Kaprodi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Document;
use App\Models\WorkflowStep;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Dashboard\TableController;

class ParafController extends Controller
{
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


    public function index()
    {
        $daftarSurat = TableController::getData();
        return view('kaprodi.paraf.index', compact('daftarSurat'));
    }

    // 2. Halaman Detail
    public function show($id)
    {
        $document = Document::findOrFail($id);
        // Kita kirim sebagai 'document' agar konsisten

        if (!$this->checkWorkflowAccess($document->id)) {
            return redirect()->route('kaprodi.paraf.index')
                ->withErrors('Belum giliran Anda untuk memparaf dokumen ini.');
        }
        return view('kaprodi.paraf-surat', compact('document'));
    }

    public function submit(Request $request, $documentId)
    {
        $activeStep = WorkflowStep::where('document_id', $documentId)
            ->where('status', 'Ditinjau')
            ->orderBy('urutan')
            ->first();

        if (!$activeStep || $activeStep->user_id != Auth::id()) {
            return back()->withErrors('Bukan giliran Anda untuk memparaf dokumen ini.');
        }

        // Update workflow step
        $activeStep->status = 'Diparaf';
        $activeStep->tanggal_aksi = now();
        $activeStep->save();

        $document = Document::find($documentId);

        // Cek apakah masih ada Kaprodi lain yang belum paraf
        $sisa = WorkflowStep::where('document_id', $documentId)
            ->where('status', 'Ditinjau')
            ->count();

        // Semua kaprodi sudah paraf → dokumen masuk tahap Diparaf
        // Kalau belum → tetap Ditinjau
        $document->status = ($sisa > 0) ? 'Ditinjau' : 'Diparaf';
        $document->save();

        return redirect()->route('kaprodi.paraf.index')
            ->with('success', 'Dokumen berhasil diparaf.');
    }
}
