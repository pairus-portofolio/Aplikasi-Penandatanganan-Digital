<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Document;
use App\Models\WorkflowStep;
use App\Http\Controllers\Dashboard\TableController;

class CardsController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $roleName = $user->role->nama_role ?? '';

        // --- LOGIKA 1: CARDS (BERFUNGSI REAL-TIME) ---
        // Inisialisasi variabel kartu
        $suratKeluarCount = 0; // Untuk TU
        $suratPerluReview = 0; // Untuk Kaprodi
        $suratPerluParaf  = 0; // Untuk Kaprodi
        $suratPerluTtd    = 0; // Untuk Kajur/Sekjur

        // Logika TU
        if ($roleName === 'TU') {
            $suratKeluarCount = Document::count();
        } 
        
        // Logika Kaprodi (D3 & D4)
        elseif (in_array($roleName, ['Kaprodi D3', 'Kaprodi D4'])) {
            $totalPending = WorkflowStep::where('user_id', $user->id)
                                        ->where('status', 'Ditinjau')
                                        ->count();
            
            $suratPerluReview = $totalPending;
            $suratPerluParaf  = $totalPending;
        } 
        
        // Logika Kajur & Sekjur
        elseif (in_array($roleName, ['Kajur', 'Sekjur'])) {
            $suratPerluTtd = WorkflowStep::where('user_id', $user->id)
                                        ->where('status', 'Ditinjau')
                                        ->count();
        }


        // --- LOGIKA 2: TABEL (Diserahkan ke teman Anda) ---
        // Kita TIDAK mengirim variabel $daftarSurat.
        // Dengan begini, index.blade.php akan otomatis pakai data dummy/palsu 
        // yang sudah ada di kodingan HTML-nya sebagai tampilan contoh.
        
        $daftarSurat = TableController::getData();

        return view('dashboard.index', compact(
            'suratKeluarCount',
            'suratPerluReview',
            'suratPerluParaf',
            'suratPerluTtd',
            'daftarSurat'
            // 'daftarSurat' <-- HAPUS INI agar tabel memunculkan data dummy
        ));
    } 
}