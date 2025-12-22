<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BarangSeeder extends Seeder
{
    public function run(): void
    {
        $barang = [
            [
                'barang_id' => 'BRG0000001',
                'barang_kode' => 'BRG1',
                'nama_barang' => 'Kering Kentang',
                'harga_awal_barang' => 25000.00,
                'stok' => 1407,
                'satuan' => 'Pcs',
                'keterangan' => 'Varian kering kentang lebar dengan bumbu pedas manis yang lezat',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'barang_id' => 'BRG0000002',
                'barang_kode' => 'BRG2',
                'nama_barang' => 'Kering Kentang Tempe',
                'harga_awal_barang' => 25000.00,
                'stok' => 1333,
                'satuan' => 'Pcs',
                'keterangan' => 'Varian kering kentang lebar dipadukan dengan potongan tempe yang dibumbui pedas manis yang lezat',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'barang_id' => 'BRG0000003',
                'barang_kode' => 'BRG3',
                'nama_barang' => 'Kering Kentang Original',
                'harga_awal_barang' => 25000.00,
                'stok' => 127,
                'satuan' => 'Pcs',
                'keterangan' => 'Varian kering kentang dengan potongan lebar yang gurih original',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'barang_id' => 'BRG0000004',
                'barang_kode' => 'BRG4',
                'nama_barang' => 'Kering Kentang Mustofa Teri',
                'harga_awal_barang' => 26000.00,
                'stok' => 1154,
                'satuan' => 'Pcs',
                'keterangan' => 'Varian kering kentang dengan potongan memanjang dipadukan dengan ikan teri yang gurih dipadukan dengan bumbu pedas manis yang lezat',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'barang_id' => 'BRG0000005',
                'barang_kode' => 'BRG5',
                'nama_barang' => 'Balado Teri Kacang',
                'harga_awal_barang' => 26000.00,
                'stok' => 1263,
                'satuan' => 'Pcs',
                'keterangan' => 'Varian ikan teri dan kacang yang manis gurih dipadukan dengan bumbu pedas manis yang lezat',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('barang')->insert($barang);
    }
}
