<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Document;

class TableController extends Controller
{
    /**
     * Query dasar berdasarkan role user.
     * Digunakan oleh CardsController & Tabel.
     */
    public static function getBaseQueryByRole()
    {
        $user = Auth::user();
        $roleId = $user->role_id;

        // TU → lihat semua surat
        if ($roleId == 1) {
            return Document::with('uploader');
        }

        // Kaprodi (2,3) → lihat surat yg punya workflow untuk dia
        if (in_array($roleId, [2, 3])) {
            return Document::with('uploader')
                ->whereHas('workflowSteps', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
        }

        // Kajur / Sekjur (4,5)
        if (in_array($roleId, [4, 5])) {
            return Document::with('uploader')
                ->whereHas('workflowSteps', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->where('status', 'Diparaf'); // HANYA yang sudah diparaf Kaprodi
        }

        // Default fallback
        return Document::with('uploader')->limit(0);
    }

    /**
     * Digunakan oleh tabel HTML (formatting)
     */
    public static function getData()
    {
        return self::formatSuratForTable(
            self::getBaseQueryByRole()->latest()->get()
        );
    }

    private static function formatSuratForTable($documents)
    {
        return $documents->map(function ($doc) {
            $statusClass = match ($doc->status) {
                'completed', 'signed', 'Ditandatangani' => 'hijau',
                'Ditinjau', 'Diparaf' => 'kuning',
                'revisi', 'Perlu Revisi' => 'merah',
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
