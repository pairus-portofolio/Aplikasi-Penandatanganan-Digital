<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Document;
use App\Models\WorkflowStep;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Enums\DocumentStatusEnum;
use Illuminate\Support\Facades\Mail;
use App\Mail\DocumentWorkflowNotification;

class ApiDocumentController extends Controller
{
    // UPLOAD SURAT
    public function store(Request $request)
    {
        // 1. Validasi Input
        $validator = Validator::make($request->all(), [
            'judul_surat' => 'required|string|max:255',
            'file_surat'  => 'required|file|mimes:pdf|max:2048', 
            'kategori'    => 'required|string',
            'tanggal'     => 'required|date',
            'alur'        => 'required|string', 
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // 2. Proses Upload & Database
        DB::beginTransaction();
        try {
            $file = $request->file('file_surat');
            $ext  = $file->getClientOriginalExtension();
            $filename = Str::uuid()->toString() . '.' . $ext;
            $filePath = $file->storeAs('documents', $filename);
            $defaultClientId = 1;

            // Simpan Data Dokumen
            $document = Document::create([
                'judul_surat'      => $request->judul_surat,
                'file_name'        => $file->getClientOriginalName(),
                'file_path'        => $filePath,
                'kategori'         => $request->kategori,
                'tanggal_surat'    => $request->tanggal,
                'status'           => DocumentStatusEnum::DITINJAU,
                'id_user_uploader' => Auth::id(), 
                'id_client_app'    => 1,
            ]);

            // Simpan Alur Workflow
            $alurUserIds = explode(',', $request->alur);
            foreach ($alurUserIds as $index => $userId) {
                // Validasi user exist
                if (!User::find($userId)) {
                    throw new \Exception("User penandatangan dengan ID $userId tidak ditemukan.");
                }

                WorkflowStep::create([
                    'document_id' => $document->id,
                    'user_id'     => $userId,
                    'urutan'      => $index + 1,
                    'status'      => DocumentStatusEnum::DITINJAU,
                ]);
            }

            DB::commit();

            // 3. Kirim Email Notifikasi (Otomatis)
            $firstStep = WorkflowStep::where('document_id', $document->id)->orderBy('urutan')->first();
            if ($firstStep && $firstStep->user) {
                try {
                    Mail::to($firstStep->user->email)
                        ->send(new DocumentWorkflowNotification($document, $firstStep->user, 'next_turn'));
                } catch (\Exception $e) {
                    // Ignore email error agar respon API tetap cepat & sukses
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Dokumen berhasil diunggah via API.',
                'data' => [
                    'id' => $document->id,
                    'judul' => $document->judul_surat,
                    'nomor_surat' => 'SRT-' . str_pad($document->id, 4, '0', STR_PAD_LEFT),
                    'status' => $document->status
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Hapus file jika gagal database
            if (isset($filePath) && Storage::exists($filePath)) {
                Storage::delete($filePath);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memproses dokumen: ' . $e->getMessage()
            ], 500);
        }
    }

    // CEK STATUS SURAT
    public function checkStatus($id)
    {
        $document = Document::find($id);

        if (!$document) {
            return response()->json(['status' => 'error', 'message' => 'Dokumen tidak ditemukan'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $document->id,
                'judul' => $document->judul_surat,
                'status_terkini' => $document->status,
                'posisi_sekarang' => $this->getCurrentWorkflowPosition($document)
            ]
        ]);
    }

    private function getCurrentWorkflowPosition($document)
    {
        $activeStep = $document->workflowSteps()->where('status', DocumentStatusEnum::DITINJAU)->orderBy('urutan')->first();
        if ($activeStep) {
            return "Menunggu giliran: " . $activeStep->user->nama_lengkap;
        }
        return "Selesai / " . $document->status;
    }
}