<?php

namespace App\Http\Controllers\Kaprodi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Document;
use App\Models\WorkflowStep; 
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    // 1. Halaman LIST (Tabel Fitur)
    public function index()
    {
        $user = Auth::user();

        // Ambil tugas review dari database
        $daftarTugas = WorkflowStep::where('user_id', $user->id)
                                   ->whereIn('status', ['Ditinjau', 'Perlu Revisi'])
                                   ->with('document.uploader')
                                   ->orderBy('created_at', 'desc')
                                   ->get();

        // Kirim ke view dengan nama variabel yang benar
        return view('kaprodi.review.index', compact('daftarTugas'));
    }

    // 2. Halaman DETAIL (Proses Review Surat)
    public function show($id)
    {
        $document = Document::findOrFail($id);
        
        // Kirim variabel 'surat' agar sesuai dengan view 'kaprodi.review-surat'
        return view('kaprodi.review-surat', compact('document'));
    }
}