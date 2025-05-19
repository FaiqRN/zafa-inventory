<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TokoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('toko')->insert([
            [
                'toko_id' => 'TKO001',
                'nama_toko' => 'Risol Pastel Pasar Oro Oro Dowo',
                'pemilik' => 'Bu Kris',
                'alamat' => 'Jl. Guntur No.20, Oro-oro Dowo, Kec. Klojen, Kota Malang, Jawa Timur 65112',
                'wilayah_kecamatan' => 'Klojen',
                'wilayah_kelurahan' => 'Oro Oro Dowo',
                'wilayah_kota_kabupaten' => 'Malang',
                'nomer_telpon' => '08'
            ],
            [
                'toko_id' => 'TKO002',
                'nama_toko' => 'Twins',
                'pemilik' => 'Bu Sulis',
                'alamat' => 'Jl. Emas No.70, Purwantoro, Kec. Blimbing, Kota Malang, Jawa Timur 65122',
                'wilayah_kecamatan' => 'Blimbing',
                'wilayah_kelurahan' => 'Purwantoro',
                'wilayah_kota_kabupaten' => 'Malang',
                'nomer_telpon' => '08'
            ],
            [
                'toko_id' => 'TKO003',
                'nama_toko' => 'Toko Kue Pak Sugeng',
                'pemilik' => 'Pak Sugeng',
                'alamat' => 'Pasar Klojen Jl. Cokroaminoto No. 2, 65111, Jl. Cokroaminoto No.2 D, 3, Klojen, Kec. Klojen, Kota Malang, Jawa Timur 65111',
                'wilayah_kecamatan' => 'Klojen',
                'wilayah_kelurahan' => 'Klojen',
                'wilayah_kota_kabupaten' => 'Malang',
                'nomer_telpon' => '08'
            ]
        ]);
    }
}