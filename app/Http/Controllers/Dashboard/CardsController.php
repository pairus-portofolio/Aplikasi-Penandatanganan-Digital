<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Document;
use App\Models\WorkflowStep;
use App\Enums\DocumentStatusEnum;
use App\Enums\RoleEnum;

class CardsController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        if (!$user->relationLoaded('role')) {
            $user->load('role');
        }
        $roleName = $user->role->nama_role ?? '';

        // Ambil dokumen yang harus dikerjakan user (urutan aktif)
        $docs = TableController::getActiveTasksQueryByRole();

        // =============================
        // CARD UNTUK ROLE TU
        // =============================
        // Fix: Gunakan total() karena $docs untuk TU adalah Paginator
        $suratKeluarCount = ($roleName === RoleEnum::TU)
            ? $docs->total()
            : 0;

        // =============================
        // CARD UNTUK KAPRODI (role 2, 3)
        // Dokumen hanya dihitung jika: 
        // - dia urutan aktif
        // - status step = Ditinjau
        // =============================
        if (in_array($roleName, RoleEnum::getKaprodiRoles())) {

            $suratPerluParaf = $docs->filter(function ($doc) use ($user) {

                $activeStep = WorkflowStep::where('document_id', $doc->id)
                    ->where('status', DocumentStatusEnum::DITINJAU)
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
        if (in_array($roleName, RoleEnum::getKajurSekjurRoles())) {

            $suratPerluTtd = $docs->filter(function ($doc) use ($user) {

                // Ambil step aktif
                $activeStep = WorkflowStep::where('document_id', $doc->id)
                    ->where('status', DocumentStatusEnum::DITINJAU)
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
