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

/**
 * Controller untuk mengelola review dokumen oleh Kaprodi.
 *
 * Menangani proses review dokumen, termasuk melihat detail dokumen
 * dan meminta revisi ke TU dengan mengirimkan notifikasi email.
 *
 * @package App\Http\Controllers\Kaprodi
 */
class ReviewController extends Controller
{
    /**
     * Tampilkan halaman daftar dokumen untuk review.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $daftarSurat = TableController::getData();
        return view('kaprodi.review.index', compact('daftarSurat'));
    }
    /**
     * Tampilkan detail dokumen untuk review.
     *
     * Tombol "Minta Revisi" hanya muncul jika status dokumen DITINJAU
     * dan user adalah giliran aktif dalam workflow.
     *
     * @param int $id ID dokumen
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $document = Document::findOrFail($id);
        
        $isActive = TableController::isUserActiveInWorkflow($document, Auth::id());

        $showRevisionButton = ($document->status === DocumentStatusEnum::DITINJAU) && $isActive;
        
        return view('shared.view-document', compact('document', 'showRevisionButton'));
    }
    /**
     * Proses permintaan revisi dokumen.
     *
     * Mengubah status dokumen menjadi PERLU_REVISI dan mengirim
     * notifikasi email ke TU dengan catatan revisi.
     *
     * @param Request $request HTTP request dengan catatan dan subjek
     * @param int $id ID dokumen
     * @return \Illuminate\Http\RedirectResponse
     */
    public function revise(Request $request, $id)
    {
        $request->validate([
            'catatan' => 'required|string',
            'subjek'  => 'required|string', 
        ]);

        $document = Document::findOrFail($id);

        $document->status = DocumentStatusEnum::PERLU_REVISI; 
        $document->save();

        if ($document->uploader && $document->uploader->email) {
            try {
                Mail::to($document->uploader->email)
                    ->send(new DocumentWorkflowNotification(
                        $document, 
                        $document->uploader, 
                        'revision_request',
                        $request->catatan,
                        $request->subjek
                    ));
            } catch (\Exception $e) {
                \Log::error("Gagal kirim email revisi: " . $e->getMessage());
            }
        }

        return redirect()->route('kaprodi.review.index')
            ->with('success', 'Status dokumen diubah menjadi Revisi. Catatan akan dikirim ke TU.');
    }
}
