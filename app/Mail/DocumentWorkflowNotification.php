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
    public $notes;
    public $customSubject;

    public function __construct(Document $document, User $receiver, $type = 'next_turn', $notes = null, $customSubject = null)
    {
        $this->document = $document;
        $this->receiver = $receiver;
        $this->type = $type;
        $this->notes = $notes;
        $this->customSubject = $customSubject;
    }

    public function build()
    {
        $subject = '';
        
        // Logika Subjek
        if ($this->customSubject) {
            // Jika ada subjek custom (dari revisi), pakai itu
            $subject = $this->customSubject;
        } elseif ($this->type == 'completed') {
            $subject = '[SELESAI] Dokumen Telah Selesai Diproses: ' . $this->document->judul_surat;
        } else {
            // Default subjek
            $subject = '[TINDAKAN DIPERLUKAN] Dokumen: ' . $this->document->judul_surat;
        }

        return $this->subject($subject)
                    ->view('emails.workflow_notification');
    }
}