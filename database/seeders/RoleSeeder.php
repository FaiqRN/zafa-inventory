<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Insert default roles
        DB::table('role')->insert([
            ['nama_role' => 'admin', 'deskripsi' => 'Administrator dengan akses penuh', 'created_at' => now(), 'updated_at' => now()],
            ['nama_role' => 'ketua', 'deskripsi' => 'Ketua dengan akses manajemen', 'created_at' => now(), 'updated_at' => now()],
            ['nama_role' => 'karyawan', 'deskripsi' => 'Karyawan dengan akses terbatas', 'created_at' => now(), 'updated_at' => now()],
            ['nama_role' => 'FRN', 'deskripsi' => 'FaiqRN', 'created_at' => now(), 'updated_at' => now()],
            ['nama_role' => 'AP', 'deskripsi' => 'AnnisaP', 'created_at' => now(), 'updated_at' => now()]

        ]);
        echo "\nRoles seeded successfully!\n";
    }
}
