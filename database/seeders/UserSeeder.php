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


        DB::table('user')->insert([
            [
                'role_id' => 1, // admin
                'username' => 'admin',
                'firstname' => 'Admin',
                'lastname' => 'System',
                'password' => Hash::make('Admin123'),
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
                'username' => 'Rinawati',
                'firstname' => 'Rinawati',
                'lastname' => 'Wulandari',
                'password' => Hash::make('Rinawati123'), 
                'foto' => null,
                'jenis_kelamin' => 'P',
                'tempat_lahir' => 'Malang',
                'tanggal_lahir' => '1985-05-15',
                'alamat' => 'Jl. Cumi-Cumi No. 1, Malang',
                'email' => 'Rinawati@gmail.com',
                'telp' => '0821-2144-1930',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'role_id' => 3, // karyawan
                'username' => 'karyawan',
                'firstname' => 'A',
                'lastname' => 'AAA',
                'password' => Hash::make('Karyawan123'), 
                'foto' => null,
                'jenis_kelamin' => 'P',
                'tempat_lahir' => 'Malang',
                'tanggal_lahir' => '1995-08-20',
                'alamat' => 'Jl. Karyawan No. 3, Malang',
                'email' => 'AAAA@zafa.com',
                'telp' => '081234567892',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'role_id' => 4, // FRN
                'username' => 'faiqrn',
                'firstname' => 'Faiq',
                'lastname' => 'Ramzy Nabighah',
                'password' => Hash::make('Luasbidang33'), 
                'foto' => null,
                'jenis_kelamin' => 'L',
                'tempat_lahir' => 'Malang',
                'tanggal_lahir' => '2004-01-30',
                'alamat' => 'Jl. Candi Mendut Selatan No. 21, Malang',
                'email' => 'Uripkoyoktaek@gmail.com',
                'telp' => '08123266006',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'role_id' => 5, // AP
                'username' => 'annisaP',
                'firstname' => 'Annisa',
                'lastname' => 'Prissilya',
                'password' => Hash::make('Arabcantik2264'), 
                'foto' => null,
                'jenis_kelamin' => 'P',
                'tempat_lahir' => 'Malang',
                'tanggal_lahir' => '2004-06-22',
                'alamat' => 'Jl. cumi-cumi No. 1, Malang',
                'email' => 'Uripngenengeneae@gmail.com',
                'telp' => '08123266006',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
        ]);
        echo "\nUsers seeded successfully!\n";
    }
}