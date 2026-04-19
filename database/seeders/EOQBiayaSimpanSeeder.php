<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EOQBiayaSimpanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $barangs = DB::table('barang')
            ->select('barang_id', 'harga_awal_barang')
            ->get();

        if ($barangs->isEmpty()) {
            return;
        }

        $defaultKomponen = [
            [
                'nama_komponen' => 'Biaya modal tertahan',
                'persentase' => 15.00,
                'keterangan' => 'Cost of capital untuk stok yang tersimpan',
            ],
            [
                'nama_komponen' => 'Risiko kerusakan/expired',
                'persentase' => 7.00,
                'keterangan' => 'Estimasi risiko produk rusak atau kedaluwarsa',
            ],
            [
                'nama_komponen' => 'Biaya penyimpanan fisik',
                'persentase' => 3.00,
                'keterangan' => 'Biaya rak, gudang, handling, dan utilitas',
            ],
        ];

        $rows = [];
        $now = now();

        foreach ($barangs as $barang) {
            $hargaPokok = (float) ($barang->harga_awal_barang ?? 0);

            foreach ($defaultKomponen as $komponen) {
                $rows[] = [
                    'barang_id' => $barang->barang_id,
                    'harga_pokok' => $hargaPokok,
                    'nama_komponen' => $komponen['nama_komponen'],
                    'persentase' => $komponen['persentase'],
                    'keterangan' => $komponen['keterangan'],
                    'user_create' => 'system',
                    'user_update' => 'system',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('eoq_biaya_simpan')->upsert(
            $rows,
            ['barang_id', 'nama_komponen'],
            ['harga_pokok', 'persentase', 'keterangan', 'user_update', 'updated_at']
        );
    }
}
