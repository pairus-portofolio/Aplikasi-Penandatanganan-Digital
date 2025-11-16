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
        return view('Tu.upload');
    }

    /**
     * Proses upload surat
     */
    public function store(Request $request)
    {
        // 1. Validasi input
        $validated = $request->validate([
            'judul_surat' => 'required|string|max:255',
            'file_surat'  => 'required|file|mimes:pdf,doc,docx|max:2048',
            'alur'         => 'required|string',  // Alur penandatanganan (misal: kaprodi_d3,kaprodi_d4,kajur)
        ]);

        // 2. Ambil file dari request
        $file = $request->file('file_surat');
        $ext  = $file->getClientOriginalExtension();

        // 3. Generate nama file unik
        $filename = Str::uuid()->toString() . '.' . $ext;

        // 4. Simpan file ke storage/app/public/documents
        $filePath = $file->storeAs('documents', $filename, 'public');

        // 5. Simpan metadata dokumen ke database
        $document = Document::create([
            'judul_surat'      => $validated['judul_surat'],
            'file_name'        => $filename,
            'file_path'        => 'storage/' . $filePath, 
            'status'           => 'Ditinjau',  // Status awal dokumen
            'id_user_uploader' => Auth::id(),
            'id_client_app'    => 1,         
        ]);

        // 6. Ambil urutan alur yang dipilih (misalnya: ['kaprodi_d3', 'kaprodi_d4', 'kajur'])
        $alurSteps = explode(',', $validated['alur']); 

        // Menyimpan langkah penandatanganan (workflow) untuk dokumen ini
        foreach ($alurSteps as $index => $userRole) {
            // Ambil user_id berdasarkan role
            $userId = $this->getUserIdByRole($userRole); 

            // Periksa jika user_id valid
            if (!$userId) {
                return redirect()->back()->withErrors("Role '$userRole' tidak valid atau tidak ditemukan.");
            }

            // Simpan langkah workflow ke tabel workflow_steps
            WorkflowStep::create([
                'document_id' => $document->id,
                'user_id'     => $userId,
                'urutan'      => $index + 1, // Urutan penandatanganan
                'status'      => 'Ditinjau',  // Status awal
                'posisi_x_ttd'=> null, // Posisi tanda tangan (opsional)
                'posisi_y_ttd'=> null, // Posisi tanda tangan (opsional)
            ]);
        }

        // 7. Redirect balik ke halaman upload dengan pesan sukses
        return redirect()
            ->route('tu.upload.create')
            ->with('success', 'Surat berhasil diunggah dan menunggu peninjauan.');
    }

    /**
     * Fungsi untuk mendapatkan user_id berdasarkan role
     */
    private function getUserIdByRole($role)
    {
        // Ambil user_id berdasarkan role
        switch ($role) {
            case 'kaprodi_d3':
                $user = User::where('role', 'kaprodi_d3')->first();
                break;
            case 'kaprodi_d4':
                $user = User::where('role', 'kaprodi_d4')->first();
                break;
            case 'kajur':
                $user = User::where('role', 'kajur')->first();
                break;
            default:
                $user = null;
        }

        // Pastikan user ditemukan
        if ($user) {
            return $user->id;
        } else {
            // Jika role tidak ditemukan, lemparkan error atau lakukan penanganan sesuai kebutuhan
            return null; // Atau lemparkan exception jika role tidak ditemukan
        }
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
