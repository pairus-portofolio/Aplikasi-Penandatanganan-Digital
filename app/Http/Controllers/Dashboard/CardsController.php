<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Document;
use App\Models\WorkflowStep;

class CardsController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $roleId = $user->role_id;

        // Ambil dokumen yang boleh dilihat user (sudah Collection!)
        $docs = TableController::getBaseQueryByRole();

        // =============================
        // CARD UNTUK ROLE TU
        // =============================
        $suratKeluarCount = ($roleId == 1)
            ? $docs->count()
            : 0;

        // =============================
        // CARD UNTUK KAPRODI (role 2, 3)
        // Dokumen hanya dihitung jika: 
        // - dia urutan aktif
        // - status step = Ditinjau
        // =============================
        if (in_array($roleId, [2,3])) {

            $suratPerluParaf = $docs->filter(function ($doc) use ($user) {

                $activeStep = WorkflowStep::where('document_id', $doc->id)
                    ->where('status', 'Ditinjau')
                    ->orderBy('urutan')
                    ->first();

                return $activeStep && $activeStep->user_id == $user->id;

            })->count();

            // REVIEW = sama dengan paraf (kaprodi review == kaprodi paraf)
            $suratPerluReview = $suratPerluParaf;

        } else {
            $suratPerluParaf = 0;
            $suratPerluReview = 0;
        }

        // =============================
        // CARD UNTUK KAJUR / SEKJUR
        // Dokumen dihitung jika:
        // - status dokumen = Diparaf
        // - dia adalah urutan aktif
        // =============================
        if (in_array($roleId, [4,5])) {

            $suratPerluTtd = $docs->filter(function ($doc) use ($user) {

                // Ambil step aktif
                $activeStep = WorkflowStep::where('document_id', $doc->id)
                    ->where('status', 'Ditinjau')
                    ->orderBy('urutan')
                    ->first();

                return $activeStep && $activeStep->user_id == $user->id;

            })->count();

        } else {
            $suratPerluTtd = 0;
        }

        // Data tabel utama
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
