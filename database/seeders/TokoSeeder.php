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
                'toko_id' => 'T0001',
                'nama_toko' => 'Toko Snack Berkah',
                'pemilik' => 'Ahmad Suherman',
                'alamat' => 'Jl. Pasar Baru No. 15, Blok C',
                'wilayah_kecamatan' => 'Cicendo',
                'wilayah_kelurahan' => 'Pasir Kaliki',
                'wilayah_kota_kabupaten' => 'Bandung',
                'nomer_telpon' => '081122334455',
            ],
            [
                'toko_id' => 'T0002',
                'nama_toko' => 'Minimarket Sejahtera',
                'pemilik' => 'Budi Santoso',
                'alamat' => 'Jl. Raya Timur No. 78',
                'wilayah_kecamatan' => 'Cikajang',
                'wilayah_kelurahan' => 'Mekarsari',
                'wilayah_kota_kabupaten' => 'Garut',
                'nomer_telpon' => '082233445566',
            ],
            [
                'toko_id' => 'T0003',
                'nama_toko' => 'Warung Camilan Sari',
                'pemilik' => 'Siti Aminah',
                'alamat' => 'Jl. Kartini No. 45',
                'wilayah_kecamatan' => 'Coblong',
                'wilayah_kelurahan' => 'Dago',
                'wilayah_kota_kabupaten' => 'Bandung',
                'nomer_telpon' => '083344556677',
            ],
            [
                'toko_id' => 'T0004',
                'nama_toko' => 'Toko Oleh-Oleh Cihampelas',
                'pemilik' => 'Dedi Mulyadi',
                'alamat' => 'Jl. Cihampelas No. 123',
                'wilayah_kecamatan' => 'Coblong',
                'wilayah_kelurahan' => 'Cipaganti',
                'wilayah_kota_kabupaten' => 'Bandung',
                'nomer_telpon' => '087788990011',
            ],
            [
                'toko_id' => 'T0005',
                'nama_toko' => 'Warung Kopi & Snack',
                'pemilik' => 'Joko Widodo',
                'alamat' => 'Jl. Asia Afrika No. 56',
                'wilayah_kecamatan' => 'Regol',
                'wilayah_kelurahan' => 'Braga',
                'wilayah_kota_kabupaten' => 'Bandung',
                'nomer_telpon' => '089977665544',
            ],
        ]);
    }
}