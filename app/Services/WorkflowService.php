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

class WorkflowService
{
    /**
     * Check if the current user has access to process the document at the current step.
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
     * Update the workflow step status for the current user.
     */
    public function completeStep($documentId, $status = 'Diparaf') // Default to 'Diparaf' for now, can be 'Ditandatangani'
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
     * Update the document status based on the progress of workflow steps.
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
     * Process the next step in the workflow after completing the current step.
     * Updates document status and sends email notifications.
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
