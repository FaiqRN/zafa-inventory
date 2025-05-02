<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReturSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('retur')->insert([
            // Retur dari Toko Snack Berkah
            [
                'pengiriman_id' => 'P0001',
                'toko_id' => 'T0001',
                'barang_id' => 'B0001',
                'nomer_pengiriman' => 'KIRIM/2025/04/001',
                'tanggal_pengiriman' => '2025-04-01',
                'tanggal_retur' => '2025-04-25',
                'harga_awal_barang' => 27000.00,
                'jumlah_kirim' => 30,
                'jumlah_retur' => 3,
                'total_terjual' => 27,
                'hasil' => 810000.00, // 27 * 30000
                'kondisi' => 'kemasan rusak',
                'keterangan' => 'Kemasan bocor pada beberapa bagian',
            ],
            [
                'pengiriman_id' => 'P0004',
                'toko_id' => 'T0001',
                'barang_id' => 'B0004',
                'nomer_pengiriman' => 'KIRIM/2025/04/004',
                'tanggal_pengiriman' => '2025-04-01',
                'tanggal_retur' => '2025-04-25',
                'harga_awal_barang' => 27000.00,
                'jumlah_kirim' => 20,
                'jumlah_retur' => 2,
                'total_terjual' => 18,
                'hasil' => 540000.00, // 18 * 30000
                'kondisi' => 'kualitas kurang baik',
                'keterangan' => 'Produk kurang renyah',
            ],
            
            // Retur dari Minimarket Sejahtera
            [
                'pengiriman_id' => 'P0006',
                'toko_id' => 'T0002',
                'barang_id' => 'B0001',
                'nomer_pengiriman' => 'KIRIM/2025/04/006',
                'tanggal_pengiriman' => '2025-04-05',
                'tanggal_retur' => '2025-04-28',
                'harga_awal_barang' => 27000.00,
                'jumlah_kirim' => 20,
                'jumlah_retur' => 1,
                'total_terjual' => 19,
                'hasil' => 589000.00, // 19 * 31000
                'kondisi' => 'kadaluarsa',
                'keterangan' => 'Mendekati tanggal kadaluarsa',
            ],
            
            // Retur dari Warung Camilan Sari
            [
                'pengiriman_id' => 'P0010',
                'toko_id' => 'T0003',
                'barang_id' => 'B0002',
                'nomer_pengiriman' => 'KIRIM/2025/04/010',
                'tanggal_pengiriman' => '2025-04-10',
                'tanggal_retur' => '2025-04-29',
                'harga_awal_barang' => 26000.00,
                'jumlah_kirim' => 15,
                'jumlah_retur' => 2,
                'total_terjual' => 13,
                'hasil' => 377000.00, // 13 * 29000
                'kondisi' => 'kemasan rusak',
                'keterangan' => 'Kemasan penyok',
            ],
            
            // Retur dari Toko Oleh-Oleh Cihampelas
            [
                'pengiriman_id' => 'P0014',
                'toko_id' => 'T0004',
                'barang_id' => 'B0004',
                'nomer_pengiriman' => 'KIRIM/2025/04/014',
                'tanggal_pengiriman' => '2025-04-15',
                'tanggal_retur' => '2025-04-30',
                'harga_awal_barang' => 27000.00,
                'jumlah_kirim' => 20,
                'jumlah_retur' => 1,
                'total_terjual' => 19,
                'hasil' => 608000.00, // 19 * 32000
                'kondisi' => 'tidak laku',
                'keterangan' => 'Kurang diminati konsumen',
            ],
        ]);
    }
}