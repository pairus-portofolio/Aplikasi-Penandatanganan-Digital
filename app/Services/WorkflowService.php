<?php

namespace App\Services;

use App\Models\Document;
use App\Models\WorkflowStep;
use App\Enums\DocumentStatusEnum;
use App\Enums\RoleEnum;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
}
