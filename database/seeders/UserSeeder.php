<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // Seed users
        // IMPORTANT: Gunakan Hash::make() karena DB::table() bypass model events
        // Password requirements: min 8 chars, uppercase, lowercase, numbers
        DB::table('user')->insert([
            [
                'role_id' => 1, // admin
                'username' => 'admin',
                'firstname' => 'Admin',
                'lastname' => 'System',
                'password' => Hash::make('Admin123'), // Strong password
                'foto' => null,
                'jenis_kelamin' => 'L',
                'tempat_lahir' => 'Jakarta',
                'tanggal_lahir' => '1990-01-01',
                'alamat' => 'Jl. Admin No. 1, Jakarta',
                'email' => 'admin@zafa.com',
                'telp' => '081234567890',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'role_id' => 2, // ketua
                'username' => 'ketua',
                'firstname' => 'Budi',
                'lastname' => 'Santoso',
                'password' => Hash::make('Ketua123'), // Strong password
                'foto' => null,
                'jenis_kelamin' => 'L',
                'tempat_lahir' => 'Bandung',
                'tanggal_lahir' => '1985-05-15',
                'alamat' => 'Jl. Ketua No. 2, Bandung',
                'email' => 'budi.santoso@zafa.com',
                'telp' => '081234567891',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'role_id' => 3, // karyawan
                'username' => 'karyawan',
                'firstname' => 'Siti',
                'lastname' => 'Nurhaliza',
                'password' => Hash::make('Karyawan123'), // Strong password
                'foto' => null,
                'jenis_kelamin' => 'P',
                'tempat_lahir' => 'Surabaya',
                'tanggal_lahir' => '1995-08-20',
                'alamat' => 'Jl. Karyawan No. 3, Surabaya',
                'email' => 'siti.nurhaliza@zafa.com',
                'telp' => '081234567892',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        echo "\nRoles and Users seeded successfully!\n";
        echo "\nLogin credentials (Strong passwords - min 8 chars, uppercase, lowercase, numbers):\n";
        echo "1. Admin    - username: admin     password: Admin123\n";
        echo "2. Ketua    - username: ketua     password: Ketua123\n";
        echo "3. Karyawan - username: karyawan  password: Karyawan123\n\n";
    }
}