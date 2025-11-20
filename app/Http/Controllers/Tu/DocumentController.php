<?php

namespace App\Http\Controllers\Tu;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\WorkflowStep;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;

class DocumentController extends Controller
{
    // Menampilkan halaman upload surat beserta daftar user penandatangan
    public function create()
    {
        // Ambil semua user yang bukan role TU
        $users = User::whereHas('role', function($query) {
            $query->where('nama_role', '!=', 'TU');
        })->get(['id', 'nama_lengkap']);

        return view('tu.upload', ['users' => $users]);
    }

    // Menyimpan surat yang diupload dan membuat workflow penandatangan
    public function store(Request $request)
    {
        // Validasi data form upload
        $validated = $request->validate([
            'judul_surat' => 'required|string|max:255',
            'file_surat'  => 'required|file|mimes:pdf|max:2048',
            'kategori'    => 'required|string',
            'tanggal'     => 'required|date',
            'alur'        => 'required|string',
        ]);

        // Mengambil file yang diupload
        $file = $request->file('file_surat');
        $ext  = $file->getClientOriginalExtension();

        // Membuat nama file unik
        $filename = Str::uuid()->toString() . '.' . $ext;

        // Menyimpan file ke storage
        $filePath = $file->storeAs('documents', $filename);

        // Menyimpan data surat ke database
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

        // Mengubah string daftar user menjadi array
        $alurUserIds = explode(',', $validated['alur']);

        // Membuat langkah workflow untuk tiap user
        foreach ($alurUserIds as $index => $userId) {

            // Validasi user penandatangan
            if (!User::find($userId)) {
                return redirect()->back()->withErrors("User ID '$userId' tidak valid.");
            }

            // Menambahkan step workflow ke database
            WorkflowStep::create([
                'document_id' => $document->id,
                'user_id'     => $userId,
                'urutan'      => $index + 1,
                'status'      => 'Ditinjau',
            ]);
        }

        // Kembali ke halaman upload dengan pesan sukses
        return redirect()
            ->route('tu.upload.create')
            ->with('success', 'Surat berhasil diunggah dan menunggu peninjauan.');
    }

    // Menampilkan detail dokumen beserta status workflow
    public function show(Document $document)
    {
        // Mengambil semua langkah workflow berdasarkan urutan
        $workflowSteps = WorkflowStep::where('document_id', $document->id)
            ->orderBy('urutan')
            ->get();

        // Mengambil step khusus untuk user yang sedang login
        $currentStep = $workflowSteps->firstWhere('user_id', Auth::id());

        // Jika user tidak punya akses pada dokumen
        if (!$currentStep) {
            return redirect()->back()->withErrors('Anda tidak memiliki hak akses untuk dokumen ini.');
        }

        return view('document.show', compact('document', 'currentStep', 'workflowSteps'));
    }

    // Mengupdate status penandatanganan workflow oleh user
    public function updateStatus(Request $request, $documentId, $stepId)
    {
        // Mengambil step yang ingin diperbarui
        $step = WorkflowStep::find($stepId);

        // Memastikan step sesuai dengan dokumen yang dimaksud
        if ($step->document_id !== $documentId) {
            return redirect()->back()->withErrors('Langkah ini tidak valid.');
        }

        // Menandai step sebagai selesai ditandatangani
        $step->status = 'signed';
        $step->tanggal_aksi = now();
        $step->save();

        // Mengecek apakah semua step sudah ditandatangani
        $allSigned = WorkflowStep::where('document_id', $documentId)
                                ->where('status', '!=', 'signed')
                                ->count() == 0;

        // Jika seluruh step selesai, update status dokumen menjadi completed
        if ($allSigned) {
            $document = Document::find($documentId);
            $document->status = 'completed';
            $document->save();
        }

        // Kembali ke halaman upload dengan notifikasi sukses
        return redirect()
            ->route('tu.upload.create')
            ->with('success', 'Langkah penandatanganan selesai.');
    }

    public function download(Document $document)
    {
        // Ambil path dari database (misal: documents/abc.pdf)
        $relativePath = $document->file_path;
        
        // Cek dulu apakah path di database sudah ada 'private/' atau belum
        if (!str_starts_with($relativePath, 'private/')) {
            $fullPath = 'private/' . $relativePath;
        } else {
            $fullPath = $relativePath;
        }

        $absolutePath = storage_path('app/' . $fullPath);

        // 3. Cek Keberadaan File
        if (!file_exists($absolutePath)) {
            abort(404);
        }

        return response()->file($absolutePath);
    }
}
