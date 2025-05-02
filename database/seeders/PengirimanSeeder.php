<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PengirimanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('pengiriman')->insert([
            // Pengiriman ke Toko Snack Berkah
            [
                'pengiriman_id' => 'P0001',
                'toko_id' => 'T0001',
                'barang_id' => 'B0001',
                'nomer_pengiriman' => 'KIRIM/2025/04/001',
                'tanggal_pengiriman' => '2025-04-01',
                'jumlah_kirim' => 30,
                'status' => 'terkirim',
            ],
            [
                'pengiriman_id' => 'P0002',
                'toko_id' => 'T0001',
                'barang_id' => 'B0002',
                'nomer_pengiriman' => 'KIRIM/2025/04/002',
                'tanggal_pengiriman' => '2025-04-01',
                'jumlah_kirim' => 25,
                'status' => 'terkirim',
            ],
            [
                'pengiriman_id' => 'P0003',
                'toko_id' => 'T0001',
                'barang_id' => 'B0003',
                'nomer_pengiriman' => 'KIRIM/2025/04/003',
                'tanggal_pengiriman' => '2025-04-01',
                'jumlah_kirim' => 25,
                'status' => 'terkirim',
            ],
            [
                'pengiriman_id' => 'P0004',
                'toko_id' => 'T0001',
                'barang_id' => 'B0004',
                'nomer_pengiriman' => 'KIRIM/2025/04/004',
                'tanggal_pengiriman' => '2025-04-01',
                'jumlah_kirim' => 20,
                'status' => 'terkirim',
            ],
            [
                'pengiriman_id' => 'P0005',
                'toko_id' => 'T0001',
                'barang_id' => 'B0005',
                'nomer_pengiriman' => 'KIRIM/2025/04/005',
                'tanggal_pengiriman' => '2025-04-01',
                'jumlah_kirim' => 20,
                'status' => 'terkirim',
            ],
            
            // Pengiriman ke Minimarket Sejahtera
            [
                'pengiriman_id' => 'P0006',
                'toko_id' => 'T0002',
                'barang_id' => 'B0001',
                'nomer_pengiriman' => 'KIRIM/2025/04/006',
                'tanggal_pengiriman' => '2025-04-05',
                'jumlah_kirim' => 20,
                'status' => 'terkirim',
            ],
            [
                'pengiriman_id' => 'P0007',
                'toko_id' => 'T0002',
                'barang_id' => 'B0003',
                'nomer_pengiriman' => 'KIRIM/2025/04/007',
                'tanggal_pengiriman' => '2025-04-05',
                'jumlah_kirim' => 15,
                'status' => 'terkirim',
            ],
            [
                'pengiriman_id' => 'P0008',
                'toko_id' => 'T0002',
                'barang_id' => 'B0005',
                'nomer_pengiriman' => 'KIRIM/2025/04/008',
                'tanggal_pengiriman' => '2025-04-05',
                'jumlah_kirim' => 15,
                'status' => 'terkirim',
            ],
            
            // Pengiriman ke Warung Camilan Sari
            [
                'pengiriman_id' => 'P0009',
                'toko_id' => 'T0003',
                'barang_id' => 'B0001',
                'nomer_pengiriman' => 'KIRIM/2025/04/009',
                'tanggal_pengiriman' => '2025-04-10',
                'jumlah_kirim' => 15,
                'status' => 'terkirim',
            ],
            [
                'pengiriman_id' => 'P0010',
                'toko_id' => 'T0003',
                'barang_id' => 'B0002',
                'nomer_pengiriman' => 'KIRIM/2025/04/010',
                'tanggal_pengiriman' => '2025-04-10',
                'jumlah_kirim' => 15,
                'status' => 'terkirim',
            ],
            [
                'pengiriman_id' => 'P0011',
                'toko_id' => 'T0003',
                'barang_id' => 'B0003',
                'nomer_pengiriman' => 'KIRIM/2025/04/011',
                'tanggal_pengiriman' => '2025-04-10',
                'jumlah_kirim' => 15,
                'status' => 'terkirim',
            ],
            [
                'pengiriman_id' => 'P0012',
                'toko_id' => 'T0003',
                'barang_id' => 'B0004',
                'nomer_pengiriman' => 'KIRIM/2025/04/012',
                'tanggal_pengiriman' => '2025-04-10',
                'jumlah_kirim' => 10,
                'status' => 'terkirim',
            ],
            
            // Pengiriman ke Toko Oleh-Oleh Cihampelas
            [
                'pengiriman_id' => 'P0013',
                'toko_id' => 'T0004',
                'barang_id' => 'B0001',
                'nomer_pengiriman' => 'KIRIM/2025/04/013',
                'tanggal_pengiriman' => '2025-04-15',
                'jumlah_kirim' => 25,
                'status' => 'terkirim',
            ],
            [
                'pengiriman_id' => 'P0014',
                'toko_id' => 'T0004',
                'barang_id' => 'B0004',
                'nomer_pengiriman' => 'KIRIM/2025/04/014',
                'tanggal_pengiriman' => '2025-04-15',
                'jumlah_kirim' => 20,
                'status' => 'terkirim',
            ],
            [
                'pengiriman_id' => 'P0015',
                'toko_id' => 'T0004',
                'barang_id' => 'B0005',
                'nomer_pengiriman' => 'KIRIM/2025/04/015',
                'tanggal_pengiriman' => '2025-04-15',
                'jumlah_kirim' => 20,
                'status' => 'terkirim',
            ],
            
            // Pengiriman ke Warung Kopi & Snack
            [
                'pengiriman_id' => 'P0016',
                'toko_id' => 'T0005',
                'barang_id' => 'B0001',
                'nomer_pengiriman' => 'KIRIM/2025/04/016',
                'tanggal_pengiriman' => '2025-04-20',
                'jumlah_kirim' => 10,
                'status' => 'terkirim',
            ],
            [
                'pengiriman_id' => 'P0017',
                'toko_id' => 'T0005',
                'barang_id' => 'B0003',
                'nomer_pengiriman' => 'KIRIM/2025/04/017',
                'tanggal_pengiriman' => '2025-04-20',
                'jumlah_kirim' => 10,
                'status' => 'terkirim',
            ],
        ]);
    }
}