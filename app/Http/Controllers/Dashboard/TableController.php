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
        // Load relationship role agar bisa cek nama_role
        $user = Auth::user();
        
        // Pastikan role terload
        if (!$user->relationLoaded('role')) {
            $user->load('role');
        }

        $roleName = $user->role->nama_role ?? '';

        // Ambil parameter search & filter
        $search = request('search');
        $status = request('status');

        // Base Query
        $query = Document::with(['uploader', 'workflowSteps.user']);

        // Filter Role Logic
        if ($roleName !== RoleEnum::TU) {
            // Role Lain (Kaprodi, Kajur, Sekjur) → user harus ada di workflow
            $query->whereHas('workflowSteps', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        // 1. Search Logic (Judul atau Nama Pengunggah)
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('judul_surat', 'like', '%' . $search . '%')
                  ->orWhereHas('uploader', function($u) use ($search) {
                      $u->where('nama_lengkap', 'like', '%' . $search . '%');
                  });
            });
        }

        // 2. Filter Status
        if ($status) {
            $query->where('status', $status);
        }

        // Return Paginated with Query String (agar search tidak hilang saat ganti halaman)
        return $query->latest()
            ->paginate(10)
            ->withQueryString();
    }

    /**
     * Query untuk card - menampilkan surat yang harus dikerjakan user saat ini.
     * Hanya surat dengan urutan aktif dan status sesuai.
     */
    public static function getActiveTasksQueryByRole()
    {
        $user = Auth::user();
        
        if (!$user->relationLoaded('role')) {
            $user->load('role');
        }

        $roleName = $user->role->nama_role ?? '';

        // 1. TU → semua dokumen (untuk card surat keluar)
        if ($roleName === RoleEnum::TU) {
            return Document::with('uploader')->latest()->paginate(10);
        }

        // 2. Role Lain (Kaprodi, Kajur, Sekjur)
        // Dokumen dianggap "Active Task" jika:
        // - User ada di workflow step
        // - Step user statusnya DITINJAU
        // - User adalah urutan terendah (min) dari semua step yang statusnya DITINJAU
        
        return Document::with(['uploader', 'workflowSteps'])
            ->whereHas('workflowSteps', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->where('status', DocumentStatusEnum::DITINJAU);
            })
            // Filter tambahan untuk memastikan user adalah active step
            ->get() // Kita ambil collection dulu untuk filter logic yang kompleks
            ->filter(function ($doc) use ($user) {
                return self::isUserActiveInWorkflow($doc, $user->id);
            });
    }

    /**
     * Helper untuk mengecek apakah user adalah active step di workflow dokumen
     */
    public static function isUserActiveInWorkflow($document, $userId)
    {
        // Tidak ada user yang boleh 'Kerjakan' saat status revisi.
        if ($document->status === DocumentStatusEnum::PERLU_REVISI) {
            return false;
        }

        // Ambil semua step yang statusnya DITINJAU
        $pendingSteps = $document->workflowSteps->where('status', DocumentStatusEnum::DITINJAU);

        if ($pendingSteps->isEmpty()) {
            return false;
        }

        // Cari urutan terkecil
        $minUrutan = $pendingSteps->min('urutan');

        // Cek apakah user memiliki step dengan urutan terkecil tersebut
        // dan status step user tersebut juga DITINJAU
        $userStep = $pendingSteps->where('user_id', $userId)->first();

        return $userStep && $userStep->urutan === $minUrutan;
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
        $user = Auth::user();
        $isTU = $user->role->nama_role === RoleEnum::TU;

        // Gunakan through() untuk memodifikasi item di dalam paginator
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
            
            // Jika user adalah TU DAN statusnya Perlu Revisi
            if ($isTU && $doc->status === DocumentStatusEnum::PERLU_REVISI) {
                // Buat link ke halaman upload dengan membawa ID (Pintu Revisi)
                $revisionUrl = route('tu.upload.create', ['id' => $doc->id]);
            }

            // Tentukan Action Type
            $actionData = self::determineActionType($doc, $user);

            // [FIX BUG 1] Prioritaskan tanggal_surat (inputan user) daripada created_at
            $tanggalTampil = $doc->created_at->format('d/m/Y'); // Default fallback
            if ($doc->tanggal_surat) {
                try {
                    $tanggalTampil = Carbon::parse($doc->tanggal_surat)->format('d/m/Y');
                } catch (\Exception $e) {
                    // Fallback jika format error
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
     * Menentukan tipe aksi, URL, dan label tombol berdasarkan kondisi dokumen dan user
     */
    private static function determineActionType($doc, $user)
    {
        $isTU = $user->role->nama_role === RoleEnum::TU;
        $isActive = self::isUserActiveInWorkflow($doc, $user->id);
        
        // Default: View Only
        $type = 'view';
        $label = 'Lihat';
        $class = 'btn-secondary'; // Abu-abu atau outline
        
        // Tentukan Base URL berdasarkan Role
        $baseUrl = '#';
        
        if (in_array($user->role->nama_role, RoleEnum::getKaprodiRoles())) {
            // Jika aktif -> ke halaman paraf
            // Jika view -> ke halaman review
            $baseUrl = $isActive 
                ? route('kaprodi.paraf.show', $doc->id) 
                : route('kaprodi.review.show', $doc->id);

        } elseif (in_array($user->role->nama_role, RoleEnum::getKajurSekjurRoles())) {
             // Kajur/Sekjur menggunakan controller yang sama, nanti kita sesuaikan logic di controller-nya
             $baseUrl = route('kajur.tandatangan.show', $doc->id);
        } else {
            // TU atau lainnya
             // Arahkan ke halaman view-only TU
             $baseUrl = route('tu.document.show', $doc->id); 
        }

        // Jika User Aktif (Giliran dia) -> Work Mode
        if ($isActive && !$isTU) {
            $type = 'work';
            $label = 'Kerjakan';
            $class = 'btn-primary';
            
            // Override URL jika perlu (sebenarnya logic di atas sudah handle, tapi untuk konsistensi)
            if (in_array($user->role->nama_role, RoleEnum::getKaprodiRoles())) {
                $baseUrl = route('kaprodi.paraf.show', $doc->id);
            } elseif (in_array($user->role->nama_role, RoleEnum::getKajurSekjurRoles())) {
                $baseUrl = route('kajur.tandatangan.show', $doc->id);
            }
        }

        return [
            'type'  => $type,
            'url'   => $baseUrl,
            'label' => $label,
            'class' => $class
        ];
    }
}