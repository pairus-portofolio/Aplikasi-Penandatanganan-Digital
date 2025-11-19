<?php

namespace App\Http\Controllers\Kaprodi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Document;
use App\Http\Controllers\Dashboard\TableController;

class ReviewController extends Controller
{
    // 1. Halaman LIST
    public function index()
    {
        $daftarSurat = TableController::getData();
        return view('kaprodi.review.index', compact('daftarSurat'));
    }

    // 2. Halaman DETAIL (lihat dokumen)
    public function show($id)
    {
        $document = Document::findOrFail($id);

        // Tidak ada pengecekan giliran di sini
        return view('kaprodi.review-surat', compact('document'));
    }
}
