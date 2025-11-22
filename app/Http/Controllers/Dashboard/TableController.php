<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Document;
use App\Models\WorkflowStep;
use App\Enums\DocumentStatusEnum;
use App\Enums\RoleEnum;

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
        if ($roleId == RoleEnum::ID_TU) {
            return Document::with('uploader')->latest()->paginate(10);
        }

        // 2. Kaprodi → dokumen yang ada di workflow, tapi hanya jika dia urutan aktif
        if (in_array($roleId, [RoleEnum::ID_KAPRODI_D3, RoleEnum::ID_KAPRODI_D4])) {
            return Document::with('uploader')
                ->whereHas('workflowSteps', function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                      ->where('status', DocumentStatusEnum::DITINJAU)
                      ->whereRaw('urutan = (
                          SELECT MIN(urutan) 
                          FROM workflow_steps as w2 
                          WHERE w2.document_id = workflow_steps.document_id 
                          AND w2.status = ?
                      )', [DocumentStatusEnum::DITINJAU]);
                })
                ->latest()
                ->paginate(10);
        }

        // 3. Kajur / Sekjur → hanya dokumen Diparaf, dan jika dia urutan aktif
        if (in_array($roleId, [RoleEnum::ID_KAJUR, RoleEnum::ID_SEKJUR])) {
            return Document::with('uploader')
                ->where('status', DocumentStatusEnum::DIPARAF)
                ->whereHas('workflowSteps', function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                      ->where('status', DocumentStatusEnum::DITINJAU)
                      ->whereRaw('urutan = (
                          SELECT MIN(urutan) 
                          FROM workflow_steps as w2 
                          WHERE w2.document_id = workflow_steps.document_id 
                          AND w2.status = ?
                      )', [DocumentStatusEnum::DITINJAU]);
                })
                ->latest()
                ->paginate(10);
        }

        // Default → empty paginator
        return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
    }


    /**
     * Digunakan oleh tabel HTML (formatting).
     */
    public static function getData()
    {
        $documents = self::getBaseQueryByRole();

        return self::formatSuratForTable($documents);
    }

    private static function formatSuratForTable($paginator)
    {
        // Gunakan through() untuk memodifikasi item di dalam paginator
        // tanpa mengubah objek Paginator itu sendiri (agar links() tetap jalan)
        $paginator->through(function ($doc) {

            $statusClass = match ($doc->status) {
                DocumentStatusEnum::DITINJAU       => 'kuning',
                DocumentStatusEnum::DIPARAF        => 'biru',
                DocumentStatusEnum::DITANDATANGANI => 'hijau',
                DocumentStatusEnum::PERLU_REVISI   => 'merah',
                default                            => 'abu', 
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

        return $paginator;
    }
}
