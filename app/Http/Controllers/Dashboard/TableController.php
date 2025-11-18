<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Document;

class TableController extends Controller
{
    public static function getData()
    {
        $user = Auth::user();
        $roleName = $user->role->nama_role ?? '';
        $rawDocs = collect([]);

        // Pastikan menggunakan 'with' untuk mengambil data relasi uploader agar lebih efisien
        if ($roleName === 'TU') {
            $rawDocs = Document::with('uploader')->latest()->get();
        } elseif (in_array($roleName, ['Kaprodi D3', 'Kaprodi D4'])) {
            $rawDocs = Document::with('uploader')->whereHas('workflowSteps', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })->latest()->get();
        } elseif (in_array($roleName, ['Kajur', 'Sekjur'])) {
            $rawDocs = Document::with('uploader')->whereHas('workflowSteps', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })->latest()->get();
        }

        return self::formatSuratForTable($rawDocs);
    }

    private static function formatSuratForTable($documents)
    {
        return $documents->map(function($doc) {
            $statusClass = match($doc->status) {
                'completed', 'signed', 'Ditandatangani' => 'hijau',
                'Ditinjau', 'Diparaf' => 'kuning',
                'revisi', 'Perlu Revisi' => 'merah',
                default => 'biru',
            };

            return [
                'id_raw'       => $doc->id,
                'nama'         => $doc->judul_surat,
                'pengunggah'   => $doc->uploader->nama_lengkap ?? 'Tidak Diketahui', 
                'tanggal'      => $doc->created_at->format('d/m/Y'), 
                'status'       => ucfirst($doc->status),
                'status_class' => $statusClass
            ];
        });
    }
}