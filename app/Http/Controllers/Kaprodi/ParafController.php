<?php

namespace App\Http\Controllers\Kaprodi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Document;
use App\Models\WorkflowStep;
use Illuminate\Support\Facades\Auth;

class ParafController extends Controller
{
    // 1. Halaman Tabel Daftar Surat 
    public function index()
    {
        $user = Auth::user();
        
        // Ambil tugas paraf (status 'Diparaf' atau sesuai logika kamu)
        // Asumsi: Status 'Diparaf' artinya sudah direview dan siap diparaf
        $daftarTugas = WorkflowStep::where('user_id', $user->id)
                                   ->where('status', 'Ditinjau', 'Diparaf') 
                                   ->with('document.uploader')
                                   ->orderBy('created_at', 'desc')
                                   ->get();

        return view('kaprodi.paraf.index', compact('daftarTugas'));
    }

    // 2. Halaman Detail
    public function show($id)
    {
        $document = Document::findOrFail($id);
        // Kita kirim sebagai 'document' agar konsisten
        return view('kaprodi.paraf-surat', compact('document'));
    }
}