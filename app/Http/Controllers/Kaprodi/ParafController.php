<?php

namespace App\Http\Controllers\Kaprodi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Document;
use App\Models\WorkflowStep;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Dashboard\TableController;
use setasign\Fpdi\Fpdi;
use App\Enums\RoleEnum;
use Illuminate\Support\Facades\Mail;
use App\Mail\DocumentWorkflowNotification;
use App\Enums\DocumentStatusEnum;
use App\Services\WorkflowService;

class ParafController extends Controller
{
    protected $workflowService;
    protected $pdfService;

    public function __construct(WorkflowService $workflowService, \App\Services\PdfService $pdfService)
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

        // Akses workflow (menggunakan Service agar lebih bersih)
        if (!$this->workflowService->checkAccess($document->id)) {
            return redirect()->route('kaprodi.paraf.index')
                ->withErrors('Belum giliran Anda untuk memparaf dokumen ini.');
        }

        $activeStep = WorkflowStep::where('document_id', $id)
            ->where('user_id', Auth::id())
            ->first();

        $savedParaf = null;
        if ($activeStep && $activeStep->posisi_x && $activeStep->posisi_y && $activeStep->halaman) {
            $savedParaf = [
                'x' => $activeStep->posisi_x,
                'y' => $activeStep->posisi_y,
                'page' => $activeStep->halaman
            ];
        }

        return view('kaprodi.paraf-surat', compact('document', 'savedParaf'));
    }

    // =====================================================================
    // 3. Upload Paraf Image
    // =====================================================================
    public function uploadParaf(Request $request)
    {
        // Validasi standar
        $request->validate([
            'image' => 'required|image|mimes:png,jpg,jpeg|max:2048',
        ]);

        if (!$request->hasFile('image')) {
            return response()->json([
                'status' => 'error',
                'message' => 'File tidak terbaca oleh sistem.'
            ], 400);
        }

        if (!$request->file('image')->isValid()) {
            return response()->json([
                'status' => 'error',
                'message' => 'File corrupt. Error Code: ' . $request->file('image')->getError()
            ], 400);
        }

        try {
            $user = Auth::user();

            // Hapus paraf lama jika ada
            try {
                if ($user->img_paraf_path && Storage::disk('public')->exists($user->img_paraf_path)) {
                    Storage::disk('public')->delete($user->img_paraf_path);
                    Log::info('Old paraf deleted', ['user_id' => $user->id, 'path' => $user->img_paraf_path]);
                }
            } catch (\Exception $e) {
                Log::warning('Failed to delete old paraf', [
                    'user_id' => $user->id,
                    'path' => $user->img_paraf_path,
                    'error' => $e->getMessage()
                ]);
            }

            // Simpan file baru
            $path = $request->file('image')->store('paraf', 'public');

            // Update DB
            $user->update(['img_paraf_path' => $path]);

            Log::info('Paraf uploaded successfully', ['user_id' => $user->id, 'path' => $path]);

            return response()->json([
                'status' => 'success',
                'path' => asset('storage/' . $path),
                'message' => 'Paraf berhasil disimpan!'
            ]);

        } catch (\Exception $e) {
            Log::error('Paraf upload failed', ['user_id' => Auth::id(), 'error' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Server Error: ' . $e->getMessage()
            ], 500);
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
                Log::info('Paraf deleted', ['user_id' => $user->id, 'path' => $user->img_paraf_path]);
            }

            $user->update(['img_paraf_path' => null]);

            return response()->json([
                'status' => 'success',
                'message' => 'Paraf berhasil dihapus permanen!'
            ]);

        } catch (\Exception $e) {
            Log::error('Paraf delete failed', ['user_id' => Auth::id(), 'error' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal hapus: ' . $e->getMessage()
            ], 500);
        }
    }

    // =====================================================================
    // 5. Submit Paraf
    // =====================================================================
    public function submit(Request $request, $documentId)
    {
        // 1. VALIDASI DULU SEBELUM PROSES PDF
        // Menggunakan Service Check Access agar konsisten
        if (!$this->workflowService->checkAccess($documentId)) {
            return back()->withErrors('Bukan giliran Anda untuk memparaf dokumen ini.');
        }

        $activeStep = WorkflowStep::where('document_id', $documentId)
            ->where('user_id', Auth::id())
            ->first();

        // VALIDASI: Pastikan user sudah menempatkan paraf (posisi_x, posisi_y, halaman tidak null)
        // [PENTING] Validasi ini dari branch bawah, harus dipertahankan agar FPDI tidak error
        if (is_null($activeStep->posisi_x) || is_null($activeStep->posisi_y) || !$activeStep->halaman) {
            return back()->withErrors('Anda belum menempatkan paraf pada dokumen. Silakan drag & drop paraf Anda ke dokumen terlebih dahulu.');
        }

        // 2. PROSES PDF
        try {
            // BARU PROSES PDF SETELAH VALIDASI
            $this->pdfService->stampPdf($documentId, Auth::id(), 'paraf');

            // 3. UPDATE STEP SAAT INI DAN PROSES WORKFLOW SELANJUTNYA
            $this->workflowService->completeStep($documentId, 'Diparaf');
            $this->workflowService->processNextStep($documentId);
            
            Log::info('Document paraf submitted', ['document_id' => $documentId, 'user_id' => Auth::id()]);

            return redirect()->route('kaprodi.paraf.index')
                ->with('success', 'Dokumen berhasil diparaf.');

        } catch (\Exception $e) {
            Log::error('Paraf submission failed', ['document_id' => $documentId, 'user_id' => Auth::id(), 'error' => $e->getMessage()]);
            return back()->withErrors('Gagal memproses PDF: ' . $e->getMessage());
        }
    }

    public function saveParaf(Request $request, $id)
    {
        $request->validate([
            'posisi_x' => 'nullable|numeric|min:0|max:1000',
            'posisi_y' => 'nullable|numeric|min:0|max:1500',
            'halaman'  => 'nullable|integer|min:1'
        ]);

        $workflowStep = WorkflowStep::where('document_id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$workflowStep) {
            return response()->json(["status" => "error", "message" => "Workflow step not found"], 404);
        }
        $data = [
            'posisi_x' => $request->posisi_x,
            'posisi_y' => $request->posisi_y,
            'halaman'  => $request->halaman,
            'tanggal_aksi' => now()
        ];

        try {
            $workflowStep->update($data);
        } catch (\Exception $e) {
            return response()->json(["status" => "error", "message" => "DB error: ".$e->getMessage()], 500);
        }

        return response()->json(["status" => "success"]);
    }

    // Private method applyParafToPdf removed as it is now handled by PdfService
}