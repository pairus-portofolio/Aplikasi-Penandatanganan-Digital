<?php

namespace App\Http\Controllers\Kaprodi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Document;
use App\Http\Controllers\Dashboard\TableController; 

class ParafController extends Controller
{
    // 1. Halaman Tabel Daftar Surat 
    public function index()
    {
        $daftarSurat = TableController::getData();
        return view('kaprodi.paraf.index', compact('daftarSurat'));
    }

    // 2. Halaman Detail (Tempat melakukan Paraf)
    public function show($id)
    {
        $document = Document::findOrFail($id);
        
        // Mengembalikan view detail yang sudah ada sebelumnya
        return view('kaprodi.paraf-surat', compact('document'));
    }
}