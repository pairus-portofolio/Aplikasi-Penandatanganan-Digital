<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Document;
use App\Models\WorkflowStep;
use App\Enums\DocumentStatusEnum;
use App\Enums\RoleEnum;
use Carbon\Carbon;

class TableController extends Controller
{
    /**
     * Query untuk tabel - menampilkan semua surat yang user ada di workflow-nya.
     * Tidak memandang status atau urutan.
     */
    public static function getBaseQueryByRole()
    {
        $user = Auth::user();

        if (!$user->relationLoaded('role')) {
            $user->load('role');
        }

        $roleName = $user->role->nama_role ?? '';

        $search = request('search');
        $status = request('status');

        $query = Document::with(['uploader', 'workflowSteps.user']);

        if ($roleName !== RoleEnum::TU) {
            $query->whereHas('workflowSteps', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('judul_surat', 'like', "%$search%")
                  ->orWhereHas('uploader', function ($u) use ($search) {
                      $u->where('nama_lengkap', 'like', "%$search%");
                  });
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        return $query->latest()
            ->paginate(10)
            ->withQueryString();
    }

    /**
     * Query untuk card - menampilkan surat yang harus dikerjakan user saat ini.
     */
    public static function getActiveTasksQueryByRole()
    {
        $user = Auth::user();

        if (!$user->relationLoaded('role')) {
            $user->load('role');
        }

        $roleName = $user->role->nama_role ?? '';

        if ($roleName === RoleEnum::TU) {
            return Document::with('uploader')->latest()->paginate(10);
        }

        return Document::with(['uploader', 'workflowSteps'])
            ->whereHas('workflowSteps', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->where('status', DocumentStatusEnum::DITINJAU);
            })
            ->get()
            ->filter(function ($doc) use ($user) {
                return self::isUserActiveInWorkflow($doc, $user->id);
            });
    }

    /**
     * Menentukan apakah user adalah active step.
     */
    public static function isUserActiveInWorkflow($document, $userId)
    {
        if ($document->status === DocumentStatusEnum::PERLU_REVISI) {
            return false;
        }

        $pendingSteps = $document->workflowSteps->where('status', DocumentStatusEnum::DITINJAU);

        if ($pendingSteps->isEmpty()) {
            return false;
        }

        $minUrutan = $pendingSteps->min('urutan');

        $userStep = $pendingSteps->where('user_id', $userId)->first();

        return $userStep && $userStep->urutan === $minUrutan;
    }

    /**
     * Return data untuk tabel.
     */
    public static function getData()
    {
        $documents = self::getBaseQueryByRole();
        return self::formatSuratForTable($documents);
    }

    private static function formatSuratForTable($paginator)
    {
        $user = Auth::user();
        $isTU = $user->role->nama_role === RoleEnum::TU;

        $paginator->through(function ($doc) use ($user, $isTU) {

            $statusClass = match ($doc->status) {
                DocumentStatusEnum::DITINJAU       => 'kuning',
                DocumentStatusEnum::DIPARAF        => 'biru',
                DocumentStatusEnum::DITANDATANGANI => 'hijau',
                DocumentStatusEnum::PERLU_REVISI   => 'merah',
                DocumentStatusEnum::FINAL          => 'abu',
                default                            => 'abu',
            };

            $revisionUrl = null;

            if ($isTU && $doc->status === DocumentStatusEnum::PERLU_REVISI) {
                $revisionUrl = route('tu.upload.create', ['id' => $doc->id]);
            }

            $actionData = self::determineActionType($doc, $user);

            // format tanggal
            $tanggalTampil = $doc->created_at->format('d/m/Y');

            if ($doc->tanggal_surat) {
                try {
                    $tanggalTampil = Carbon::parse($doc->tanggal_surat)->format('d/m/Y');
                } catch (\Exception $e) {
                    // tanggal_surat invalid → fallback ke created_at
                }
            }

            return [
                'id_raw'       => $doc->id,
                'nama'         => $doc->judul_surat,
                'pengunggah'   => $doc->uploader->nama_lengkap ?? 'Tidak Diketahui',
                'tanggal'      => $tanggalTampil,
                'status'       => ucfirst($doc->status),
                'revision_url' => $revisionUrl,
                'status_class' => $statusClass,
                'action_type'  => $actionData['type'],
                'action_url'   => $actionData['url'],
                'action_label' => $actionData['label'],
                'action_class' => $actionData['class']
            ];
        });

        return $paginator;
    }

    /**
     * Menentukan action berdasarkan role & workflow.
     */
    private static function determineActionType($doc, $user)
    {
        $role = $user->role->nama_role;
        $isTU = $role === RoleEnum::TU;
        $isActive = self::isUserActiveInWorkflow($doc, $user->id);

        // Default
        $type  = 'view';
        $label = 'Lihat';
        $class = 'btn-secondary';

        // Tentukan base URL tanpa useless assignment
        if (in_array($role, RoleEnum::getKaprodiRoles())) {

            $baseUrl = route('kaprodi.paraf.show', $doc->id);

        } elseif (in_array($role, RoleEnum::getKajurSekjurRoles())) {

            $baseUrl = route('kajur.tandatangan.show', $doc->id);

        } else {

            $baseUrl = route('tu.document.show', $doc->id);
        }

        // Jika giliran user (active step) dan bukan TU → Kerjakan
        if ($isActive && !$isTU) {
            $type  = 'work';
            $label = 'Kerjakan';
            $class = 'btn-primary';
        }

        return [
            'type'  => $type,
            'url'   => $baseUrl,
            'label' => $label,
            'class' => $class
        ];
    }
}
