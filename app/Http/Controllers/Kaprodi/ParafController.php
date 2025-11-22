<?php

namespace App\Http\Controllers\Kaprodi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Document;
use App\Models\WorkflowStep;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Dashboard\TableController;
use App\Services\WorkflowService;
use App\Services\PdfService;
use App\Enums\DocumentStatusEnum;

class ParafController extends Controller
{
    protected $workflowService;
    protected $pdfService;

    public function __construct(WorkflowService $workflowService, PdfService $pdfService)
    {
        $this->workflowService = $workflowService;
        $this->pdfService = $pdfService;
    }

    // =====================================================================
    // 1. Halaman Tabel Daftar Surat
    // =====================================================================
    public function index()
    {
        $daftarSurat = TableController::getData();
        return view('kaprodi.paraf.index', compact('daftarSurat'));
    }

    // =====================================================================
    // 2. Halaman Detail Dokumen (Tempat Paraf PDF)
    // =====================================================================
    public function show($id)
    {
        $document = Document::findOrFail($id);

        // Akses workflow via Service
        if (!$this->workflowService->checkAccess($document->id)) {
            return redirect()->route('kaprodi.paraf.index')
                ->withErrors('Belum giliran Anda untuk memparaf dokumen ini.');
        }

        return view('kaprodi.paraf-surat', compact('document'));
    }

    // =====================================================================
    // 3. Upload Paraf Image
    // =====================================================================
    public function uploadParaf(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:png,jpg,jpeg|max:2048',
        ]);

        if (!$request->hasFile('image') || !$request->file('image')->isValid()) {
            return response()->json([
                'status' => 'error',
                'message' => 'File tidak valid.'
            ], 400);
        }

        try {
            $user = Auth::user();

            // Hapus paraf lama
            if ($user->img_paraf_path && Storage::disk('public')->exists($user->img_paraf_path)) {
                Storage::disk('public')->delete($user->img_paraf_path);
            }

            // Simpan file baru
            $path = $request->file('image')->store('paraf', 'public');
            $user->update(['img_paraf_path' => $path]);

            return response()->json([
                'status' => 'success',
                'path' => asset('storage/' . $path),
                'message' => 'Paraf berhasil disimpan!'
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // =====================================================================
    // 4. Hapus Paraf Permanen
    // =====================================================================
    public function deleteParaf()
    {
        try {
            $user = Auth::user();
            if ($user->img_paraf_path && Storage::disk('public')->exists($user->img_paraf_path)) {
                Storage::disk('public')->delete($user->img_paraf_path);
            }
            $user->update(['img_paraf_path' => null]);

            return response()->json(['status' => 'success', 'message' => 'Paraf dihapus!']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // =====================================================================
    // 5. Submit Paraf (Workflow)
    // =====================================================================
    public function submit(Request $request, $documentId)
    {
        // 1. Validasi Akses
        if (!$this->workflowService->checkAccess($documentId)) {
            return back()->withErrors('Bukan giliran Anda untuk memparaf dokumen ini.');
        }

        try {
            // 2. Proses PDF (Tempel Paraf)
            $this->pdfService->applySignature($documentId, Auth::id());

            // 3. Update Step Status
            $this->workflowService->completeStep($documentId, DocumentStatusEnum::DIPARAF);

            // 4. Update Document Status
            $this->workflowService->updateDocumentStatus($documentId);

            return redirect()->route('kaprodi.paraf.index')
                ->with('success', 'Dokumen berhasil diparaf.');

        } catch (\Exception $e) {
            return back()->withErrors('Gagal memproses: ' . $e->getMessage());
        }
    }

    public function saveParaf(Request $request, $id)
    {
        $request->validate([
            'posisi_x' => 'required|numeric',
            'posisi_y' => 'required|numeric',
            'halaman'  => 'required|integer'
        ]);

        $workflowStep = WorkflowStep::where('document_id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if ($workflowStep) {
            $workflowStep->update([
                'posisi_x' => $request->posisi_x,
                'posisi_y' => $request->posisi_y,
                'halaman'  => $request->halaman,
                'tanggal_aksi' => now()
            ]);
            return response()->json(["status" => "success"]);
        }
        return response()->json(["status" => "error"], 404);
    }
}
