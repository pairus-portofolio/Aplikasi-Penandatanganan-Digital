<?php

namespace App\Http\Controllers\Tu;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Enums\DocumentStatusEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Ilovepdf\Ilovepdf;

/**
 * Controller untuk mengelola finalisasi dokumen yang sudah ditandatangani.
 *
 * Menangani proses finalisasi dokumen dari status DITANDATANGANI menjadi FINAL,
 * dengan fitur preview dan download dengan kompresi otomatis.
 *
 * @package App\Http\Controllers\Tu
 */
class FinalisasiController extends Controller
{
    /**
     * Tampilkan daftar dokumen yang siap difinalisasi.
     *
     * @return \Illuminate\View\View
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
     * Tampilkan halaman preview dokumen sebelum finalisasi.
     *
     * @param int $id ID dokumen
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $document = Document::with('uploader')->findOrFail($id);

        return view('Tu.finalisasi.show', compact('document'));
    }
    /**
     * Preview PDF secara inline di browser.
     *
     * @param int $id ID dokumen
     * @return \Illuminate\Http\Response
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
     * Ubah status dokumen menjadi FINAL.
     *
     * @param Request $request HTTP request
     * @param int $id ID dokumen
     * @return \Illuminate\Http\RedirectResponse
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
     * Download file PDF dengan kompresi otomatis untuk file > 1MB.
     *
     * @param int $id ID dokumen
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download($id)
    {
        $document = Document::findOrFail($id);
        $sourcePath = $this->findPhysicalPath($document->file_path);
        $downloadName = $document->judul_surat . '.pdf';
        
        $oneMB = 1048576;

        if (filesize($sourcePath) > $oneMB) {
            
            $compressedPath = $this->attemptCompression($sourcePath, $downloadName);

            if ($compressedPath) {
                return response()->download($compressedPath, $downloadName)->deleteFileAfterSend(true);
            }
        }

        return response()->download($sourcePath, $downloadName);
    }
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
