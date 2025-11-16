<?php

namespace App\Http\Controllers\Tu;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\WorkflowStep;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    /**
     * Tampilkan halaman form upload surat
     */
    public function create()
    {
        // Ambil semua user yang BUKAN TU untuk jadi pilihan alur
        $users = User::whereHas('role', function($query) {
            $query->where('nama_role', '!=', 'TU');
        })->get(['id', 'nama_lengkap']); // Ambil ID dan NAMA

        return view('Tu.upload', ['users' => $users]); // Kirim data users ke view
    }

    /**
     * Proses upload surat
     */
    public function store(Request $request)
    {
        // 1. Validasi input
        $validated = $request->validate([
            'judul_surat' => 'required|string|max:255',
            'file_surat'  => 'required|file|mimes:docx|max:2048',
            'kategori'    => 'required|string',
            'tanggal'     => 'required|date',
            'alur'        => 'required|string',
        ]);

        // 2. Ambil file dari request
        $file = $request->file('file_surat');
        $ext  = $file->getClientOriginalExtension();

        // 3. Generate nama file unik
        $filename = Str::uuid()->toString() . '.' . $ext;

        // 4. Simpan file ke storage/app/public/documents
        $filePath = $file->storeAs('documents', $filename);

        // 5. Simpan metadata dokumen ke database
        $document = Document::create([
            'judul_surat'      => $validated['judul_surat'],
            'file_name'        => $file->getClientOriginalName(),
            'file_path'        => $filePath, 
            'kategori'         => $validated['kategori'],
            'tanggal_surat'    => $validated['tanggal'],
            'status'           => 'Ditinjau',
            'id_user_uploader' => Auth::id(),
            'id_client_app'    => 1,         
        ]);

        // 6. Ambil urutan alur yang dipilih (misalnya: ['kaprodi_d3', 'kaprodi_d4', 'kajur'])
        $alurUserIds = explode(',', $validated['alur']);

        // Menyimpan langkah penandatanganan (workflow) untuk dokumen ini
        foreach ($alurUserIds as $index => $userId) {
        
            // Pastikan user-nya ada (keamanan)
            $userExists = User::find($userId); 
            if (!$userExists) {
                return redirect()->back()->withErrors("User ID '$userId' tidak valid.");
            }

            WorkflowStep::create([
                'document_id' => $document->id,
                'user_id'     => $userId, // <-- LANGSUNG PAKAI ID
                'urutan'      => $index + 1,
                'status'      => 'Ditinjau',
            ]);
        }

        // 7. Redirect balik ke halaman upload dengan pesan sukses
        return redirect()
            ->route('tu.upload.create')
            ->with('success', 'Surat berhasil diunggah dan menunggu peninjauan.');
    }

    /**
     * Menampilkan dokumen dan status langkah-langkah penandatanganan
     */
    public function show(Document $document)
    {
        // Ambil langkah-langkah untuk dokumen ini berdasarkan urutan
        $workflowSteps = WorkflowStep::where('document_id', $document->id)
            ->orderBy('urutan') // Urutkan langkah sesuai urutan
            ->get();

        // Periksa apakah role pengguna cocok dengan urutan langkah yang aktif
        $currentStep = $workflowSteps->firstWhere('user_id', Auth::id());

        if (!$currentStep) {
            return redirect()->back()->withErrors('Anda tidak memiliki hak akses untuk dokumen ini.');
        }

        return view('document.show', compact('document', 'currentStep', 'workflowSteps'));
    }

    /**
     * Update status langkah penandatanganan
     */
    public function updateStatus(Request $request, $documentId, $stepId)
    {
        // Ambil langkah yang sesuai
        $step = WorkflowStep::find($stepId);

        // Pastikan langkah ini sesuai dengan dokumen yang sedang diproses
        if ($step->document_id !== $documentId) {
            return redirect()->back()->withErrors('Langkah ini tidak valid.');
        }

        // Update status ke 'signed' atau 'diparaf' (tergantung aksi yang dilakukan)
        $step->status = 'signed'; // Ganti dengan status yang sesuai
        $step->tanggal_aksi = now(); // Waktu aksi dilakukan
        $step->save();

        // Cek jika semua langkah sudah diselesaikan
        $allSigned = WorkflowStep::where('document_id', $documentId)
                                ->where('status', '!=', 'signed')
                                ->count() == 0;

        if ($allSigned) {
            // Jika semua langkah sudah selesai, ubah status dokumen
            $document = Document::find($documentId);
            $document->status = 'completed'; // Status dokumen selesai
            $document->save();
        }

        return redirect()->route('tu.upload.create')->with('success', 'Langkah penandatanganan selesai.');
    }
}
