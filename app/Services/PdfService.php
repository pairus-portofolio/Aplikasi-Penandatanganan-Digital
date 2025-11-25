<?php

namespace App\Services;

use App\Models\Document;
use App\Models\WorkflowStep;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;

class PdfService
{
    public function applySignature($documentId, $userId)
    {
        $document = Document::findOrFail($documentId);
        $workflow = WorkflowStep::where('document_id', $documentId)
            ->where('user_id', $userId)
            ->first();

        if (!$workflow || is_null($workflow->posisi_x) || is_null($workflow->posisi_y) || !$workflow->halaman) {
            return; // No signature position set, skip
        }

        $user = $workflow->user; // Get user from workflow relation
        if (!$user->img_paraf_path) {
            throw new \Exception("User belum mengatur gambar paraf.");
        }

        // 1. Find Source File
        $dbPath = $document->file_path;
        $sourcePath = $this->resolveFilePath($dbPath);

        // 2. Find Signature Image
        $parafPath = storage_path('app/public/' . $user->img_paraf_path);
        if (!file_exists($parafPath)) {
            throw new \Exception("File gambar paraf tidak ditemukan.");
        }

        // 3. Process FPDI
        $outputDir = storage_path('app/public/paraf_output');
        if (!is_dir($outputDir)) mkdir($outputDir, 0755, true);

        $newFileName = 'paraf_output/' . $documentId . '_' . time() . '.pdf';
        $outputFile = storage_path('app/public/' . $newFileName);

        try {
            $pdf = new Fpdi();
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

                    $pdf->Image($parafPath, $x_mm, $y_mm, $lebar_mm);
                }
            }

            $pdf->Output($outputFile, 'F');

            // Update document path to the new file
            $document->update(['file_path' => $newFileName]);

        } catch (\Exception $e) {
            throw new \Exception("Gagal memproses PDF: " . $e->getMessage());
        }
    }

    private function resolveFilePath($relativePath)
    {
        $paths = [
            storage_path('app/private/' . $relativePath),
            storage_path('app/public/' . $relativePath),
            storage_path('app/' . $relativePath),
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        throw new \Exception("File fisik surat tidak ditemukan.");
    }
}
