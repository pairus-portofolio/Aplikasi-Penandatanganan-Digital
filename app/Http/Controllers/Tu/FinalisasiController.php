<?php

namespace App\Http\Controllers\Tu;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Enums\DocumentStatusEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

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
     * PREVIEW PDF di iframe
     */
    public function preview($id)
    {
        $document = Document::findOrFail($id);

        $filePath = $document->file_path;

        if (!Storage::exists($filePath)) {
            abort(404, 'File tidak ditemukan.');
        }

        $fileContent = Storage::get($filePath);
        
        return response()->make($fileContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$document->file_name.'"'
        ]);
    }

    /**
     * Finalisasi dokumen
     */
    public function store(Request $request, $id)
    {
        $document = Document::findOrFail($id);

        // hanya finalisasi (karena ga ada tombol revisi)
        $document->status = DocumentStatusEnum::FINAL;
        $document->save();

        // DIPENTINGKAN: kirim ID untuk popup
        return redirect()
            ->route('tu.finalisasi.index')
            ->with('success', 'Dokumen berhasil difinalisasi.')
            ->with('download_doc_id', $document->id);
    }

    
}
