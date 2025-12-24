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

        $docs = TableController::getActiveTasksQueryByRole();

        $suratKeluarCount = ($roleName === RoleEnum::TU)
            ? $docs->total()
            : 0;

        // PERBAIKAN: Gunakan getKoordinatorRoles()
        if (in_array($roleName, RoleEnum::getKoordinatorRoles())) {

            $suratPerluParaf = $docs->filter(function ($doc) use ($user) {

                $activeStep = WorkflowStep::where('document_id', $doc->id)
                    ->where('status', DocumentStatusEnum::DITINJAU)
                    ->orderBy('urutan')
                    ->first();

                return $activeStep && $activeStep->user_id == $user->id;

            })->count();

            $suratPerluReview = $suratPerluParaf;

        } else {
            $suratPerluParaf = 0;
            $suratPerluReview = 0;
        }

        if (in_array($roleName, RoleEnum::getKajurSekjurRoles())) {

            $suratPerluTtd = $docs->filter(function ($doc) use ($user) {

                $activeStep = WorkflowStep::where('document_id', $doc->id)
                    ->where('status', DocumentStatusEnum::DITINJAU)
                    ->orderBy('urutan')
                    ->first();

                return $activeStep && $activeStep->user_id == $user->id;

            })->count();

        } else {
            $suratPerluTtd = 0;
        }

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