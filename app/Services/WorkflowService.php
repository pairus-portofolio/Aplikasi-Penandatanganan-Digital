<?php

namespace App\Services;

use App\Models\Document;
use App\Models\WorkflowStep;
use App\Enums\DocumentStatusEnum;
use App\Enums\RoleEnum;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\DocumentWorkflowNotification;

/**
 * Service untuk mengelola workflow dokumen.
 * * Menangani validasi akses, update status workflow step,
 * update status dokumen, dan pengiriman notifikasi email.
 * * @package App\Services
 */
class WorkflowService
{
    /**
     * Cek apakah user yang sedang login memiliki akses untuk memproses dokumen.
     * * User memiliki akses jika workflow step yang aktif (status 'Ditinjau')
     * dengan urutan terkecil adalah milik user tersebut.
     *
     * @param int $documentId ID dokumen
     * @return bool True jika user memiliki akses, false jika tidak
     */
    public function checkAccess($documentId)
    {
        $activeStep = WorkflowStep::where('document_id', $documentId)
            ->where('status', DocumentStatusEnum::DITINJAU)
            ->orderBy('urutan')
            ->first();

        if (!$activeStep) {
            return false;
        }

        return $activeStep->user_id == Auth::id();
    }

    /**
     * Tandai workflow step saat ini sebagai selesai.
     * * Update status step menjadi 'Diparaf' atau 'Ditandatangani'
     * dan set tanggal aksi.
     *
     * @param int $documentId ID dokumen
     * @param string $status Status baru ('Diparaf' atau 'Ditandatangani')
     * @return WorkflowStep Step yang sudah diupdate
     * @throws \Exception Jika bukan giliran user atau step tidak ditemukan
     */
    public function completeStep($documentId, $status = 'Diparaf')
    {
        $activeStep = WorkflowStep::where('document_id', $documentId)
            ->where('status', DocumentStatusEnum::DITINJAU)
            ->orderBy('urutan')
            ->first();

        if (!$activeStep || $activeStep->user_id != Auth::id()) {
            throw new \Exception('Bukan giliran Anda untuk memproses dokumen ini.');
        }

        $activeStep->status = $status; // 'Diparaf' or 'Ditandatangani'
        $activeStep->tanggal_aksi = now();
        $activeStep->save();

        Log::info('Workflow step completed', [
            'document_id' => $documentId,
            'step_id' => $activeStep->id,
            'user_id' => Auth::id(),
            'status' => $status
        ]);

        return $activeStep;
    }

    /**
     * Update status dokumen berdasarkan progress workflow steps.
     * * Logika:
     * - Jika masih ada Koordinator yang pending -> status 'Ditinjau'
     * - Jika Koordinator selesai tapi Kajur/Sekjur pending -> status 'Diparaf'
     * - Jika semua selesai -> status 'Ditandatangani'
     *
     * @param int $documentId ID dokumen
     * @return void
     */
    public function updateDocumentStatus($documentId)
    {
        $document = Document::findOrFail($documentId);

        // PERBAIKAN: Ganti getKaprodiRoles() menjadi getKoordinatorRoles()
        $pendingKoordinator = WorkflowStep::where('document_id', $documentId)
            ->where('status', DocumentStatusEnum::DITINJAU)
            ->whereHas('user.role', function ($q) {
                $q->whereIn('nama_role', RoleEnum::getKoordinatorRoles());
            })
            ->exists();

        // Check if there are any pending steps for Kajur/Sekjur (Tanda Tangan)
        $pendingKajurSekjur = WorkflowStep::where('document_id', $documentId)
            ->where('status', DocumentStatusEnum::DITINJAU)
            ->whereHas('user.role', function ($q) {
                $q->whereIn('nama_role', RoleEnum::getKajurSekjurRoles());
            })
            ->exists();

        if ($pendingKoordinator) {
            // Jika Koordinator masih ada yang belum paraf, status tetap Ditinjau
            $document->status = DocumentStatusEnum::DITINJAU;
        } elseif ($pendingKajurSekjur) {
            // Jika Koordinator selesai tapi Kajur/Sekjur belum, status jadi Diparaf
            $document->status = DocumentStatusEnum::DIPARAF;
        } else {
            // Jika semua selesai
            $document->status = DocumentStatusEnum::DITANDATANGANI;
        }

        if ($document->isDirty('status')) {
            $oldStatus = $document->getOriginal('status');
            $document->save();
            
            Log::info('Document status updated', [
                'document_id' => $documentId,
                'old_status' => $oldStatus,
                'new_status' => $document->status
            ]);
        } else {
            $document->save();
        }
    }

    /**
     * Proses step berikutnya dalam workflow setelah step saat ini selesai.
     * * Menangani:
     * 1. Update status dokumen berdasarkan step yang tersisa
     * 2. Kirim email notifikasi ke user berikutnya (jika ada)
     * 3. Kirim email completion ke uploader (jika workflow selesai)
     *
     * @param int $documentId ID dokumen
     * @return void
     */
    public function processNextStep($documentId, $sendNotification = false)
    {
        // 1. Update document status based on remaining workflow steps
        $this->updateDocumentStatus($documentId);

        $document = Document::findOrFail($documentId);

        // 2. Find the next pending step (first step with status 'Ditinjau')
        $nextStep = WorkflowStep::where('document_id', $documentId)
            ->where('status', DocumentStatusEnum::DITINJAU)
            ->orderBy('urutan')
            ->first();

        // 3. LOGIKA PENGIRIMAN EMAIL
        if ($sendNotification) {
            
            if ($nextStep && $nextStep->user) {
                // Kasus: Ada user selanjutnya (Estafet)
                try {
                    Mail::to($nextStep->user->email)
                        ->send(new DocumentWorkflowNotification($document, $nextStep->user, 'next_turn'));
                    
                    Log::info('Notifikasi estafet dikirim manual', ['doc_id' => $documentId]);
                } catch (\Exception $e) {
                    Log::error('Gagal kirim notifikasi estafet', ['error' => $e->getMessage()]);
                }

            } else {
                // Kasus: Tidak ada step lagi (Finish) -> Kirim ke TU
                if ($document->uploader && $document->uploader->email) {
                    try {
                        Mail::to($document->uploader->email)
                            ->send(new DocumentWorkflowNotification($document, $document->uploader, 'completed'));
                        
                        Log::info('Notifikasi selesai dikirim manual', ['doc_id' => $documentId]);
                    } catch (\Exception $e) {
                        Log::error('Gagal kirim notifikasi selesai', ['error' => $e->getMessage()]);
                    }
                }
            }
        }
    }
}