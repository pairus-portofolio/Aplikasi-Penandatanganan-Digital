<?php

namespace App\Services;

use App\Models\Document;
use App\Models\WorkflowStep;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;

class PdfService
{
    /**
     * Stamp the PDF with a signature or paraf image.
     *
     * @param int $documentId
     * @param int $userId
     * @param string $type 'paraf' or 'tandatangan'
     * @throws \Exception
     */
    public function stampPdf($documentId, $userId, $type)
    {
        $document = Document::findOrFail($documentId);
        $user = Auth::user(); // Or find by $userId if needed, but usually Auth::user() is safe here

        // 1. Get Workflow Step
        $workflow = WorkflowStep::where('document_id', $documentId)
            ->where('user_id', $userId)
            ->first();

        if (!$workflow || is_null($workflow->posisi_x) || is_null($workflow->posisi_y) || !$workflow->halaman) {
            throw new \Exception("Posisi $type belum diatur.");
        }

        // 2. Get Image Path based on Type
        $imagePath = null;
        if ($type === 'paraf') {
            $imagePath = $user->img_paraf_path;
        } elseif ($type === 'tandatangan') {
            $imagePath = $user->img_ttd_path;
        } else {
            throw new \Exception("Tipe stamp tidak valid.");
        }

        if (!$imagePath) {
            throw new \Exception("Anda belum mengupload gambar $type.");
        }

        $fullImagePath = storage_path('app/public/' . $imagePath);
        if (!file_exists($fullImagePath)) {
            throw new \Exception("File gambar $type tidak ditemukan.");
        }

        // 3. Resolve PDF Source Path
        $dbPath = $document->file_path;
        $sourcePath = null;
        $pathPrivate = storage_path('app/private/' . $dbPath);
        $pathPublic  = storage_path('app/public/' . $dbPath);
        $pathApp     = storage_path('app/' . $dbPath);

        if (file_exists($pathPrivate)) $sourcePath = $pathPrivate;
        elseif (file_exists($pathPublic)) $sourcePath = $pathPublic;
        elseif (file_exists($pathApp)) $sourcePath = $pathApp;
        else throw new \Exception("File fisik dokumen tidak ditemukan.");

        // 4. Process FPDI
        try {
            // Use 'pt' (points) for consistency with frontend coordinates
            $pdf = new Fpdi('P', 'pt');
            $pageCount = $pdf->setSourceFile($sourcePath);

            for ($i = 1; $i <= $pageCount; $i++) {
                $template = $pdf->importPage($i);
                $size = $pdf->getTemplateSize($template);

                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($template);

                // Stamp on the correct page
                if ($i == $workflow->halaman) {
                    $x = $workflow->posisi_x;
                    $y = $workflow->posisi_y;
                    
                    // Default width 100pt (same as frontend 100px assumption)
                    $width = 100;

                    $pdf->Image($fullImagePath, $x, $y, $width);
                }
            }

            // Overwrite original file
            $pdf->Output($sourcePath, 'F');

        } catch (\Exception $e) {
            throw new \Exception("Gagal memproses PDF: " . $e->getMessage());
        }
    }
}
