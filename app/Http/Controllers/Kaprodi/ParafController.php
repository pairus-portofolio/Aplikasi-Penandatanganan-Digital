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
use App\Services\WorkflowService;
use App\Enums\DocumentStatusEnum;

/**
 * Controller untuk mengelola proses paraf dokumen.
 * * Menangani upload gambar paraf, penempatan paraf pada PDF,
 * dan submit paraf ke dalam workflow dokumen.
 * * @package App\Http\Controllers\Kaprodi
 */
class ParafController extends Controller
{
    /**
     * Service untuk mengelola workflow dokumen.
     *
     * @var WorkflowService
     */
    protected $workflowService;

    /**
     * Service untuk mengelola operasi PDF.
     *
     * @var \App\Services\PdfService
     */
    protected $pdfService;

    /**
     * Inisialisasi controller dengan dependency injection.
     *
     * @param WorkflowService $workflowService Service untuk workflow
     * @param \App\Services\PdfService $pdfService Service untuk PDF
     */
    public function __construct(WorkflowService $workflowService, \App\Services\PdfService $pdfService)
    {
        $this->workflowService = $workflowService;
        $this->pdfService = $pdfService;
    }

    /**
     * Tampilkan halaman daftar dokumen yang perlu diparaf.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $daftarSurat = TableController::getData();
        return view('kaprodi.paraf.index', compact('daftarSurat'));
    }

    /**
     * Tampilkan halaman detail dokumen untuk proses paraf.
     * * Menampilkan PDF viewer dan sidebar untuk drag & drop paraf.
     * Jika user sudah pernah menempatkan paraf, posisi akan dimuat kembali.
     *
     * @param int $id ID dokumen
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function show($id)
    {
        $document = Document::findOrFail($id);

        // 1. Cek apakah user adalah giliran aktif (Mode Kerjakan)
        $isCurrentTurn = $this->workflowService->checkAccess($document->id);
        
        // 2. Cek apakah user ada di history workflow (Mode Lihat-Saja)
        $isInWorkflow = WorkflowStep::where('document_id', $id)->where('user_id', Auth::id())->exists();

        // Jika user tidak aktif dan tidak ada di history, tolak akses
        if (!$isCurrentTurn && !$isInWorkflow) {
            return redirect()->route('kaprodi.paraf.index')
                ->withErrors('Anda tidak memiliki akses ke dokumen ini.');
        }

        // Jika user ada di workflow tapi bukan giliran aktif, set view-only mode
        $isViewOnly = !$isCurrentTurn;

        // [PERBAIKAN UTAMA] Jika View Only, gunakan tampilan shared view-document
        if ($isViewOnly) {
            // Karena ini mode Lihat-Saja, tombol Revisi juga disembunyikan
            return view('shared.view-document', [
                'document' => $document,
                'showRevisionButton' => false
            ]);
        }
        
        // --- LANJUT KE MODE KERJAKAN (GILIRAN AKTIF) ---

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

        // Prepare paraf data for view (no logic in Blade)
        $user = Auth::user();
        $parafData = [
            'hasImage' => !empty($user->img_paraf_path),
            'url' => !empty($user->img_paraf_path) ? asset('storage/' . $user->img_paraf_path) : ''
        ];

        return view('kaprodi.paraf-surat', compact('document', 'savedParaf', 'parafData'));
    }

    /**
     * Upload gambar paraf baru untuk user yang sedang login.
     * * Menggantikan gambar paraf lama jika sudah ada.
     * File disimpan di storage/app/public/paraf.
     *
     * @param Request $request Request dengan file image
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadParaf(Request $request)
    {
                // Validasi standarpublic function uploadParaf(Request $request)
        {
            $errorResponse = null;

            // Validasi standar
            $request->validate([
                'image' => 'required|image|mimes:png,jpg,jpeg|max:2048',
            ]);

            if (!$request->hasFile('image')) {
                $errorResponse = [
                    'status'  => 'error',
                    'message' => 'File tidak terbaca oleh sistem.',
                    'code'    => 400
                ];
            }

            if (!$errorResponse && !$request->file('image')->isValid()) {
                $errorResponse = [
                    'status'  => 'error',
                    'message' => 'File corrupt. Error Code: ' . $request->file('image')->getError(),
                    'code'    => 400
                ];
            }

            // Jika ada error validasi manual → return satu kali
            if ($errorResponse) {
                return response()->json([
                    'status'  => $errorResponse['status'],
                    'message' => $errorResponse['message']
                ], $errorResponse['code']);
            }

            // Proses utama
            try {
                $user = Auth::user();

                // Hapus paraf lama
                try {
                    if ($user->img_paraf_path && Storage::disk('public')->exists($user->img_paraf_path)) {
                        Storage::disk('public')->delete($user->img_paraf_path);
                        Log::info('Old paraf deleted', [
                            'user_id' => $user->id,
                            'path'    => $user->img_paraf_path
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to delete old paraf', [
                        'user_id' => $user->id,
                        'path'    => $user->img_paraf_path,
                        'error'   => $e->getMessage()
                    ]);
                }

                // Simpan file baru
                $path = $request->file('image')->store('paraf', 'public');

                // Update DB
                $user->update(['img_paraf_path' => $path]);

                Log::info('Paraf uploaded successfully', [
                    'user_id' => $user->id,
                    'path'    => $path
                ]);

                return response()->json([
                    'status'  => 'success',
                    'path'    => asset('storage/' . $path),
                    'message' => 'Paraf berhasil disimpan!'
                ]);

            } catch (\Exception $e) {

                Log::error('Paraf upload failed', [
                    'user_id' => Auth::id(),
                    'error'   => $e->getMessage()
                ]);

                // Return catch → 1 kali saja
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Server Error: ' . $e->getMessage()
                ], 500);
            }
        }
    }

    /**
     * Hapus gambar paraf user secara permanen.
     * * Menghapus file dari storage dan reset path di database.
     *
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Submit paraf ke dokumen dan lanjutkan workflow.
     * * Proses:
     * 1. Validasi akses dan posisi paraf
     * 2. Stamp paraf ke PDF menggunakan PdfService
     * 3. Update status workflow step
     * 4. Proses step berikutnya (update status dokumen & kirim email)
     *
     * @param Request $request HTTP request
     * @param int $documentId ID dokumen
     * @return \Illuminate\Http\RedirectResponse
     */
    public function submit(Request $request, $documentId)
    {
        $error = null;

        // 1. Validasi akses
        if (!$this->workflowService->checkAccess($documentId)) {
            $error = 'Bukan giliran Anda untuk memparaf dokumen ini.';
        }

        $activeStep = WorkflowStep::where('document_id', $documentId)
            ->where('user_id', Auth::id())
            ->first();

        // 2. Validasi posisi
        if (!$error && (is_null($activeStep->posisi_x) || is_null($activeStep->posisi_y) || !$activeStep->halaman)) {
            $error = 'Anda belum menempatkan paraf pada dokumen.';
        }

        // Jika ada error → return satu kali saja
        if ($error) {
            return back()->withErrors($error);
        }

        // 3. Proses PDF & Workflow
        try {
            $this->pdfService->stampPdf($documentId, Auth::id(), 'paraf');

            $this->workflowService->completeStep($documentId, DocumentStatusEnum::DIPARAF);

            $sendNotification = $request->input('send_notification') == '1';
            $this->workflowService->processNextStep($documentId, $sendNotification);

            Log::info('Document paraf submitted', [
                'document_id' => $documentId,
                'user_id'     => Auth::id()
            ]);

            return redirect()
                ->route('kaprodi.paraf.index')
                ->with('success', 'Dokumen berhasil diparaf.');

        } catch (\Exception $e) {

            Log::error('Paraf submission failed', [
                'document_id' => $documentId,
                'error'       => $e->getMessage()
            ]);

            return back()->withErrors($e->getMessage());
        }
    }
    /**
     * Simpan posisi paraf yang ditempatkan user pada PDF.
     * * Dipanggil via AJAX saat user drag & drop paraf.
     * Menyimpan koordinat X, Y, dan nomor halaman.
     *
     * @param Request $request Request dengan posisi_x, posisi_y, halaman
     * @param int $id ID dokumen
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveParaf(Request $request, $id)
    {
        $request->validate([
            'posisi_x' => 'nullable|numeric|min:0|max:2000',
            'posisi_y' => 'nullable|numeric|min:0|max:3000',
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

}
