<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EoqBiayaPesanGlobalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('eoq_biaya_pesan_global')->insert([
            [
                'nama_biaya' => 'Biaya tenaga packing',
                'nominal'    => 5000,
                'keterangan' => 'Upah tenaga packing per order',
                'user_create' => 'admin',
                'user_update' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_biaya' => 'Biaya pengiriman',
                'nominal'    => 10000,
                'keterangan' => 'Ongkos kirim per order',
                'user_create' => 'admin',
                'user_update' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_biaya' => 'Biaya komunikasi',
                'nominal'    => 1000,
                'keterangan' => 'Biaya telepon atau WhatsApp untuk koordinasi pengiriman',
                'user_create' => 'admin',
                'user_update' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_biaya' => 'Biaya administrasi',
                'nominal'    => 1000,
                'keterangan' => 'Cetak nota dan surat jalan per order',
                'user_create' => 'admin',
                'user_update' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}