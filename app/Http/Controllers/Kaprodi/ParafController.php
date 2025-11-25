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
use Illuminate\Support\Facades\Mail;
use App\Mail\DocumentWorkflowNotification;

class ParafController extends Controller
{
    // =====================================================================
    // 0. Cek apakah user ini adalah step aktif (Ditinjau)
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

        // Akses workflow
        if (!$this->checkWorkflowAccess($document->id)) {
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
    // 5. Submit Paraf
    // =====================================================================
    public function submit(Request $request, $documentId)
    {
        // 1. VALIDASI DULU SEBELUM PROSES PDF
        $activeStep = WorkflowStep::where('document_id', $documentId)
            ->where('status', 'Ditinjau')
            ->orderBy('urutan')
            ->first();

        if (!$activeStep || $activeStep->user_id != Auth::id()) {
            return back()->withErrors('Bukan giliran Anda untuk memparaf dokumen ini.');
        }

        // 2. PROSES PDF
        try {
            $this->applyParafToPdf($documentId);
        } catch (\Exception $e) {
            return back()->withErrors('Gagal memproses PDF: ' . $e->getMessage());
        }

        // 3. UPDATE STEP SAAT INI
        $activeStep->status = 'Diparaf';
        $activeStep->tanggal_aksi = now();
        $activeStep->save();

        $document = Document::find($documentId);

        // ============================================================
        // 4. CEK ESTAFET & KIRIM EMAIL
        // ============================================================
        
        // Cari langkah workflow SELANJUTNYA
        $nextStep = WorkflowStep::where('document_id', $documentId)
            ->where('urutan', $activeStep->urutan + 1)
            ->first();

        if ($nextStep && $nextStep->user) {
            // --- KASUS A: ADA ESTAFET KE USER LAIN ---
            
            // ID 4 = Kajur, ID 5 = Sekjur
            $nextRoleId = $nextStep->user->role_id;

            // Jika user berikutnya adalah Kajur/Sekjur, status HARUS 'Diparaf'
            // agar muncul di dashboard mereka.
            if (in_array($nextRoleId, [4, 5])) {
                $document->status = 'Diparaf';
            } else {
                // Jika user berikutnya masih sesama Kaprodi/Dosen (Paraf), status tetap 'Ditinjau'
                $document->status = 'Ditinjau'; 
            }
            
            $document->save();
            
            // Kirim Email ke User Selanjutnya
            try {
                Mail::to($nextStep->user->email)
                    ->send(new DocumentWorkflowNotification($document, $nextStep->user, 'next_turn'));
            } catch (\Exception $e) {
                \Log::error("Gagal kirim email estafet: " . $e->getMessage());
            }

        } else {
            // --- KASUS B: TIDAK ADA LAGI (FINISH) ---
            // Logika jika setelah ini tidak ada step lagi
            
            $document->status = 'Diparaf';
            $document->save();
            
            // Kirim Email ke Pengunggah (TU)
            if ($document->uploader && $document->uploader->email) {
                try {
                    Mail::to($document->uploader->email)
                        ->send(new DocumentWorkflowNotification($document, $document->uploader, 'completed'));
                } catch (\Exception $e) {
                    \Log::error("Gagal kirim email selesai ke TU: " . $e->getMessage());
                }
            }
        }
        
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

        // 1. Cek Workflow & User
        $workflow = WorkflowStep::where('document_id', $documentId)
            ->where('user_id', auth()->id())
            ->first();

        if (!$workflow || is_null($workflow->posisi_x) || is_null($workflow->posisi_y) || !$workflow->halaman) {
            return; 
        }

        $user = auth()->user();
        if (!$user->img_paraf_path) {
            throw new \Exception("User belum mengatur gambar paraf.");
        }

        // ============================================================
        // 2. CARI FILE 
        // ============================================================
        $dbPath = $document->file_path; 
        $sourcePath = null;

        $pathPrivate = storage_path('app/private/' . $dbPath);
        $pathPublic = storage_path('app/public/' . $dbPath);
        $pathApp = storage_path('app/' . $dbPath);

        if (file_exists($pathPrivate)) {
            $sourcePath = $pathPrivate;
        } elseif (file_exists($pathPublic)) {
            $sourcePath = $pathPublic;
        } elseif (file_exists($pathApp)) {
            $sourcePath = $pathApp;
        } else {
            throw new \Exception("File fisik surat tidak ditemukan.");
        }

        // 3. CARI GAMBAR PARAF
        $parafPath = storage_path('app/public/' . $user->img_paraf_path);
        if (!file_exists($parafPath)) {
            throw new \Exception("File gambar paraf tidak ditemukan.");
        }

        // 4. PROSES FPDI
        $outputDir = storage_path('app/public/paraf_output');
        if (!is_dir($outputDir)) mkdir($outputDir, 0755, true);

        $newFileName = 'paraf_output/' . $documentId . '_' . time() . '.pdf';
        $outputFile = storage_path('app/public/' . $newFileName);

        try {
            $pdf = new \setasign\Fpdi\Fpdi(); 
            
            $pageCount = $pdf->setSourceFile($sourcePath);

            for ($i = 1; $i <= $pageCount; $i++) {
                $template = $pdf->importPage($i);
                $size = $pdf->getTemplateSize($template);

                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($template);

                if ($i == $workflow->halaman) {

                    $x_mm = $workflow->posisi_x * 0.352778; 
                    $y_mm = $workflow->posisi_y * 0.352778;

                    $lebar_mm = 100 * 0.352778; 

                    $pdf->Image(
                        $parafPath,
                        $x_mm, 
                        $y_mm, 
                        $lebar_mm
                    );
                }
            }

            $pdf->Output($outputFile, 'F');

            $document->update([
                'file_path' => $newFileName
            ]);

        } catch (\Exception $e) {
            throw new \Exception("Gagal memproses PDF: " . $e->getMessage());
        }
    }
}
