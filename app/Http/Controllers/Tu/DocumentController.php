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
use Illuminate\Support\Facades\DB;
use App\Enums\RoleEnum;
use Illuminate\Support\Facades\Mail;
use App\Mail\DocumentWorkflowNotification;
use App\Enums\DocumentStatusEnum;
use App\Services\WorkflowService;

class DocumentController extends Controller
{
    protected $workflowService;

    public function __construct(WorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }
    // Menampilkan halaman upload surat beserta daftar user penandatangan
    public function create()
    {
        // Ambil semua user yang bukan role TU
        $users = User::whereHas('role', function($query) {
            $query->where('nama_role', '!=', RoleEnum::TU);
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

        // GUNAKAN TRANSACTION UNTUK MENCEGAH DATA INCONSISTENCY
        DB::beginTransaction();
        try {
            // Menyimpan file ke storage
            $filePath = $file->storeAs('documents', $filename);

            // Menyimpan data surat ke database
            $document = Document::create([
                'judul_surat'      => $validated['judul_surat'],
                'file_name'        => $file->getClientOriginalName(),
                'file_path'        => $filePath,
                'kategori'         => $validated['kategori'],
                'tanggal_surat'    => $validated['tanggal'],
                'status'           => DocumentStatusEnum::DITINJAU,
                'id_user_uploader' => Auth::id(),
                'id_client_app'    => 1,
            ]);

            // Mengubah string daftar user menjadi array
            $alurUserIds = explode(',', $validated['alur']);

            // Membuat langkah workflow untuk tiap user
            foreach ($alurUserIds as $index => $userId) {

                // Validasi user penandatangan
                if (!User::find($userId)) {
                    throw new \Exception("User ID '$userId' tidak valid.");
                }

                // Menambahkan step workflow ke database
                WorkflowStep::create([
                    'document_id' => $document->id,
                    'user_id'     => $userId,
                    'urutan'      => $index + 1,
                    'status'      => DocumentStatusEnum::DITINJAU,
                ]);
            }

            // Commit transaction jika semua berhasil
            DB::commit();

            // --- LOGIKA EMAIL NOTIFIKASI (USER PERTAMA) ---
            // Ambil user urutan ke-1
            $firstStep = WorkflowStep::where('document_id', $document->id)
                ->where('urutan', 1)
                ->first();

            if ($firstStep && $firstStep->user) {
                try {
                    Mail::to($firstStep->user->email)
                        ->send(new DocumentWorkflowNotification($document, $firstStep->user, 'next_turn'));
                } catch (\Exception $e) {
                    \Log::error("Gagal kirim email ke user pertama: " . $e->getMessage());
                }
            }

            // Kembali ke halaman upload dengan pesan sukses
            return redirect()
                ->route('tu.upload.create')
                ->with('success', 'Surat berhasil diunggah dan menunggu peninjauan.');

        } catch (\Exception $e) {
            // Rollback transaction jika terjadi error
            DB::rollBack();

            // Hapus file yang sudah terupload jika ada
            if (isset($filePath) && Storage::exists($filePath)) {
                Storage::delete($filePath);
            }

            // Kembali dengan error message
            return redirect()
                ->back()
                ->withInput()
                ->withErrors('Gagal mengupload dokumen: ' . $e->getMessage());
        }
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

    public function updateRevision(Request $request, $id)
    {
        $request->validate([
            'file_revisi' => 'required|file|mimes:pdf|max:2048',
        ]);

        $document = Document::findOrFail($id);

        // Validasi: Hanya dokumen berstatus Revisi yang boleh diupload ulang
        if ($document->status !== DocumentStatusEnum::PERLU_REVISI) {
            return back()->withErrors('Dokumen ini tidak sedang dalam status revisi.');
        }

        DB::beginTransaction();
        try {
            // 1. Hapus File Lama (Opsional, tapi sebaiknya dihapus agar tidak menumpuk sampah)
            if (Storage::exists($document->file_path)) {
                Storage::delete($document->file_path);
            }

            // 2. Upload File Baru
            $file = $request->file('file_revisi');
            $ext  = $file->getClientOriginalExtension();
            $filename = Str::uuid()->toString() . '.' . $ext;
            
            // Simpan file baru
            $filePath = $file->storeAs('documents', $filename); 

            // 3. Update Data Dokumen (Replace file lama & Reset Status)
            $document->update([
                'file_path' => $filePath,
                'file_name' => $file->getClientOriginalName(),
                'status'    => DocumentStatusEnum::DITINJAU // Reset status jadi Ditinjau agar Kaprodi bisa review ulang
            ]);

            // 4. RESET SEMUA WORKFLOW STEPS
            // Karena file diganti, proses harus diulang dari awal (reset approval)
            WorkflowStep::where('document_id', $id)->update([
                'status' => DocumentStatusEnum::DITINJAU,
                'tanggal_aksi' => null,
                'posisi_x' => null, // Reset posisi paraf jika perlu, karena layout file baru mungkin beda
                'posisi_y' => null,
                'halaman' => null
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'File revisi berhasil diunggah. Alur persetujuan dimulai ulang.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Gagal upload revisi: ' . $e->getMessage());
        }
    }

    // Mengupdate status penandatanganan workflow oleh user
    public function updateStatus(Request $request, $documentId, $stepId)
    {
        try {
            
            // Ini adalah endpoint untuk user melakukan aksi (paraf/ttd)
            $this->workflowService->completeStep($documentId, DocumentStatusEnum::DIPARAF);
            $this->workflowService->updateDocumentStatus($documentId);

            return redirect()
                ->back()
                ->with('success', 'Status berhasil diperbarui.');

        } catch (\Exception $e) {
            return redirect()->back()->withErrors($e->getMessage());
        }
    }

    public function download(Document $document)
    {
        // Path dari database
        $relativePath = $document->file_path;
        
        // Cek Lokasi 1: Folder Private (Dokumen Original)
        $privatePath = storage_path('app/private/' . $relativePath);

        // Cek Lokasi 2: Folder Public (Dokumen Hasil Paraf)
        $publicPath = storage_path('app/public/' . $relativePath);

        // Cek Lokasi 3: Folder App Default (Jaga-jaga)
        $appPath = storage_path('app/' . $relativePath);

        $finalPath = null;

        if (file_exists($privatePath)) {
            $finalPath = $privatePath;
        } elseif (file_exists($publicPath)) {
            $finalPath = $publicPath;
        } elseif (file_exists($appPath)) {
            $finalPath = $appPath;
        } else {
            abort(404, 'File fisik tidak ditemukan.');
        }

        // Return file ke browser (inline = preview) dengan error handling
        try {
            return response()->file($finalPath, [
                'Content-Type' => 'application/pdf',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0', 
            ]);
        } catch (\Exception $e) {
            abort(500, 'Gagal membaca file: ' . $e->getMessage());
        }
    }

    // Di dalam class DocumentController

public function preview($id) // Terima $id, bukan Document $document
{
    $document = Document::findOrFail($id); 

    $filePath = $document->file_path; 

    // Menggunakan Storage::get untuk mengambil konten file
    if (!Storage::exists($filePath)) {
        abort(404, 'File tidak ditemukan di storage: ' . $filePath);
    }
    
    $fileContent = Storage::get($filePath); 

    // PENTING: Pastikan Content-Type adalah application/pdf 
    // dan tidak ada header Content-Disposition yang memaksa download
    return response()->make($fileContent, 200, [
        'Content-Type' => 'application/pdf',
    ]);
}
}
