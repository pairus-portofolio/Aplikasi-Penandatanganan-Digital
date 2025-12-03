<?php

namespace App\Http\Controllers\Tu;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Enums\DocumentStatusEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Ilovepdf\Ilovepdf;

class ArsipController extends Controller
{
    /**
     * Menampilkan daftar arsip final dengan pencarian & pagination.
     */
    public function index(Request $request)
    {
        // Query dasar
        $documents = Document::query()
            ->where('status', DocumentStatusEnum::FINAL)
            ->with('uploader');

        // Logika pencarian (judul surat atau nama pengunggah)
        $documents->when($request->filled('search'), function ($query) use ($request) {
        $searchTerm = '%' . strtolower($request->search) . '%';

        $query->where(function ($subQuery) use ($searchTerm) {
            $subQuery->whereRaw('LOWER(judul_surat) LIKE ?', [$searchTerm])
                    ->orWhereHas('uploader', function ($q) use ($searchTerm) {
                        $q->whereRaw('LOWER(nama_lengkap) LIKE ?', [$searchTerm]);
                    });
        });
    });

        // Sorting & pagination
        $documents = $documents->orderBy('updated_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return view('Tu.arsip.index', compact('documents'));
    }

    /**
     * Menampilkan halaman detail arsip (full page).
     */
    public function show($id)
    {
        $document = Document::findOrFail($id);
        return view('Tu.arsip.show', compact('document'));
    }

    /**
     * Menampilkan preview file PDF secara inline.
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
     * Mengunduh file PDF.
     * Jika ukuran > 1MB, otomatis dikompres menggunakan iLovePDF.
     */
    public function download($id)
    {
        $document = Document::findOrFail($id);
        $sourcePath = $this->findPhysicalPath($document->file_path);
        $downloadName = $document->file_name ?? 'dokumen_arsip.pdf';

        $oneMB = 1048576;

        // Kompres jika perlu
        if (filesize($sourcePath) > $oneMB) {
            $compressedPath = $this->attemptCompression($sourcePath, $downloadName);

            if ($compressedPath) {
                return response()->download($compressedPath, $downloadName)->deleteFileAfterSend(true);
            }
        }

        // Fallback: file asli
        return response()->download($sourcePath, $downloadName);
    }

    /* =========================================================================
     * Helper Functions
     * ========================================================================= */

    /**
     * Mengompres PDF menggunakan iLovePDF.
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

            $possiblePaths = [
                $tempDir . '/compressed_' . $downloadName,
                $tempDir . '/compressed_' . $downloadName . '.pdf'
            ];

            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    return $path;
                }
            }
        } catch (\Exception $e) {
            \Log::warning('Gagal kompres arsip: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Mencari path fisik file pada berbagai lokasi (fallback anti 404).
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
