<?php

namespace App\Http\Controllers\Kaprodi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Document;
use App\Http\Controllers\Dashboard\TableController; // Import Logic Tabel

class ReviewController extends Controller
{
    // 1. Halaman LIST (Tabel Fitur)
    public function index()
    {
        // Ambil data tabel dari Logic Pusat
        $daftarSurat = TableController::getData();

        // Tampilkan View Tabel Khusus Review
        return view('kaprodi.review.index', compact('daftarSurat'));
    }

    // 2. Halaman DETAIL (Proses Review Surat)
    public function show($id)
    {
        // Cari dokumen berdasarkan ID (Pastikan logic keamanan ditambahkan nanti)
        $document = Document::findOrFail($id);

        // Tampilkan halaman review yang ada preview gambarnya
        return view('kaprodi.review-surat', compact('document'));
    }
}