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
    // Tampilkan halaman form upload surat
    public function create()
    {
        // Ambil semua user yang bukan role TU
        $users = User::whereHas('role', function($query) {
            $query->where('nama_role', '!=', 'TU');
        })->get(['id', 'nama_lengkap']); // ambil hanya id dan nama

        return view('tu.upload', ['users' => $users]);
    }

    // Proses upload surat
    public function store(Request $request)
    {
        // Validasi input form
        $validated = $request->validate([
            'judul_surat' => 'required|string|max:255',
            'file_surat'  => 'required|file|mimes:docx|max:2048',
            'kategori'    => 'required|string',
            'tanggal'     => 'required|date',
            'alur'        => 'required|string',
        ]);

        // Ambil file dari request
        $file = $request->file('file_surat');
        $ext  = $file->getClientOriginalExtension();

        // Generate nama file baru (unik)
        $filename = Str::uuid()->toString() . '.' . $ext;

        // Simpan file ke storage/documents
        $filePath = $file->storeAs('documents', $filename);

        // Simpan metadata ke database
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

        // Pecah string alur menjadi array user ID
        $alurUserIds = explode(',', $validated['alur']);

        // Simpan setiap langkah workflow
        foreach ($alurUserIds as $index => $userId) {

            // Validasi apakah user ID valid
            if (!User::find($userId)) {
                return redirect()->back()->withErrors("User ID '$userId' tidak valid.");
            }

            // Buat step baru dalam workflow
            WorkflowStep::create([
                'document_id' => $document->id,
                'user_id'     => $userId,
                'urutan'      => $index + 1, // urutan dimulai dari 1
                'status'      => 'Ditinjau',
            ]);
        }

        // Kembali ke halaman upload dengan pesan sukses
        return redirect()
            ->route('tu.upload.create')
            ->with('success', 'Surat berhasil diunggah dan menunggu peninjauan.');
    }

    // Menampilkan detail dokumen + status workflow
    public function show(Document $document)
    {
        // Ambil semua langkah workflow berdasarkan urutan
        $workflowSteps = WorkflowStep::where('document_id', $document->id)
            ->orderBy('urutan')
            ->get();

        // Ambil step yang cocok dengan user saat ini
        $currentStep = $workflowSteps->firstWhere('user_id', Auth::id());

        // Jika user tidak punya akses pada dokumen
        if (!$currentStep) {
            return redirect()->back()->withErrors('Anda tidak memiliki hak akses untuk dokumen ini.');
        }

        return view('document.show', compact('document', 'currentStep', 'workflowSteps'));
    }

    // Update status penandatanganan workflow (paraf/ttd)
    public function updateStatus(Request $request, $documentId, $stepId)
    {
        // Ambil step berdasarkan ID
        $step = WorkflowStep::find($stepId);

        // Pastikan step sesuai dengan dokumen
        if ($step->document_id !== $documentId) {
            return redirect()->back()->withErrors('Langkah ini tidak valid.');
        }

        // Ubah status step menjadi signed
        $step->status = 'signed';
        $step->tanggal_aksi = now();
        $step->save();

        // Periksa apakah semua step sudah signed
        $allSigned = WorkflowStep::where('document_id', $documentId)
                                ->where('status', '!=', 'signed')
                                ->count() == 0;

        // Jika semua sudah signed → update status dokumen
        if ($allSigned) {
            $document = Document::find($documentId);
            $document->status = 'completed';
            $document->save();
        }

        return redirect()
            ->route('tu.upload.create')
            ->with('success', 'Langkah penandatanganan selesai.');
    }
}
