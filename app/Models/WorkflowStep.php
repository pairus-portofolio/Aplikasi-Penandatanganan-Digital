<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Document;

class WorkflowStep extends Model
{
    // Tambahkan ini agar bisa diisi massal
    protected $guarded = [];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
