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

    // UPDATE: Tambahkan parameter $id = null
    public function create($id = null)
    {
        // Ambil semua user yang bukan role TU
        $users = User::whereHas('role', function($query) {
            $query->where('nama_role', '!=', RoleEnum::TU);
        })->get(['id', 'nama_lengkap']);

        $document = null;

        // JIKA ADA ID (MODE REVISI)
        if ($id) {
            $document = Document::findOrFail($id);
            // Validasi: Cuma boleh edit kalau statusnya Perlu Revisi
            if ($document->status !== DocumentStatusEnum::PERLU_REVISI) {
                return redirect()->route('tu.upload.create')
                    ->withErrors('Hanya dokumen status Revisi yang bisa diedit di sini.');
            }
        }

        return view('tu.upload', ['users' => $users, 'document' => $document]);
    }

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

        $file = $request->file('file_surat');
        $ext  = $file->getClientOriginalExtension();
        $filename = Str::uuid()->toString() . '.' . $ext;

        DB::beginTransaction();
        try {
            $filePath = $file->storeAs('documents', $filename);

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

            $alurUserIds = explode(',', $validated['alur']);

            foreach ($alurUserIds as $index => $userId) {
                if (!User::find($userId)) {
                    throw new \Exception("User ID '$userId' tidak valid.");
                }

                WorkflowStep::create([
                    'document_id' => $document->id,
                    'user_id'     => $userId,
                    'urutan'      => $index + 1,
                    'status'      => DocumentStatusEnum::DITINJAU,
                ]);
            }

            DB::commit();

            // --- LOGIKA EMAIL MANUAL ---
            if ($request->input('send_notification') == '1') {
                $firstStep = WorkflowStep::where('document_id', $document->id)
                    ->where('urutan', 1)
                    ->first();

                if ($firstStep && $firstStep->user) {
                    try {
                        Mail::to($firstStep->user->email)
                            ->send(new DocumentWorkflowNotification($document, $firstStep->user, 'next_turn'));
                    } catch (\Exception $e) {
                        \Log::error("Gagal kirim email: " . $e->getMessage());
                    }
                }
            }

            return redirect()
                ->route('tu.upload.create')
                ->with('success', 'Surat berhasil diunggah.');

        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($filePath) && Storage::exists($filePath)) {
                Storage::delete($filePath);
            }

            // FIX BUG #9: Log error detail untuk developer, tampilkan generic message ke user
            \Log::error('Document upload failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Kembali dengan error message yang user-friendly
            return redirect()
                ->back()
                ->withInput()
                ->withErrors('Gagal mengupload dokumen. Silakan periksa kembali data Anda dan coba lagi.');
        }
    }

    // UPDATE: Perbaiki logika replace file di sini
    public function updateRevision(Request $request, $id)
    {
        // Validasi: File revisi wajib diupload, data lain opsional (bisa pakai data lama)
        $request->validate([
            'file_surat' => 'required|file|mimes:pdf|max:2048', // Ganti nama input jadi file_surat biar sama dgn form
            'judul_surat'=> 'required|string|max:255',
            'kategori'   => 'required|string',
            'tanggal'    => 'required|date',
            'alur'       => 'required|string',
        ]);

        $document = Document::findOrFail($id);

        if ($document->status !== DocumentStatusEnum::PERLU_REVISI) {
            return back()->withErrors('Dokumen ini tidak sedang dalam status revisi.');
        }

        DB::beginTransaction();
        try {
            // 1. Hapus File Lama
            if ($document->file_path && Storage::exists($document->file_path)) {
                Storage::delete($document->file_path);
            }

            // 2. Upload File Baru
            $file = $request->file('file_surat');
            $ext  = $file->getClientOriginalExtension();
            $filename = Str::uuid()->toString() . '.' . $ext;
            $filePath = $file->storeAs('documents', $filename); 

            // 3. Update Data Dokumen (Replace path & reset status)
            $document->update([
                'judul_surat'   => $request->judul_surat,
                'kategori'      => $request->kategori,
                'tanggal_surat' => $request->tanggal,
                'file_path'     => $filePath,
                'file_name'     => $file->getClientOriginalName(),
                'status'        => DocumentStatusEnum::DITINJAU 
            ]);

            // 4. RESET ALUR (Workflow)
            // Hapus step lama dan buat ulang sesuai input 'alur' 
            // (karena bisa jadi TU mengubah urutan saat revisi)
            WorkflowStep::where('document_id', $id)->delete();

            $alurUserIds = explode(',', $request->alur);
            foreach ($alurUserIds as $index => $userId) {
                WorkflowStep::create([
                    'document_id' => $document->id,
                    'user_id'     => $userId,
                    'urutan'      => $index + 1,
                    'status'      => DocumentStatusEnum::DITINJAU,
                ]);
            }

            DB::commit();

            // Kirim notifikasi manual jika dicentang
            if ($request->input('send_notification') == '1') {
                // ... logika kirim notif sama ...
                $firstStep = WorkflowStep::where('document_id', $document->id)->orderBy('urutan')->first();
                if($firstStep && $firstStep->user) {
                     try {
                        Mail::to($firstStep->user->email)
                            ->send(new DocumentWorkflowNotification($document, $firstStep->user, 'next_turn'));
                    } catch (\Exception $e) {}
                }
            }

            // Redirect ke halaman create tanpa ID (bersih)
            return redirect()->route('tu.upload.create')->with('success', 'Revisi berhasil disimpan dan alur dimulai ulang.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Gagal update revisi: ' . $e->getMessage());
        }
    }

    public function show(Document $document)
    {
        $workflowSteps = WorkflowStep::where('document_id', $document->id)->orderBy('urutan')->get();
        $currentStep = $workflowSteps->firstWhere('user_id', Auth::id());

        if (!$currentStep) {
            return redirect()->back()->withErrors('Anda tidak memiliki hak akses.');
        }
        return view('document.show', compact('document', 'currentStep', 'workflowSteps'));
    }

    public function download(Document $document)
    {
        $relativePath = $document->file_path;
        $privatePath = storage_path('app/private/' . $relativePath);
        $publicPath = storage_path('app/public/' . $relativePath);
        $appPath = storage_path('app/' . $relativePath);

        $finalPath = null;
        if (file_exists($privatePath)) $finalPath = $privatePath;
        elseif (file_exists($publicPath)) $finalPath = $publicPath;
        elseif (file_exists($appPath)) $finalPath = $appPath;
        else abort(404, 'File fisik tidak ditemukan.');

        return response()->file($finalPath, [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0', 
        ]);
    }

    public function preview($id)
    {
        $document = Document::findOrFail($id); 
        $filePath = $document->file_path; 
        if (!Storage::exists($filePath)) {
            abort(404);
        }
        $fileContent = Storage::get($filePath); 
        return response()->make($fileContent, 200, ['Content-Type' => 'application/pdf']);
    }
}