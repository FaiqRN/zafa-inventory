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
        // Get roles
        $adminRole = DB::table('role')->where('nama_role', 'Admin')->first();
        $ketuaRole = DB::table('role')->where('nama_role', 'Ketua')->first();
        $karyawanRole = DB::table('role')->where('nama_role', 'Karyawan')->first();

        if (!$adminRole || !$ketuaRole || !$karyawanRole) {
            $this->command->error('Role tidak lengkap. Jalankan RoleSeeder terlebih dahulu.');
            return;
        }

        // Create default admin user if not exists
        $adminExists = DB::table('user')->where('username', 'admin')->exists();

        if (!$adminExists) {
            DB::table('user')->insert([
                'role_id' => $adminRole->role_id,
                'username' => 'adminsuper',
                'password' => Hash::make('Superadmin6789!!'),
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

        // Create default ketua user if not exists
        $ketuaExists = DB::table('user')->where('username', 'ketua')->exists();

        if (!$ketuaExists) {
            DB::table('user')->insert([
                'role_id' => $ketuaRole->role_id,
                'username' => 'admin',
                'password' => Hash::make('admin123'),
                'firstname' => 'admin',
                'lastname' => 'Tim',
                'email' => 'admin@example.com',
                'telp' => '081234567891',
                'alamat' => 'Jl. Ketua No. 1',
                'jenis_kelamin' => 'L',
                'created_by' => 'system',
                'user_create' => 'system',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command->info('✓ User Ketua berhasil dibuat (username: ketua, password: ketua123)');
        } else {
            $this->command->info('✓ User Ketua sudah ada');
        }

        // Create default karyawan user if not exists
        $karyawanExists = DB::table('user')->where('username', 'karyawan')->exists();

        if (!$karyawanExists) {
            DB::table('user')->insert([
                'role_id' => $karyawanRole->role_id,
                'username' => 'karyawan',
                'password' => Hash::make('karyawan123'),
                'firstname' => 'Karyawan',
                'lastname' => 'Satu',
                'email' => 'karyawan@example.com',
                'telp' => '081234567892',
                'alamat' => 'Jl. Karyawan No. 1',
                'jenis_kelamin' => 'P',
                'created_by' => 'system',
                'user_create' => 'system',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command->info('✓ User Karyawan berhasil dibuat (username: karyawan, password: karyawan123)');
        } else {
            $this->command->info('✓ User Karyawan sudah ada');
        }
    }
}
