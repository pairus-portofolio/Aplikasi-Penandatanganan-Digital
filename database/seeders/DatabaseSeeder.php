<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // PENTING: Urutan berpengaruh!
        // Kita butuh Roles dan ApiClients ada DULUAN sebelum Users dan Documents
        $this->call([
            RoleSeeder::class,
            ApiClientSeeder::class,
            UserSeeder::class,
        ]);
    }
}
