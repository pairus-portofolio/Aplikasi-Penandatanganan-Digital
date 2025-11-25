<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Document;
use App\Models\User;

class DocumentWorkflowNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $document;
    public $receiver;
    public $type; 

    public function __construct(Document $document, User $receiver, $type = 'next_turn')
    {
        $this->document = $document;
        $this->receiver = $receiver;
        $this->type = $type;
    }

    public function build()
    {
        $subject = '';
        
        if ($this->type == 'completed') {
            $subject = '[SELESAI] Dokumen Telah Selesai Diproses: ' . $this->document->judul_surat;
        } else {
            // ID Role: 2,3 (Kaprodi/Paraf), 4,5 (Kajur/Tanda Tangan)
            if (in_array($this->receiver->role_id, [2, 3])) {
                $subject = '[TINDAKAN DIPERLUKAN] Giliran Anda Melakukan Paraf: ' . $this->document->judul_surat;
            } else {
                $subject = '[TINDAKAN DIPERLUKAN] Giliran Anda Menandatangani: ' . $this->document->judul_surat;
            }
        }

        return $this->subject($subject)
                    ->view('emails.workflow_notification');
    }
}