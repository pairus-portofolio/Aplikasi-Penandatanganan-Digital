<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\ApiClient;
use App\Models\WorkflowStep;

class Document extends Model
{
    // Tambahkan ini agar bisa diisi massal
    protected $guarded = [];

    public function uploader()
    {
        // Kita beri nama 'id_user_uploader' sesuai nama kolom FK kita
        return $this->belongsTo(User::class, 'id_user_uploader');
    }

    public function apiClient()
    {
        // Kita beri nama 'id_client_app' sesuai nama kolom FK kita
        return $this->belongsTo(ApiClient::class, 'id_client_app');
    }

    public function workflowSteps()
    {
        return $this->hasMany(WorkflowStep::class);
    }
}
