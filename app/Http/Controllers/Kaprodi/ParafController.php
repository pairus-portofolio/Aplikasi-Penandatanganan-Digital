<?php

namespace App\Http\Controllers\Kaprodi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Document;
use App\Models\WorkflowStep;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Dashboard\TableController;

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

        // Apakah masih ada yang belum paraf?
        $sisa = WorkflowStep::where('document_id', $documentId)
            ->where('status', 'Ditinjau')
            ->count();

        $document->status = ($sisa > 0) ? 'Ditinjau' : 'Diparaf';
        $document->save();

        return redirect()
            ->route('kaprodi.paraf.show', $documentId)
            ->with('success', 'Dokumen berhasil diparaf.')
            ->with('popup', true); 

    }
}
