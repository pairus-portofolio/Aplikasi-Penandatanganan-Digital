<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::firstOrCreate(
            ['nama_role' => 'Tata Usaha']
        );
        
        Role::firstOrCreate(
            ['nama_role' => 'Koordinator Program Studi']
        );

        Role::firstOrCreate(
            ['nama_role' => 'Dosen']
        );

        Role::firstOrCreate(
            ['nama_role' => 'Ketua Jurusan']
        );

        Role::firstOrCreate(
            ['nama_role' => 'Sekretaris Jurusan']
        );

        Role::firstOrCreate(
            ['nama_role' => 'Admin']
        );
    }
}
