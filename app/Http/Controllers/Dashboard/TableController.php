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
        
        // Ubah pengecekan dari 'nama_role' menjadi 'role_id'
        $roleId = $user->role_id; 
        
        $rawDocs = collect([]);

        // 1. Logika TU (ID: 1)
        if ($roleId == 1) {
            $rawDocs = Document::with('uploader')->latest()->get();
        } 
        
        // 2. Logika Kaprodi D3 (ID: 2) & D4 (ID: 3)
        elseif (in_array($roleId, [2, 3])) {
            $rawDocs = Document::with('uploader')->whereHas('workflowSteps', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })->latest()->get();
        } 
        
        // 3. Logika Kajur (ID: 4) & Sekjur (ID: 5)
        elseif (in_array($roleId, [4, 5])) {
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