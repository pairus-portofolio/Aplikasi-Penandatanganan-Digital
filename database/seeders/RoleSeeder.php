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
            ['nama_role' => 'TU']
        );
        
        Role::firstOrCreate(
            ['nama_role' => 'Kaprodi D3']
        );

        Role::firstOrCreate(
            ['nama_role' => 'Kaprodi D4']
        );

        Role::firstOrCreate(
            ['nama_role' => 'Kajur']
        );

        Role::firstOrCreate(
            ['nama_role' => 'Sekjur']
        );

        Role::firstOrCreate(
            ['nama_role' => 'Admin']
        );
    }
}
