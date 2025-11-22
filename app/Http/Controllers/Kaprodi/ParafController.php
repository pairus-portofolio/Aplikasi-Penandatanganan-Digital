<?php

namespace App\Http\Controllers\Kaprodi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Document;
use App\Models\WorkflowStep;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Dashboard\TableController;
use setasign\Fpdi\Fpdi;
use App\Enums\RoleEnum;

class ParafController extends Controller
{
    // =====================================================================
    // Cek apakah user ini adalah step aktif (Ditinjau)
    // =====================================================================
    private function checkWorkflowAccess($documentId)
    {
        $activeStep = WorkflowStep::where('document_id', $documentId)
            ->where('status', 'Ditinjau')
            ->orderBy('urutan')
            ->first();

        if (!$activeStep) {
            return false;
        }

        return $activeStep->user_id == Auth::id();
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

        // Akses workflow (jangan dihapus)
        if (!$this->checkWorkflowAccess($document->id)) {
            return redirect()->route('kaprodi.paraf.index')
                ->withErrors('Belum giliran Anda untuk memparaf dokumen ini.');
        }

        return view('kaprodi.paraf-surat', compact('document'));
    }

    // =====================================================================
    // 3. Upload Paraf Image (BARU)
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
                }
            } catch (\Exception $e) {
                // safe ignore
            }

            // Simpan file baru
            $path = $request->file('image')->store('paraf', 'public');

            // Update DB
            $user->update(['img_paraf_path' => $path]);

            return response()->json([
                'status' => 'success',
                'path' => asset('storage/' . $path),
                'message' => 'Paraf berhasil disimpan!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Server Error: ' . $e->getMessage()
            ], 500);
        }
    }

    // =====================================================================
    // 4. Hapus Paraf Permanen (BARU)
    // =====================================================================
    public function deleteParaf()
    {
        try {
            $user = Auth::user();

            if ($user->img_paraf_path && Storage::disk('public')->exists($user->img_paraf_path)) {
                Storage::disk('public')->delete($user->img_paraf_path);
            }

            $user->update(['img_paraf_path' => null]);

            return response()->json([
                'status' => 'success',
                'message' => 'Paraf berhasil dihapus permanen!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal hapus: ' . $e->getMessage()
            ], 500);
        }
    }

    // =====================================================================
    // 5. Submit Paraf (Workflow - JANGAN DIUBAH)
    // =====================================================================
    public function submit(Request $request, $documentId)
    {
        // VALIDASI DULU SEBELUM PROSES PDF (Fix Race Condition)
        $activeStep = WorkflowStep::where('document_id', $documentId)
            ->where('status', 'Ditinjau')
            ->orderBy('urutan')
            ->first();

        if (!$activeStep || $activeStep->user_id != Auth::id()) {
            return back()->withErrors('Bukan giliran Anda untuk memparaf dokumen ini.');
        }

        // VALIDASI: Pastikan user sudah menempatkan paraf (posisi_x, posisi_y, halaman tidak null)
        if (is_null($activeStep->posisi_x) || is_null($activeStep->posisi_y) || !$activeStep->halaman) {
            return back()->withErrors('Anda belum menempatkan paraf pada dokumen. Silakan drag & drop paraf Anda ke dokumen terlebih dahulu.');
        }

        // BARU PROSES PDF SETELAH VALIDASI
        $this->applyParafToPdf($documentId);

        // Update workflow step
        $activeStep->status = 'Diparaf';
        $activeStep->tanggal_aksi = now();
        $activeStep->save();

        $document = Document::find($documentId);

        // Cek apakah masih ada KAPRODI yang belum paraf
        $masihBelumParaf = WorkflowStep::where('document_id', $documentId)
            ->where('status', 'Ditinjau')
            ->whereHas('user.role', function ($q) {
                $q->whereIn('nama_role', RoleEnum::getKaprodiRoles());
            })
            ->count();

        // Jika masih ada Kaprodi → status tetap Ditinjau
        // Jika semua Kaprodi selesai → status Dokumen jadi Diparaf
        $document->status = ($masihBelumParaf > 0) ? 'Ditinjau' : 'Diparaf';
        $document->save();

        return redirect()->route('kaprodi.paraf.index')
            ->with('success', 'Dokumen berhasil diparaf.');
    }

    public function saveParaf(Request $request, $id)
    {
        $request->validate([
            'posisi_x' => 'required|numeric|min:0|max:1000',
            'posisi_y' => 'required|numeric|min:0|max:1500',
            'halaman'  => 'required|integer|min:1'
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

    private function applyParafToPdf($documentId)
    {
        $document = Document::findOrFail($documentId);

        // 1. Check workflow step dari user ini
        $workflow = WorkflowStep::where('document_id', $documentId)
            ->where('user_id', auth()->id())
            ->first();

        if (!$workflow || is_null($workflow->posisi_x) ||
            is_null($workflow->posisi_y) || !$workflow->halaman) {
            throw new \Exception("Data posisi paraf tidak lengkap.");
        }

        $user = auth()->user();

        if (!$user->img_paraf_path) {
            throw new \Exception("User belum mengatur gambar paraf.");
        }

        // ============================================================
        // 2. Tentukan PATH file PDF asli (bisa private/public/app)
        // ============================================================

        $dbPath = $document->file_path;
        $sourcePath = null;

        $pathPrivate = storage_path('app/private/' . $dbPath);
        $pathPublic  = storage_path('app/public/' . $dbPath);
        $pathApp     = storage_path('app/' . $dbPath);

        if (file_exists($pathPrivate)) {
            $sourcePath = $pathPrivate;
        } elseif (file_exists($pathPublic)) {
            $sourcePath = $pathPublic;
        } elseif (file_exists($pathApp)) {
            $sourcePath = $pathApp;
        } else {
            throw new \Exception("File fisik dokumen tidak ditemukan.");
        }

        // 3. Ambil gambar paraf user
        $parafPath = storage_path('app/public/' . $user->img_paraf_path);

        if (!file_exists($parafPath)) {
            throw new \Exception("File gambar paraf tidak ditemukan.");
        }

        // ============================================================
        // 4. PROSES FPDI — PARAF + OVERWRITE FILE LAMA
        // ============================================================

        try {
            $pdf = new \setasign\Fpdi\Fpdi();
            $pageCount = $pdf->setSourceFile($sourcePath);

            for ($i = 1; $i <= $pageCount; $i++) {

                $template = $pdf->importPage($i);
                $size = $pdf->getTemplateSize($template);

                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($template);

                // Tambah paraf hanya pada halaman yang ditentukan user
                if ($i == $workflow->halaman) {

                    $x_mm      = $workflow->posisi_x * 0.352778;
                    $y_mm      = $workflow->posisi_y * 0.352778;
                    $width_mm  = 100 * 0.352778;

                    $pdf->Image(
                        $parafPath,
                        $x_mm,
                        $y_mm,
                        $width_mm
                    );
                }
            }

            $pdf->Output($sourcePath, 'F');

        } catch (\Exception $e) {
            throw new \Exception("Gagal memproses PDF: " . $e->getMessage());
        }
    }
}