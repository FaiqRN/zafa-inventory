<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BarangTokoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('barang_toko')->insert([
            // Toko Snack Berkah (T0001) - semua produk
            [
                'barang_toko_id' => 'BT0001',
                'toko_id' => 'T0001',
                'barang_id' => 'B0001',
                'harga_barang_toko' => 30000.00, // Markup untuk toko
            ],
            [
                'barang_toko_id' => 'BT0002',
                'toko_id' => 'T0001',
                'barang_id' => 'B0002',
                'harga_barang_toko' => 29000.00,
            ],
            [
                'barang_toko_id' => 'BT0003',
                'toko_id' => 'T0001',
                'barang_id' => 'B0003',
                'harga_barang_toko' => 29000.00,
            ],
            [
                'barang_toko_id' => 'BT0004',
                'toko_id' => 'T0001',
                'barang_id' => 'B0004',
                'harga_barang_toko' => 30000.00,
            ],
            [
                'barang_toko_id' => 'BT0005',
                'toko_id' => 'T0001',
                'barang_id' => 'B0005',
                'harga_barang_toko' => 29000.00,
            ],
            
            // Minimarket Sejahtera (T0002) - 3 produk
            [
                'barang_toko_id' => 'BT0006',
                'toko_id' => 'T0002',
                'barang_id' => 'B0001',
                'harga_barang_toko' => 31000.00,
            ],
            [
                'barang_toko_id' => 'BT0007',
                'toko_id' => 'T0002',
                'barang_id' => 'B0003',
                'harga_barang_toko' => 30000.00,
            ],
            [
                'barang_toko_id' => 'BT0008',
                'toko_id' => 'T0002',
                'barang_id' => 'B0005',
                'harga_barang_toko' => 30000.00,
            ],
            
            // Warung Camilan Sari (T0003) - 4 produk
            [
                'barang_toko_id' => 'BT0009',
                'toko_id' => 'T0003',
                'barang_id' => 'B0001',
                'harga_barang_toko' => 30000.00,
            ],
            [
                'barang_toko_id' => 'BT0010',
                'toko_id' => 'T0003',
                'barang_id' => 'B0002',
                'harga_barang_toko' => 29000.00,
            ],
            [
                'barang_toko_id' => 'BT0011',
                'toko_id' => 'T0003',
                'barang_id' => 'B0003',
                'harga_barang_toko' => 29000.00,
            ],
            [
                'barang_toko_id' => 'BT0012',
                'toko_id' => 'T0003',
                'barang_id' => 'B0004',
                'harga_barang_toko' => 30000.00,
            ],
            
            // Toko Oleh-Oleh Cihampelas (T0004) - 3 produk
            [
                'barang_toko_id' => 'BT0013',
                'toko_id' => 'T0004',
                'barang_id' => 'B0001',
                'harga_barang_toko' => 32000.00,
            ],
            [
                'barang_toko_id' => 'BT0014',
                'toko_id' => 'T0004',
                'barang_id' => 'B0004',
                'harga_barang_toko' => 32000.00,
            ],
            [
                'barang_toko_id' => 'BT0015',
                'toko_id' => 'T0004',
                'barang_id' => 'B0005',
                'harga_barang_toko' => 31000.00,
            ],
            
            // Warung Kopi & Snack (T0005) - 2 produk
            [
                'barang_toko_id' => 'BT0016',
                'toko_id' => 'T0005',
                'barang_id' => 'B0001',
                'harga_barang_toko' => 30000.00,
            ],
            [
                'barang_toko_id' => 'BT0017',
                'toko_id' => 'T0005',
                'barang_id' => 'B0003',
                'harga_barang_toko' => 29000.00,
            ],
        ]);
    }
}