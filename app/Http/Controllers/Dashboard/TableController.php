<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Document;
use App\Models\WorkflowStep;

class TableController extends Controller
{
    /**
     * Query dasar berdasarkan role user.
     */
    public static function getBaseQueryByRole()
    {
        $user = Auth::user();
        $roleId = $user->role_id;

        // 1. TU → semua dokumen (Collection)
        if ($roleId == 1) {
            return Document::with('uploader')->latest()->get();
        }

        // 2. Kaprodi → dokumen yang ada di workflow, tapi hanya jika dia urutan aktif
        if (in_array($roleId, [2,3])) {

            $docs = Document::with('uploader')
                ->whereHas('workflowSteps', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->latest()
                ->get();

            return $docs->filter(function ($doc) use ($user) {

                $activeStep = WorkflowStep::where('document_id', $doc->id)
                    ->where('status', 'Ditinjau')
                    ->orderBy('urutan')
                    ->first();

                if (!$activeStep) return false;

                return $activeStep->user_id == $user->id;
            });
        }

        // 3. Kajur / Sekjur → hanya dokumen Diparaf, dan jika dia urutan aktif
        if (in_array($roleId, [4,5])) {

            $docs = Document::with('uploader')
                ->where('status', 'Diparaf')
                ->whereHas('workflowSteps', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->latest()
                ->get();

            return $docs->filter(function ($doc) use ($user) {
                $activeStep = WorkflowStep::where('document_id', $doc->id)
                    ->where('status', 'Ditinjau')
                    ->orderBy('urutan')
                    ->first();

                if (!$activeStep) return false;

                return $activeStep->user_id == $user->id;
            });
        }

        // Default → empty collection
        return collect();
    }


    /**
     * Digunakan oleh tabel HTML (formatting).
     */
    public static function getData()
    {
        $documents = self::getBaseQueryByRole();

        return self::formatSuratForTable($documents);
    }

    private static function formatSuratForTable($documents)
    {
        return $documents->map(function ($doc) {

            $statusClass = match ($doc->status) {
                'Ditandatangani' => 'hijau',
                'Ditinjau', 'Diparaf' => 'kuning',
                'Perlu Revisi' => 'merah',
                default => 'biru',
            };

            return [
                'id_raw'       => $doc->id,
                'nomor'        => 'SRT-' . str_pad($doc->id, 4, '0', STR_PAD_LEFT),
                'nama'         => $doc->judul_surat,
                'pengunggah'   => $doc->uploader->nama_lengkap ?? 'Tidak Diketahui',
                'tanggal'      => $doc->created_at->format('d/m/Y'),
                'status'       => ucfirst($doc->status),
                'status_class' => $statusClass
            ];
        });
    }
}
