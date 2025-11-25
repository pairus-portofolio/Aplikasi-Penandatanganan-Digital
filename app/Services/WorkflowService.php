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
 * 
 * Menangani validasi akses, update status workflow step,
 * update status dokumen, dan pengiriman notifikasi email.
 * 
 * @package App\Services
 */
class WorkflowService
{
    /**
     * Cek apakah user yang sedang login memiliki akses untuk memproses dokumen.
     * 
     * User memiliki akses jika workflow step yang aktif (status 'Ditinjau')
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
     * 
     * Update status step menjadi 'Diparaf' atau 'Ditandatangani'
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
     * 
     * Logika:
     * - Jika masih ada Kaprodi yang pending -> status 'Ditinjau'
     * - Jika Kaprodi selesai tapi Kajur/Sekjur pending -> status 'Diparaf'
     * - Jika semua selesai -> status 'Ditandatangani'
     *
     * @param int $documentId ID dokumen
     * @return void
     */
    public function updateDocumentStatus($documentId)
    {
        $document = Document::findOrFail($documentId);

        // Check if there are any pending steps for Kaprodi (Paraf)
        $pendingKaprodi = WorkflowStep::where('document_id', $documentId)
            ->where('status', DocumentStatusEnum::DITINJAU)
            ->whereHas('user.role', function ($q) {
                $q->whereIn('nama_role', RoleEnum::getKaprodiRoles());
            })
            ->exists();

        // Check if there are any pending steps for Kajur/Sekjur (Tanda Tangan)
        $pendingKajurSekjur = WorkflowStep::where('document_id', $documentId)
            ->where('status', DocumentStatusEnum::DITINJAU)
            ->whereHas('user.role', function ($q) {
                $q->whereIn('nama_role', RoleEnum::getKajurSekjurRoles());
            })
            ->exists();

        if ($pendingKaprodi) {
            // If Kaprodi still needs to action, status remains Ditinjau
            $document->status = DocumentStatusEnum::DITINJAU;
        } elseif ($pendingKajurSekjur) {
            // If Kaprodi done but Kajur/Sekjur pending, status becomes Diparaf
            $document->status = DocumentStatusEnum::DIPARAF;
        } else {
            // If all done
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
     * 
     * Menangani:
     * 1. Update status dokumen berdasarkan step yang tersisa
     * 2. Kirim email notifikasi ke user berikutnya (jika ada)
     * 3. Kirim email completion ke uploader (jika workflow selesai)
     *
     * @param int $documentId ID dokumen
     * @return void
     */
    public function processNextStep($documentId)
    {
        // 1. Update document status based on remaining workflow steps
        $this->updateDocumentStatus($documentId);

        $document = Document::findOrFail($documentId);

        // 2. Find the next pending step (first step with status 'Ditinjau')
        $nextStep = WorkflowStep::where('document_id', $documentId)
            ->where('status', DocumentStatusEnum::DITINJAU)
            ->orderBy('urutan')
            ->first();

        if ($nextStep && $nextStep->user) {
            // There is a next step - send notification to the next user
            try {
                Mail::to($nextStep->user->email)
                    ->send(new DocumentWorkflowNotification($document, $nextStep->user, 'next_turn'));
                
                Log::info('Next step notification sent', [
                    'document_id' => $documentId,
                    'next_user_id' => $nextStep->user_id,
                    'next_user_email' => $nextStep->user->email
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send next step notification', [
                    'document_id' => $documentId,
                    'next_user_id' => $nextStep->user_id,
                    'error' => $e->getMessage()
                ]);
            }
        } else {
            // No more pending steps - workflow is complete
            // Send completion notification to the uploader
            if ($document->uploader && $document->uploader->email) {
                try {
                    Mail::to($document->uploader->email)
                        ->send(new DocumentWorkflowNotification($document, $document->uploader, 'completed'));
                    
                    Log::info('Workflow completion notification sent', [
                        'document_id' => $documentId,
                        'uploader_id' => $document->uploader->id,
                        'uploader_email' => $document->uploader->email
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to send completion notification', [
                        'document_id' => $documentId,
                        'uploader_id' => $document->uploader->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
    }
}
