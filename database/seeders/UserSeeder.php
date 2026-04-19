<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get Admin role
        $adminRole = DB::table('role')->where('nama_role', 'Admin')->first();

        if (!$adminRole) {
            $this->command->error('Role Admin tidak ditemukan. Jalankan RoleSeeder terlebih dahulu.');
            return;
        }

        // Create default admin user if not exists
        $adminExists = DB::table('user')->where('username', 'admin')->exists();

        if (!$adminExists) {
            DB::table('user')->insert([
                'role_id' => $adminRole->role_id,
                'username' => 'admin',
                'password' => Hash::make('admin123'),
                'firstname' => 'Administrator',
                'lastname' => 'System Super',
                'email' => 'devanozo976@gmail.com',
                'telp' => '081234567890',
                'alamat' => 'Jl.Kontolondon.com',
                'jenis_kelamin' => 'L',
                'created_by' => 'system',
                'user_create' => 'system',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command->info('✓ User Admin berhasil dibuat (username: admin, password: admin123)');
        } else {
            $this->command->info('✓ User Admin sudah ada');
        }
    }
}
