<?php

namespace App\Http\Controllers\Tu;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\WorkflowStep;
use App\Models\User;
use App\Models\Role; 
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

/**
 * Controller untuk mengelola dokumen (upload dan revisi) oleh TU.
 *
 * Menangani proses upload dokumen baru dengan validasi alur workflow kustom,
 * update revisi dokumen, dan view dokumen untuk TU.
 *
 * @package App\Http\Controllers\Tu
 */
class DocumentController extends Controller
{
    /**
     * Service untuk mengelola workflow dokumen.
     *
     * @var WorkflowService
     */
    protected $workflowService;

    /**
     * Inisialisasi controller dengan dependency injection.
     *
     * @param WorkflowService $workflowService Service untuk workflow
     */
    public function __construct(WorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    /**
     * Tampilkan form upload dokumen baru atau revisi.
     *
     * Jika ada ID dokumen, mode revisi akan diaktifkan dan data dokumen
     * beserta workflow yang ada akan dimuat untuk diedit.
     *
     * @param int|null $id ID dokumen untuk mode revisi (opsional)
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function create($id = null)
    {
        $kaprodis = User::whereHas('role', function($query) {
            $query->whereIn('nama_role', RoleEnum::getKaprodiRoles());
        })->with('role')->get(['id', 'nama_lengkap', 'role_id']);

        $penandatangan = User::whereHas('role', function($query) {
            $query->whereIn('nama_role', RoleEnum::getKajurSekjurRoles());
        })->with('role')->get(['id', 'nama_lengkap', 'role_id']);

        $document = null;
        $users = null;
        $existingAlurIds = [];

        if ($id) {
            $document = Document::findOrFail($id);
            if ($document->status !== DocumentStatusEnum::PERLU_REVISI) {
                return redirect()->route('tu.upload.create')
                    ->withErrors('Hanya dokumen status Revisi yang bisa diedit di sini.');
            }
            $existingAlurIds = $document->workflowSteps->pluck('user_id')->toArray();
            $users = User::whereHas('role', function($query) {
                $query->where('nama_role', '!=', RoleEnum::TU);
            })->get(['id', 'nama_lengkap']);
        }

        return view('tu.upload', [
            'kaprodis' => $kaprodis,
            'penandatangan' => $penandatangan,
            'document' => $document,
            'existingAlurIds' => $existingAlurIds,
            'users' => $users,
        ]);
    }

    /**
     * Simpan dokumen baru yang diupload oleh TU.
     *
     * Validasi alur workflow yang ketat:
     * - Maksimal 2 pemaraf (Kaprodi)
     * - Wajib 1 penandatangan (Kajur/Sekjur) di akhir
     * - Tidak boleh ada duplikasi user
     *
     * @param Request $request HTTP request dengan data dokumen dan alur
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // Validasi Alur Kustom
        $validated = $request->validate([
            'judul_surat' => 'required|string|max:255',
            'file_surat'  => 'required|file|mimes:pdf|max:2048',
            'kategori'    => 'required|string',
            'tanggal'     => 'required|date',
            'alur'        => [ 
                'required',
                'string',
                'regex:/^[0-9,]+$/',
                function ($attribute, $value, $fail) {
                    $kaprodiRoleNames = RoleEnum::getKaprodiRoles();
                    $kajurSekjurRoleNames = RoleEnum::getKajurSekjurRoles();

                    $userIds = array_filter(explode(',', $value));
                    
                    if (empty($userIds)) {
                        $fail('Minimal harus ada 1 penandatangan (Kajur/Sekjur).');
                        return;
                    }
                    
                    if (count($userIds) !== count(array_unique($userIds))) {
                        $fail('Tidak boleh ada penandatangan yang sama lebih dari sekali dalam alur.');
                        return;
                    }
                    
                    $allUsers = User::whereIn('id', $userIds)->with('role')->get();
                    
                    $parafUsers = [];
                    $ttdUser = null;
                    
                    foreach ($userIds as $id) {
                        $user = $allUsers->firstWhere('id', $id);
                        $roleName = $user->role->nama_role ?? '';

                        if (in_array($roleName, $kaprodiRoleNames)) {
                            $parafUsers[] = $id;
                        } elseif (in_array($roleName, $kajurSekjurRoleNames)) {
                            if ($ttdUser !== null) {
                                $fail('Hanya boleh ada satu Penandatangan (Kajur atau Sekjur).');
                                return;
                            }
                            $ttdUser = $id;
                        } else {
                            $fail("User dengan ID '$id' tidak memiliki role yang valid.");
                            return;
                        }
                    }
                    
                    if (count($parafUsers) > 2) {
                        $fail('Maksimal hanya boleh ada 2 Pemaraf (Kaprodi).');
                        return;
                    }
                    
                    if ($ttdUser === null) {
                        $fail('Wajib ada 1 Penandatangan (Kajur atau Sekjur).');
                        return;
                    }

                    if (end($userIds) != $ttdUser) {
                        $fail('Penandatangan (Kajur/Sekjur) harus menjadi langkah terakhir.');
                        return;
                    }

                    if (in_array($ttdUser, $parafUsers)) {
                        $fail('Penandatangan tidak boleh merangkap sebagai Pemaraf.');
                        return;
                    }
                },
            ],
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

            $alurUserIds = array_filter(explode(',', $validated['alur']));

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

            // Logika Email
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
                ->with('success', 'Surat berhasil diunggah dan menunggu peninjauan.');

        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($filePath) && Storage::exists($filePath)) {
                Storage::delete($filePath);
            }

            \Log::error('Document upload failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->withErrors('Gagal mengupload dokumen. Silakan periksa kembali data Anda dan coba lagi.');
        }
    }
    /**
     * Update dokumen yang statusnya PERLU_REVISI.
     *
     * Mengganti file dokumen, update metadata, dan reset workflow
     * untuk memulai alur dari awal.
     *
     * @param Request $request HTTP request dengan file dan data baru
     * @param int $id ID dokumen yang akan direvisi
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateRevision(Request $request, $id)
    {
        $request->validate([
            'file_surat' => 'required|file|mimes:pdf|max:2048',
            'judul_surat'=> 'required|string|max:255',
            'kategori'   => 'required|string',
            'tanggal'    => 'required|date',
            'alur'       => 'required|string', // Perlu dipertimbangkan validasi alur kustom di sini juga
        ]);

        $document = Document::findOrFail($id);

        if ($document->status !== DocumentStatusEnum::PERLU_REVISI) {
            return back()->withErrors('Dokumen ini tidak sedang dalam status revisi.');
        }

        DB::beginTransaction();
        try {
            if ($document->file_path && Storage::exists($document->file_path)) {
                Storage::delete($document->file_path);
            }

            $file = $request->file('file_surat');
            $ext  = $file->getClientOriginalExtension();
            $filename = Str::uuid()->toString() . '.' . $ext;
            $filePath = $file->storeAs('documents', $filename); 

            $document->update([
                'judul_surat'   => $request->judul_surat,
                'kategori'      => $request->kategori,
                'tanggal_surat' => $request->tanggal,
                'file_path'     => $filePath,
                'file_name'     => $file->getClientOriginalName(),
                'status'        => DocumentStatusEnum::DITINJAU 
            ]);

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

            if ($request->input('send_notification') == '1') {
                $firstStep = WorkflowStep::where('document_id', $document->id)->orderBy('urutan')->first();
                if($firstStep && $firstStep->user) {
                     try {
                        Mail::to($firstStep->user->email)
                            ->send(new DocumentWorkflowNotification($document, $firstStep->user, 'next_turn'));
                    } catch (\Exception $e) {}
                }
            }

            return redirect()->route('tu.upload.create')->with('success', 'Revisi berhasil disimpan dan alur dimulai ulang.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Gagal update revisi: ' . $e->getMessage());
        }
    }

    /**
     * Tampilkan dokumen untuk TU (view-only mode).
     *
     * TU selalu bisa melihat semua dokumen dalam mode read-only.
     *
     * @param int $id ID dokumen
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $document = Document::findOrFail($id);
        
        // TU selalu bisa melihat dokumen (View Only)
        return view('shared.view-document', [
            'document' => $document,
            'showRevisionButton' => false
        ]);
    }
    
    /**
     * Download file PDF dokumen.
     *
     * Mencari file di berbagai lokasi storage (private, public, app).
     *
     * @param Document $document Instance dokumen
     * @return \Illuminate\Http\Response
     */
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

    /**
     * Preview dokumen PDF di browser.
     *
     * @param int $id ID dokumen
     * @return \Illuminate\Http\Response
     */
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