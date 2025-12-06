<?php

namespace App\Http\Controllers\Tu;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Enums\DocumentStatusEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Ilovepdf\Ilovepdf;

class FinalisasiController extends Controller
{
    /**
     * Halaman daftar dokumen yang siap difinalisasi
     */
    public function index()
    {
        $suratFinalisasi = Document::where('status', DocumentStatusEnum::DITANDATANGANI)
            ->with('uploader')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('Tu.finalisasi.index', compact('suratFinalisasi'));
    }

    /**
     * Halaman preview dokumen sebelum finalisasi
     */
    public function show($id)
    {
        $document = Document::with('uploader')->findOrFail($id);

        return view('Tu.finalisasi.show', compact('document'));
    }

    /**
     * PREVIEW PDF (Versi "Pencarian File Pintar")
     */
    public function preview($id)
    {
        $document = Document::findOrFail($id);
        
        $fullPath = $this->findPhysicalPath($document->file_path);

        return response()->file($fullPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$document->file_name.'"'
        ]);
    }

    /**
     * Finalisasi dokumen & Trigger Popup
     */
    public function store(Request $request, $id)
    {
        $document = Document::findOrFail($id);

        $document->status = DocumentStatusEnum::FINAL;
        $document->save();

        return redirect()
            ->route('tu.finalisasi.index')
            ->with('success', 'Dokumen berhasil difinalisasi.')
            ->with('download_doc_id', $document->id);
    }

    /**
     * DOWNLOAD PDF (Dengan Kompresi Jika Perlu)
     */
    public function download($id)
    {
        $document = Document::findOrFail($id);
        $sourcePath = $this->findPhysicalPath($document->file_path);
        $downloadName = $document->judul_surat . '.pdf';
        
        // Batas ukuran 1 MB
        $oneMB = 1048576;

        // Jika file besar, coba kompres dulu
        if (filesize($sourcePath) > $oneMB) {
            
            $compressedPath = $this->attemptCompression($sourcePath, $downloadName);

            // Jika kompresi sukses, download file kompresi
            if ($compressedPath) {
                return response()->download($compressedPath, $downloadName)->deleteFileAfterSend(true);
            }
        }

        // Fallback ke download asli jika tidak perlu atau gagal kompresi
        return response()->download($sourcePath, $downloadName);
    }

    /**
     * PRIVATE HELPER: Logika Kompresi iLovePDF
     */
    private function attemptCompression($sourcePath, $downloadName)
    {
        try {
            $publicKey = env('ILOVEPDF_PUBLIC_KEY');
            $secretKey = env('ILOVEPDF_SECRET_KEY');

            if (!$publicKey || !$secretKey) {
                return null;
            }

            $ilovepdf = new Ilovepdf($publicKey, $secretKey);
            $myTask = $ilovepdf->newTask('compress');
            $myTask->addFile($sourcePath);
            $myTask->setOutputFilename('compressed_' . $downloadName);
            $myTask->execute();

            $tempDir = storage_path('app/temp');
            if (!File::exists($tempDir)) {
                File::makeDirectory($tempDir, 0755, true);
            }

            $myTask->download($tempDir);
            
            // Cek variasi nama file output
            $possiblePaths = [
                $tempDir . '/compressed_' . $downloadName,
                $tempDir . '/compressed_' . $downloadName . '.pdf'
            ];

            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    return $path; // Berhasil menemukan file hasil kompresi
                }
            }

        } catch (\Exception $e) {
            // Log error tanpa menghentikan proses utama
            \Log::warning('Gagal kompres iLovePDF: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * PRIVATE HELPER: Mencari lokasi fisik file secara cerdas
     */
    private function findPhysicalPath($relativePath)
    {
        if (Storage::exists($relativePath)) {
            return Storage::path($relativePath);
        }

        $alternatives = [
            storage_path('app/' . $relativePath),
            storage_path('app/public/' . $relativePath),
            storage_path('app/private/' . $relativePath),
            public_path('storage/' . $relativePath),
        ];

        foreach ($alternatives as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        abort(404, 'File fisik tidak ditemukan di server.');
    }
}
