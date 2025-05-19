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
            // Toko TKO001
            [
                'barang_toko_id' => 'BT001',
                'toko_id' => 'TKO001',
                'barang_id' => 'BRGOGODSVM',
                'harga_barang_toko' => 28000.00
            ],
            [
                'barang_toko_id' => 'BT002',
                'toko_id' => 'TKO001',
                'barang_id' => 'BRGG6WPWPU',
                'harga_barang_toko' => 28000.00
            ],
            [
                'barang_toko_id' => 'BT003',
                'toko_id' => 'TKO001',
                'barang_id' => 'BRGHRUHXIF',
                'harga_barang_toko' => 28000.00
            ],
            [
                'barang_toko_id' => 'BT004',
                'toko_id' => 'TKO001',
                'barang_id' => 'BRGQAVQCQR',
                'harga_barang_toko' => 29000.00
            ],
            [
                'barang_toko_id' => 'BT005',
                'toko_id' => 'TKO001',
                'barang_id' => 'BRGSNHRBU6',
                'harga_barang_toko' => 29000.00
            ],
            // Toko TKO003
            [
                'barang_toko_id' => 'BT006',
                'toko_id' => 'TKO003',
                'barang_id' => 'BRGOGODSVM',
                'harga_barang_toko' => 28000.00
            ],
            [
                'barang_toko_id' => 'BT007',
                'toko_id' => 'TKO003',
                'barang_id' => 'BRGG6WPWPU',
                'harga_barang_toko' => 28000.00
            ],
            [
                'barang_toko_id' => 'BT008',
                'toko_id' => 'TKO003',
                'barang_id' => 'BRGHRUHXIF',
                'harga_barang_toko' => 28000.00
            ],
            [
                'barang_toko_id' => 'BT009',
                'toko_id' => 'TKO003',
                'barang_id' => 'BRGQAVQCQR',
                'harga_barang_toko' => 29000.00
            ],
            [
                'barang_toko_id' => 'BT010',
                'toko_id' => 'TKO003',
                'barang_id' => 'BRGSNHRBU6',
                'harga_barang_toko' => 29000.00
            ],
            // Toko TKO002
            [
                'barang_toko_id' => 'BT011',
                'toko_id' => 'TKO002',
                'barang_id' => 'BRGOGODSVM',
                'harga_barang_toko' => 27000.00
            ],
            [
                'barang_toko_id' => 'BT012',
                'toko_id' => 'TKO002',
                'barang_id' => 'BRGG6WPWPU',
                'harga_barang_toko' => 27000.00
            ],
            [
                'barang_toko_id' => 'BT013',
                'toko_id' => 'TKO002',
                'barang_id' => 'BRGHRUHXIF',
                'harga_barang_toko' => 27000.00
            ],
            [
                'barang_toko_id' => 'BT014',
                'toko_id' => 'TKO002',
                'barang_id' => 'BRGQAVQCQR',
                'harga_barang_toko' => 29000.00
            ],
            [
                'barang_toko_id' => 'BT015',
                'toko_id' => 'TKO002',
                'barang_id' => 'BRGSNHRBU6',
                'harga_barang_toko' => 29000.00
            ]
        ]);
    }
}