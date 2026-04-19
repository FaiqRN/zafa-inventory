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
        $roles = [
            [
                'nama_role' => 'Admin',
                'deskripsi' => 'Administrator dengan akses penuh ke semua fitur sistem',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_role' => 'Ketua',
                'deskripsi' => 'Ketua dengan akses manajemen operasional',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_role' => 'Karyawan',
                'deskripsi' => 'Karyawan dengan akses operasional terbatas',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($roles as $role) {
            DB::table('role')->updateOrInsert(
                ['nama_role' => $role['nama_role']],
                $role
            );
        }

        $this->command->info('✓ Roles berhasil di-seed');
    }
}
