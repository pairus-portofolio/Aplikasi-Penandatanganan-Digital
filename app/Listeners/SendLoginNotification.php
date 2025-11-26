<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\LoginAlert;

class SendLoginNotification
{
    public function __construct()
    {
        //
    }

    public function handle(Login $event)
    {
        // 1. Cek Validitas User
        if (!$event->user) {
            return;
        }

        // 2. Cek Email Kosong
        if (empty($event->user->email)) {
            return; 
        }

        try {
            Mail::to($event->user->email)->send(new LoginAlert($event->user));
        } catch (\Exception $e) {
            // 3. Filter Log
            Log::warning("Peringatan Email Login (User ID: {$event->user->id}): " . $e->getMessage());
        }
    }
}