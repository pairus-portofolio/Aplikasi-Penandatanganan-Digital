<?php

namespace App\Http\Controllers\Kajur_Sekjur;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Document;
use App\Http\Controllers\Dashboard\TableController; // Panggil Logika Tabel Pusat

class TandatanganController extends Controller
{
    // 1. Halaman Tabel Daftar Surat (List)
    public function index()
    {
        // Ambil data dari TableController (sudah otomatis filter punya Kajur/Sekjur)
        $daftarSurat = TableController::getData();

        // Tampilkan view tabel baru
        return view('kajur_sekjur.tandatangan.index', compact('daftarSurat'));
    }

    // 2. Halaman Detail (Tempat Tanda Tangan)
    public function show($id)
    {
        $document = Document::findOrFail($id);
        
        // Kembalikan ke view detail/tanda tangan yang lama
        return view('kajur_sekjur.tandatangan-surat', compact('document'));
    }
}