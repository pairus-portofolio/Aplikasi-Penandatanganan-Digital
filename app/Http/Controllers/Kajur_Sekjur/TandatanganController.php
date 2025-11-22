<?php

namespace App\Http\Controllers\Kajur_Sekjur;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Document;
use App\Http\Controllers\Dashboard\TableController;
use App\Services\WorkflowService;
use App\Enums\DocumentStatusEnum;

class TandatanganController extends Controller
{
    protected $workflowService;

    public function __construct(WorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
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

        if (!$this->workflowService->checkAccess($document->id)) {
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
        if (!$this->workflowService->checkAccess($documentId)) {
            return back()->withErrors('Bukan giliran Anda untuk menandatangani dokumen ini.');
        }

        try {
            // 1. Update Step Status
            $this->workflowService->completeStep($documentId, DocumentStatusEnum::DITANDATANGANI);

            // 2. Update Document Status
            $this->workflowService->updateDocumentStatus($documentId);

            return redirect()
                ->route('kajur.tandatangan.index')
                ->with('success', 'Dokumen berhasil ditandatangani.');

        } catch (\Exception $e) {
            return back()->withErrors('Gagal memproses: ' . $e->getMessage());
        }
    }
}
