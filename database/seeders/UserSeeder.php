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
                'username' => 'admin',
                'nama_lengkap' => 'Administrator',
                'password' => Hash::make('admin123'),
                'foto' => null,
                'jenis_kelamin' => 'L',
                'tempat_lahir' => 'Jakarta',
                'tanggal_lahir' => '1990-01-01',
                'alamat' => 'Jl. Admin No. 1, Jakarta',
                'email' => 'admin@zafa.com',
                'telp' => '081234567890',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'username' => 'owner',
                'nama_lengkap' => 'Pemilik Toko',
                'password' => Hash::make('owner123'),
                'foto' => null,
                'jenis_kelamin' => 'L',
                'tempat_lahir' => 'Bandung',
                'tanggal_lahir' => '1985-05-15',
                'alamat' => 'Jl. Pemilik No. 1, Bandung',
                'email' => 'owner@zafa.com',
                'telp' => '081234567891',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'username' => 'user1',
                'nama_lengkap' => 'Staff Toko',
                'password' => Hash::make('user123'),
                'foto' => null,
                'jenis_kelamin' => 'P',
                'tempat_lahir' => 'Surabaya',
                'tanggal_lahir' => '1995-10-20',
                'alamat' => 'Jl. Staff No. 1, Surabaya',
                'email' => 'staff@zafa.com',
                'telp' => '081234567892',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}