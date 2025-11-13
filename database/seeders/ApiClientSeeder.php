<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ApiClient;
use Illuminate\Support\Facades\Hash;

class ApiClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ApiClient::firstOrCreate(
            ['nama_client' => 'Web Manajemen Dokumen Dosen'],
            [
                'api_token' => Hash::make('TOKEN_RAHASIA_WEB_DOSEN_123'),
                'callback_url' => 'https://webdosen.test/api/callback'
            ]
        );
    }
}
