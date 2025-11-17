<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('judul_surat');
            $table->string('kategori')->nullable();
            $table->string('tanggal_surat')->nullable();
            $table->string('status')->default('Ditinjau');
            $table->foreignId('id_user_uploader')->constrained('users');
            $table->foreignId('id_client_app')->constrained('api_clients');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
