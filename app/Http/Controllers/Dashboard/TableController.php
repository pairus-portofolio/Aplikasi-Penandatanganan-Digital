<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Document;
use App\Models\WorkflowStep;
use App\Enums\DocumentStatusEnum;
use App\Enums\RoleEnum;
use Carbon\Carbon;

/**
 * Controller untuk mengelola data tabel dokumen di dashboard.
 *
 * Menyediakan query dan format data untuk tabel dokumen berdasarkan role user,
 * termasuk filtering, search, dan penentuan action button.
 *
 * @package App\Http\Controllers\Dashboard
 */
class TableController extends Controller
{
    /**
     * Dapatkan query dasar untuk tabel dokumen berdasarkan role user.
     *
     * Menampilkan semua dokumen yang user terlibat dalam workflow-nya,
     * dengan fitur search dan filter status.
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator
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

    /**
     * Dapatkan query untuk dokumen yang harus dikerjakan user (active tasks).
     *
     * Hanya menampilkan dokumen yang sedang dalam giliran user untuk dikerjakan.
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator|\Illuminate\Support\Collection
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
     * Cek apakah user adalah active step dalam workflow dokumen.
     *
     * User dianggap active jika memiliki step dengan status DITINJAU
     * dan berada di urutan paling kecil (giliran pertama yang pending).
     *
     * @param \App\Models\Document $document Dokumen yang dicek
     * @param int $userId ID user yang dicek
     * @return bool True jika user adalah active step
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
     * Dapatkan data yang sudah diformat untuk ditampilkan di tabel.
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public static function getData()
    {
        $documents = self::getBaseQueryByRole();
        return self::formatSuratForTable($documents);
    }

    /**
     * Format data dokumen untuk ditampilkan di tabel.
     *
     * Menambahkan informasi status, action button, dan format tanggal.
     *
     * @param \Illuminate\Pagination\LengthAwarePaginator $paginator Data dokumen
     * @return \Illuminate\Pagination\LengthAwarePaginator Data yang sudah diformat
     */
    private static function formatSuratForTable($paginator)
    {
        $user = Auth::user();
        $isTU = $user->role->nama_role === RoleEnum::TU;

        $paginator->through(function ($doc) use ($user, $isTU) {

            $statusClass = match ($doc->status) {
                DocumentStatusEnum::DITINJAU => 'kuning',
                DocumentStatusEnum::DIPARAF => 'biru',
                DocumentStatusEnum::DITANDATANGANI => 'hijau',
                DocumentStatusEnum::PERLU_REVISI => 'merah',
                DocumentStatusEnum::FINAL => 'abu',
                default => 'abu',
            };

            $revisionUrl = null;

            if ($isTU && $doc->status === DocumentStatusEnum::PERLU_REVISI) {
                $revisionUrl = route('tu.upload.create', ['id' => $doc->id]);
            }

            $actionData = self::determineActionType($doc, $user);

            $tanggalTampil = $doc->created_at->format('d/m/Y');

            if ($doc->tanggal_surat) {
                try {
                    $tanggalTampil = Carbon::parse($doc->tanggal_surat)->format('d/m/Y');
                } catch (\Exception $e) {
                    // Fallback ke created_at jika tanggal_surat invalid
                }
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

    /**
     * Tentukan tipe action button berdasarkan role dan status workflow.
     *
     * @param \App\Models\Document $doc Dokumen yang dicek
     * @param \App\Models\User $user User yang sedang login
     * @return array Array berisi type, url, label, dan class untuk action button
     */
    private static function determineActionType($doc, $user)
    {
        $role = $user->role->nama_role;
        $isTU = $role === RoleEnum::TU;
        $isActive = self::isUserActiveInWorkflow($doc, $user->id);

        $type = 'view';
        $label = 'Lihat';
        $class = 'btn-secondary';
        if (in_array($role, RoleEnum::getKaprodiRoles())) {

            $baseUrl = route('kaprodi.paraf.show', $doc->id);

        } elseif (in_array($role, RoleEnum::getKajurSekjurRoles())) {

            $baseUrl = route('kajur.tandatangan.show', $doc->id);

        } else {

            $baseUrl = route('tu.document.show', $doc->id);
        }
        if ($isActive && !$isTU) {
            $type = 'work';
            $label = 'Kerjakan';
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
