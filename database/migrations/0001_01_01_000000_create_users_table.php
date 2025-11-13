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
        Schema::create('users', function (Blueprint $table) {
            $table->id(); 
            $table->string('nama_lengkap'); 
            $table->string('email')->unique();
            $table->string('google_id')->unique()->nullable(); 
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable(); 
            $table->string('img_ttd_path')->nullable(); 
            $table->string('img_paraf_path')->nullable(); 
            $table->text('private_key')->nullable(); 
            $table->text('public_key')->nullable(); 
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
