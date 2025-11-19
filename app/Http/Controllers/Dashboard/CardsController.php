<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\WorkflowStep;

class CardsController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $role = $user->role->nama_role ?? '';

        // Ambil query utama dari TableController → konsisten
        $mainQuery = TableController::getBaseQueryByRole();

        // CARD: Surat Keluar (TU)
        $suratKeluarCount = ($role === 'TU')
            ? $mainQuery->count()
            : 0;

        // CARD: Surat Perlu Review (Kaprodi)
        $suratPerluReview = in_array($role, ['Kaprodi D3', 'Kaprodi D4'])
            ? WorkflowStep::where('user_id', $user->id)
                ->where('status', 'Ditinjau')
                ->count()
            : 0;

        // CARD: Surat Perlu Paraf (Kaprodi)
        $suratPerluParaf = $suratPerluReview;

        // CARD: Surat Perlu TTD (Kajur/Sekjur)
        $suratPerluTtd = in_array($role, ['Kajur', 'Sekjur'])
            ? $mainQuery->count() // sudah otomatis status = Diparaf
            : 0;

        // Data tabel
        $daftarSurat = TableController::getData();

        return view('dashboard.index', compact(
            'suratKeluarCount',
            'suratPerluReview',
            'suratPerluParaf',
            'suratPerluTtd',
            'daftarSurat'
        ));
    }
}
