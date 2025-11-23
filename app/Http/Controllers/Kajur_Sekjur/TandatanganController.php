<?php

namespace App\Http\Controllers\Kajur_Sekjur;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Document;
use App\Models\WorkflowStep;
use App\Http\Controllers\Dashboard\TableController;
use App\Services\WorkflowService;
use App\Enums\DocumentStatusEnum;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use setasign\Fpdi\Fpdi;

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

        $activeStep = WorkflowStep::where('document_id', $id)
            ->where('user_id', Auth::id())
            ->first();

        $savedSignature = null;
        if ($activeStep && $activeStep->posisi_x && $activeStep->posisi_y && $activeStep->halaman) {
            $savedSignature = [
                'x' => $activeStep->posisi_x,
                'y' => $activeStep->posisi_y,
                'page' => $activeStep->halaman
            ];
        }

        return view('kajur_sekjur.tandatangan-surat', compact('document', 'savedSignature'));
    }

    // ======================================================
    // 3. UPLOAD TANDA TANGAN (BARU)
    // ======================================================
    public function uploadTandatangan(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:png,jpg,jpeg|max:2048',
        ]);

        if (!$request->hasFile('image')) {
            return response()->json(['status' => 'error', 'message' => 'File tidak terbaca.'], 400);
        }

        try {
            $user = Auth::user();

            // Hapus file lama jika ada
            try {
                if ($user->img_ttd_path && Storage::disk('public')->exists($user->img_ttd_path)) {
                    Storage::disk('public')->delete($user->img_ttd_path);
                    Log::info('Old tandatangan deleted', ['user_id' => $user->id, 'path' => $user->img_ttd_path]);
                }
            } catch (\Exception $e) {
                Log::warning('Failed to delete old tandatangan', [
                    'user_id' => $user->id,
                    'path' => $user->img_ttd_path,
                    'error' => $e->getMessage()
                ]);
            }

            // Simpan file baru
            $path = $request->file('image')->store('tandatangan', 'public');
            $user->update(['img_ttd_path' => $path]);

            Log::info('Tandatangan uploaded successfully', ['user_id' => $user->id, 'path' => $path]);

            return response()->json([
                'status' => 'success',
                'path' => asset('storage/' . $path),
                'message' => 'Tanda tangan berhasil disimpan!'
            ]);

        } catch (\Exception $e) {
            Log::error('Tandatangan upload failed', ['user_id' => Auth::id(), 'error' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => 'Server Error: ' . $e->getMessage()], 500);
        }
    }

    // ======================================================
    // 4. HAPUS TANDA TANGAN (BARU)
    // ======================================================
    public function deleteTandatangan()
    {
        try {
            $user = Auth::user();

            if ($user->img_ttd_path && Storage::disk('public')->exists($user->img_ttd_path)) {
                Storage::disk('public')->delete($user->img_ttd_path);
                Log::info('Tandatangan deleted', ['user_id' => $user->id, 'path' => $user->img_ttd_path]);
            }

            $user->update(['img_ttd_path' => null]);

            return response()->json(['status' => 'success', 'message' => 'Tanda tangan dihapus!']);

        } catch (\Exception $e) {
            Log::error('Tandatangan delete failed', ['user_id' => Auth::id(), 'error' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => 'Gagal hapus: ' . $e->getMessage()], 500);
        }
    }

    // ======================================================
    // 5. SIMPAN POSISI TANDA TANGAN (BARU)
    // ======================================================
    public function saveTandatangan(Request $request, $id)
    {
        $request->validate([
            'posisi_x' => 'required|numeric',
            'posisi_y' => 'required|numeric',
            'halaman'  => 'required|integer|min:1'
        ]);

        $workflowStep = WorkflowStep::where('document_id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$workflowStep) {
            return response()->json(["status" => "error", "message" => "Workflow step not found"], 404);
        }

        try {
            $workflowStep->update([
                'posisi_x' => $request->posisi_x,
                'posisi_y' => $request->posisi_y,
                'halaman'  => $request->halaman,
                'tanggal_aksi' => now()
            ]);
        } catch (\Exception $e) {
            return response()->json(["status" => "error", "message" => "DB error: ".$e->getMessage()], 500);
        }

        return response()->json(["status" => "success"]);
    }

    // ======================================================
    // 6. SUBMIT TANDA TANGAN
    // ======================================================
    public function submit(Request $request, $documentId)
    {
        if (!$this->workflowService->checkAccess($documentId)) {
            return back()->withErrors('Bukan giliran Anda untuk menandatangani dokumen ini.');
        }

        // VALIDASI: Pastikan user sudah menempatkan TTD
        $activeStep = WorkflowStep::where('document_id', $documentId)
            ->where('user_id', Auth::id())
            ->first();

        if (is_null($activeStep->posisi_x) || is_null($activeStep->posisi_y) || !$activeStep->halaman) {
            return back()->withErrors('Anda belum menempatkan tanda tangan pada dokumen.');
        }

        try {
            // 1. Apply TTD ke PDF
            $this->applyTandatanganToPdf($documentId);

            // 2. Update Step Status
            $this->workflowService->completeStep($documentId, DocumentStatusEnum::DITANDATANGANI);

            // 3. Update Document Status
            $this->workflowService->updateDocumentStatus($documentId);

            Log::info('Document tandatangan submitted', ['document_id' => $documentId, 'user_id' => Auth::id()]);

            return redirect()
                ->route('kajur.tandatangan.index')
                ->with('success', 'Dokumen berhasil ditandatangani.');

        } catch (\Exception $e) {
            Log::error('Tandatangan submission failed', ['document_id' => $documentId, 'user_id' => Auth::id(), 'error' => $e->getMessage()]);
            return back()->withErrors('Gagal memproses: ' . $e->getMessage());
        }
    }

    // ======================================================
    // 7. PRIVATE: APPLY TTD TO PDF (FPDI)
    // ======================================================
    private function applyTandatanganToPdf($documentId)
    {
        $document = Document::findOrFail($documentId);
        $user = Auth::user();

        // Cek Workflow
        $workflow = WorkflowStep::where('document_id', $documentId)
            ->where('user_id', $user->id)
            ->first();

        if (!$workflow || is_null($workflow->posisi_x) || is_null($workflow->posisi_y)) {
            throw new \Exception("Posisi tanda tangan belum diatur.");
        }

        if (!$user->img_ttd_path) {
            throw new \Exception("Anda belum mengupload gambar tanda tangan.");
        }

        // Path File PDF
        $dbPath = $document->file_path;
        $sourcePath = null;
        $pathPrivate = storage_path('app/private/' . $dbPath);
        $pathPublic  = storage_path('app/public/' . $dbPath);
        $pathApp     = storage_path('app/' . $dbPath);

        if (file_exists($pathPrivate)) $sourcePath = $pathPrivate;
        elseif (file_exists($pathPublic)) $sourcePath = $pathPublic;
        elseif (file_exists($pathApp)) $sourcePath = $pathApp;
        else throw new \Exception("File fisik dokumen tidak ditemukan.");

        // Path Gambar TTD
        $ttdPath = storage_path('app/public/' . $user->img_ttd_path);
        if (!file_exists($ttdPath)) {
            throw new \Exception("File gambar tanda tangan tidak ditemukan.");
        }

        // Proses FPDI
        try {
            // FIX: Gunakan satuan 'pt' (points) agar sesuai dengan getTemplateSize()
            // Default FPDI adalah 'mm', yang menyebabkan ukuran halaman membengkak (1 pt != 1 mm)
            $pdf = new Fpdi('P', 'pt'); 
            $pageCount = $pdf->setSourceFile($sourcePath);

            for ($i = 1; $i <= $pageCount; $i++) {
                $template = $pdf->importPage($i);
                $size = $pdf->getTemplateSize($template);

                // AddPage dengan ukuran asli (dalam pt)
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($template);

                // Stamp TTD di halaman yang sesuai
                if ($i == $workflow->halaman) {
                    // Karena PDF sudah dalam 'pt', tidak perlu konversi * 0.352778
                    // Koordinat dari Frontend (PDF.js) biasanya sudah dalam skala 72 DPI (points)
                    
                    $x = $workflow->posisi_x; 
                    $y = $workflow->posisi_y;
                    
                    // Lebar tanda tangan (misal 100px di frontend ~= 100pt di PDF)
                    // Sesuaikan jika dirasa terlalu besar/kecil
                    $width = 100; 

                    $pdf->Image($ttdPath, $x, $y, $width);
                }
            }

            $pdf->Output($sourcePath, 'F'); // Overwrite file asli

        } catch (\Exception $e) {
            throw new \Exception("Gagal memproses PDF: " . $e->getMessage());
        }
    }
}
