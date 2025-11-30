<?php

namespace App\Http\Controllers\Kaprodi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Document;
use App\Models\WorkflowStep; 
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Dashboard\TableController;
use App\Enums\DocumentStatusEnum;
use Illuminate\Support\Facades\Mail;
use App\Mail\DocumentWorkflowNotification;

class ReviewController extends Controller
{
    // 1. Halaman LIST
    public function index()
    {
        $daftarSurat = TableController::getData();
        return view('kaprodi.review.index', compact('daftarSurat'));
    }

    // 2. Halaman DETAIL (lihat dokumen)
    public function show($id)
    {
        $document = Document::findOrFail($id);
        
        return view('kaprodi.review-surat', compact('document'));
    }

    // 3. PROSES REVISI (Action dari Pop-up)
    public function revise(Request $request, $id)
    {
        // Validasi input dari modal
        $request->validate([
            'catatan' => 'required|string',
            'subjek'  => 'required|string', 
        ]);

        $document = Document::findOrFail($id);

        // 1. Ubah Status Dokumen jadi 'Perlu Revisi'
        $document->status = DocumentStatusEnum::PERLU_REVISI; 
        $document->save();

        // 2. Kirim Email Otomatis ke TU
        if ($document->uploader && $document->uploader->email) {
            try {
                // Kita kirim parameter tambahan: tipe 'revision_request', catatan, dan subjek
                Mail::to($document->uploader->email)
                    ->send(new DocumentWorkflowNotification(
                        $document, 
                        $document->uploader, 
                        'revision_request', // Tipe Notifikasi
                        $request->catatan,  // Isi Catatan
                        $request->subjek    // Subjek Custom
                    ));
            } catch (\Exception $e) {
                // Log error tapi jangan gagalkan proses redirect
                \Log::error("Gagal kirim email revisi: " . $e->getMessage());
            }
        }

        return redirect()->route('kaprodi.review.index')
            ->with('success', 'Status dokumen diubah menjadi Revisi. Catatan akan dikirim ke TU.');
    }
}
