<?php

namespace App\Http\Controllers\Kaprodi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Document;
use App\Models\WorkflowStep; // ← Tambahkan
use Illuminate\Support\Facades\Auth; // ← Tambahkan
use App\Http\Controllers\Dashboard\TableController;

class ParafController extends Controller
{
    // Function pengecekan giliran workflow
    private function checkWorkflowAccess($documentId)
    {
        // Step aktif = step dengan urutan terkecil yang belum signed
        $activeStep = WorkflowStep::where('document_id', $documentId)
            ->where('status', '!=', 'signed')
            ->orderBy('urutan')
            ->first();

        // Jika workflow sudah selesai → tidak ada step aktif
        if (!$activeStep) {
            return false;
        }

        // Cek apakah user login adalah user pada step aktif
        return $activeStep->user_id == Auth::id();
    }

    // 1. Halaman tabel daftar surat 
    public function index()
    {
        $daftarSurat = TableController::getData();
        return view('kaprodi.paraf.index', compact('daftarSurat'));
    }

    // 2. Halaman detail paraf
    public function show($id)
    {
        $document = Document::findOrFail($id);

        // Cek workflow
        if (!$this->checkWorkflowAccess($document->id)) {
            return redirect()->route('kaprodi.paraf.index')
                ->withErrors('Belum giliran Anda untuk memparaf dokumen ini.');
        }
        
        // Tampilkan halaman paraf
        return view('kaprodi.paraf-surat', compact('document'));
    }

    public function submit(Request $request, $documentId)
    {
        // Ambil step aktif (urutan terkecil yang belum selesai)
        $activeStep = WorkflowStep::where('document_id', $documentId)
            ->whereIn('status', ['Ditinjau']) // Step yang belum diparaf
            ->orderBy('urutan')
            ->first();

        // Validasi: apakah memang giliran user yang login?
        if (!$activeStep || $activeStep->user_id != Auth::id()) {
            return back()->withErrors('Bukan giliran Anda untuk memparaf dokumen ini.');
        }

        // ------------------------------------------
        //  1. UPDATE STATUS WORKFLOW_STEP
        // ------------------------------------------

        $activeStep->status = 'Diparaf';
        $activeStep->tanggal_aksi = now();

        // Jika kamu mau simpan posisi paraf, bisa ditambahkan di sini
        // $activeStep->posisi_x_ttd = $request->input('posisi_x');
        // $activeStep->posisi_y_ttd = $request->input('posisi_y');

        $activeStep->save();

        // ------------------------------------------
        // 2. STATUS DOKUMEN DISINKRONKAN OTOMATIS
        // ------------------------------------------

        $document = Document::find($documentId);

        // Cek apakah masih ada kaprodi lain yang belum paraf
        $masihBelumParaf = WorkflowStep::where('document_id', $documentId)
            ->where('status', 'Ditinjau')
            ->count();

        // Jika masih ada step yang belum dikerjakan
        if ($masihBelumParaf > 0) {
            // Dokumen tetap "Ditinjau"
            $document->status = 'Ditinjau';
        } 
        else {
            // SEMUA KAPRODI sudah paraf → dokumen masuk status "Diparaf"
            $document->status = 'Diparaf';
        }

        $document->save();

        return redirect()->route('kaprodi.paraf.index')
            ->with('success', 'Dokumen berhasil diparaf.');
    }


   
}
