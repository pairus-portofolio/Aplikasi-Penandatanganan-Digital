<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role; 
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil data role berdasarkan nama barunya
        $roleTu          = Role::where('nama_role', 'Tata Usaha')->firstOrFail();
        $roleKoordinator = Role::where('nama_role', 'Koordinator Program Studi')->firstOrFail();
        $roleDosen       = Role::where('nama_role', 'Dosen')->firstOrFail();
        $roleKajur       = Role::where('nama_role', 'Ketua Jurusan')->firstOrFail();
        $roleSekjur      = Role::where('nama_role', 'Sekretaris Jurusan')->firstOrFail();
        $roleAdmin       = Role::where('nama_role', 'Administrasi')->firstOrFail();

        // 1. TU
        User::firstOrCreate(
            ['email' => 'siti.soviyyah.tif24@polban.ac.id'],
            [
                'nama_lengkap' => 'Siti Soviyyah',
                'google_id'    => 'GOOGLE_ID_SITI_123456', 
                'password'     => 'password123',
                'role_id'      => $roleTu->id
            ]
        );

        // 2. Koordinator Program Studi 
        User::firstOrCreate(
            ['email' => 'fairuz.sheva.tif24@polban.ac.id'],
            [
                'nama_lengkap' => 'Fairuz Sheva Muhammad',
                'google_id'    => 'GOOGLE_ID_FAIRUZ_654321', 
                'password'     => 'password123', 
                'role_id'      => $roleKoordinator->id
            ]
        );

        // 3. Dosen
        User::firstOrCreate(
            ['email' => 'qlio.amanda.tif24@polban.ac.id'],
            [
                'nama_lengkap' => 'Qlio Amanda Febriany',
                'google_id'    => 'GOOGLE_ID_QLIO_987654', 
                'password'     => 'password123', 
                'role_id'      => $roleDosen->id
            ]
        );

        // 4. Kajur
        User::firstOrCreate(
            ['email' => 'helga.athifa.tif24@polban.ac.id'],
            [
                'nama_lengkap' => 'Helga Athifa Hidayat',
                'google_id'    => 'GOOGLE_ID_HELGA_987654', 
                'password'     => 'password123', 
                'role_id'      => $roleKajur->id
            ]
        );

        // 5. Sekjur
        User::firstOrCreate(
            ['email' => 'nike.kustiane.tif24@polban.ac.id'],
            [
                'nama_lengkap' => 'Nike Kustiane',
                'google_id'    => 'GOOGLE_ID_NIKE_987654', 
                'password'     => 'password123', 
                'role_id'      => $roleSekjur->id
            ]
        );

        // 6. Admin
        User::firstOrCreate(
            ['email' => 'qlioamanda@gmail.com'],
            [
                'nama_lengkap' => 'Admin', 
                'password'     => Hash::make('password123'), 
                'role_id'      => $roleAdmin->id
            ]
        );
    }
}