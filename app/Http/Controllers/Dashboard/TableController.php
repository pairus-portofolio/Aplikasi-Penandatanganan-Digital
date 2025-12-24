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

        // LOGIKA PERBAIKAN:
        // Jika user BUKAN TU dan BUKAN DOSEN, maka batasi hak aksesnya.
        // Artinya: Role TU dan DOSEN diperbolehkan melihat SEMUA surat di tabel.
        if ($roleName !== RoleEnum::TU && $roleName !== RoleEnum::DOSEN) {
            $query->where(function ($q) use ($user) {
                $q->whereHas('workflowSteps', function ($subQ) use ($user) {
                    $subQ->where('user_id', $user->id);
                })
                ->orWhere('id_user_uploader', $user->id);
            });
        }

        if ($search) {
            $searchTerm = '%' . strtolower($search) . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->whereRaw('LOWER(judul_surat) LIKE ?', [$searchTerm])
                    ->orWhereHas('uploader', function ($u) use ($searchTerm) {
                        $u->whereRaw('LOWER(nama_lengkap) LIKE ?', [$searchTerm]);
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

    public static function getData()
    {
        $documents = self::getBaseQueryByRole();
        return self::formatSuratForTable($documents);
    }

    private static function formatSuratForTable($paginator)
    {
        $user = Auth::user();
        $roleName = $user->role->nama_role ?? '';
        $isTU = $roleName === RoleEnum::TU;
        
        $paginator->through(function ($doc) use ($user, $isTU, $roleName) {

            $statusClass = match ($doc->status) {
                DocumentStatusEnum::DITINJAU => 'kuning',
                DocumentStatusEnum::DIPARAF => 'biru',
                DocumentStatusEnum::DITANDATANGANI => 'hijau',
                DocumentStatusEnum::PERLU_REVISI => 'merah',
                DocumentStatusEnum::FINAL => 'abu',
                default => 'abu',
            };

            $isUploader = $doc->id_user_uploader == $user->id;
            $revisionUrl = null;

            // Revisi hanya untuk TU atau Pengunggah asli
            if (($isTU || $isUploader) && $doc->status === DocumentStatusEnum::PERLU_REVISI) {
                $revisionUrl = route('tu.upload.create', ['id' => $doc->id]);
            }

            $actionData = self::determineActionType($doc, $user);

            $tanggalTampil = $doc->created_at->format('d/m/Y');
            if ($doc->tanggal_surat) {
                try {
                    $tanggalTampil = Carbon::parse($doc->tanggal_surat)->format('d/m/Y');
                } catch (\Exception $e) {}
            }

            return [
                'id_raw' => $doc->id,
                'nama' => $doc->judul_surat,
                'pengunggah' => $doc->uploader->nama_lengkap ?? 'Tidak Diketahui',
                'tanggal' => $tanggalTampil,
                'status' => ucfirst($doc->status),
                'revision_url' => $revisionUrl,
                'status_class' => $statusClass,
                'action_type' => $actionData['type'],
                'action_url' => $actionData['url'],
                'action_label' => $actionData['label'],
                'action_class' => $actionData['class']
            ];
        });

        return $paginator;
    }

    private static function determineActionType($doc, $user)
    {
        $role = $user->role->nama_role;
        $isActive = self::isUserActiveInWorkflow($doc, $user->id);

        $type = 'view';
        $label = 'Lihat';
        $class = 'btn-secondary';

        // Routing URL dasar berdasarkan Role
        if (in_array($role, RoleEnum::getKoordinatorRoles())) {
            $baseUrl = route('kaprodi.paraf.show', $doc->id);
        } elseif (in_array($role, RoleEnum::getKajurSekjurRoles())) {
            $baseUrl = route('kajur.tandatangan.show', $doc->id);
        } else {
            // Default untuk TU dan Dosen (Hanya Melihat)
            $baseUrl = route('tu.document.show', $doc->id);
        }

        // Jika giliran user ini untuk memproses (Paraf/TTD)
        // Dosen tidak akan pernah masuk ke sini karena isActive pasti false
        if ($isActive && $role !== RoleEnum::TU) {
            $type = 'work';
            $label = 'Proses';
            $class = 'btn-primary';
        }

        return [
            'type' => $type,
            'url' => $baseUrl,
            'label' => $label,
            'class' => $class
        ];
    }
}