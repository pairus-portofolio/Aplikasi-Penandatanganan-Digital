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
use Illuminate\Support\Facades\Http;

/**
 * Controller untuk mengelola proses tanda tangan dokumen.
 * 
 * Menangani upload tanda tangan dengan verifikasi reCAPTCHA,
 * penempatan tanda tangan pada PDF, dan submit tanda tangan
 * ke dalam workflow dokumen.
 * 
 * @package App\Http\Controllers\Kajur_Sekjur
 */
class TandatanganController extends Controller
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
     * Tampilkan halaman daftar dokumen yang perlu ditandatangani.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $daftarSurat = TableController::getData();
        return view('kajur_sekjur.tandatangan.index', compact('daftarSurat'));
    }

    /**
     * Tampilkan halaman detail dokumen untuk proses tanda tangan.
     * 
     * Menampilkan PDF viewer dan sidebar untuk drag & drop tanda tangan.
     * Jika user sudah pernah menempatkan tanda tangan, posisi akan dimuat kembali.
     *
     * @param int $id ID dokumen
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
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

    /**
     * Upload gambar tanda tangan baru dengan verifikasi reCAPTCHA.
     * 
     * Menggantikan gambar tanda tangan lama jika sudah ada.
     * File disimpan di storage/app/public/tandatangan.
     * Memerlukan validasi Google reCAPTCHA sebelum upload.
     *
     * @param Request $request Request dengan file image dan g-recaptcha-response
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadTandatangan(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:png,jpg,jpeg|max:2048',
            'g-recaptcha-response' => 'required',
        ]);

        // VERIFIKASI RECAPTCHA KE GOOGLE
        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => env('RECAPTCHA_SECRET_KEY'),
            'response' => $request->input('g-recaptcha-response'),
            'remoteip' => $request->ip(),
        ]);

        if (!$response->json()['success']) {
            return response()->json(['status' => 'error', 'message' => 'Validasi Captcha Gagal. Silakan coba lagi.'], 400);
        }

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

    /**
     * Hapus gambar tanda tangan user secara permanen.
     * 
     * Menghapus file dari storage dan reset path di database.
     *
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Simpan posisi tanda tangan yang ditempatkan user pada PDF.
     * 
     * Dipanggil via AJAX saat user drag & drop tanda tangan.
     * Menyimpan koordinat X, Y, dan nomor halaman.
     *
     * @param Request $request Request dengan posisi_x, posisi_y, halaman
     * @param int $id ID dokumen
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveTandatangan(Request $request, $id)
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

    /**
     * Submit tanda tangan ke dokumen dan lanjutkan workflow.
     * 
     * Proses:
     * 1. Validasi akses dan posisi tanda tangan
     * 2. Stamp tanda tangan ke PDF menggunakan PdfService
     * 3. Update status workflow step
     * 4. Proses step berikutnya (update status dokumen & kirim email)
     *
     * @param Request $request HTTP request
     * @param int $documentId ID dokumen
     * @return \Illuminate\Http\RedirectResponse
     */
    public function submit(Request $request, $documentId)
    {
        if (!$this->workflowService->checkAccess($documentId)) {
            return back()->withErrors('Bukan giliran Anda untuk menandatangani dokumen ini.');
        }

        // VALIDASI: Pastikan user sudah menempatkan TTD
        $activeStep = WorkflowStep::where('document_id', $documentId)
            ->where('user_id', Auth::id())
            ->first();

        // [MERGE FIX] Validasi harus dijalankan sebelum proses PDF
        if (is_null($activeStep->posisi_x) || is_null($activeStep->posisi_y) || !$activeStep->halaman) {
            return back()->withErrors('Anda belum menempatkan tanda tangan pada dokumen.');
        }

        try {
            // 1. Apply TTD ke PDF
            $this->pdfService->stampPdf($documentId, Auth::id(), 'tandatangan');

            // 2. Update Step Status dan Proses Workflow Selanjutnya
            $this->workflowService->completeStep($documentId, DocumentStatusEnum::DITANDATANGANI);
            $this->workflowService->processNextStep($documentId);

            Log::info('Document tandatangan submitted', ['document_id' => $documentId, 'user_id' => Auth::id()]);

            Log::info('Document tandatangan submitted', ['document_id' => $documentId, 'user_id' => Auth::id()]);

            return redirect()
                ->route('kajur.tandatangan.index')
                ->with('success', 'Dokumen berhasil ditandatangani.');

        } catch (\Exception $e) {
            Log::error('Tandatangan submission failed', ['document_id' => $documentId, 'user_id' => Auth::id(), 'error' => $e->getMessage()]);
            return back()->withErrors('Gagal memproses: ' . $e->getMessage());
        }
    }

}
