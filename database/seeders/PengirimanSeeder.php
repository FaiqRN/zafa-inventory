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
                'pengiriman_id' => 'P001',
                'toko_id' => 'TKO001',
                'barang_id' => 'BRGOGODSVM',
                'nomer_pengiriman' => 'PNG001',
                'tanggal_pengiriman' => '2025-01-10',
                'jumlah_kirim' => 6,
                'status' => 'terkirim'
            ],
            [
                'pengiriman_id' => 'P002',
                'toko_id' => 'TKO001',
                'barang_id' => 'BRGG6WPWPU',
                'nomer_pengiriman' => 'PNG002',
                'tanggal_pengiriman' => '2025-01-10',
                'jumlah_kirim' => 6,
                'status' => 'terkirim'
            ],
            [
                'pengiriman_id' => 'P003',
                'toko_id' => 'TKO001',
                'barang_id' => 'BRGSNHRBU6',
                'nomer_pengiriman' => 'PNG003',
                'tanggal_pengiriman' => '2025-01-10',
                'jumlah_kirim' => 5,
                'status' => 'terkirim'
            ],
            [
                'pengiriman_id' => 'P004',
                'toko_id' => 'TKO001',
                'barang_id' => 'BRGQAVQCQR',
                'nomer_pengiriman' => 'PNG004',
                'tanggal_pengiriman' => '2025-01-10',
                'jumlah_kirim' => 6,
                'status' => 'terkirim'
            ],
            [
                'pengiriman_id' => 'P005',
                'toko_id' => 'TKO003',
                'barang_id' => 'BRGOGODSVM',
                'nomer_pengiriman' => 'PNG005',
                'tanggal_pengiriman' => '2025-01-04',
                'jumlah_kirim' => 15,
                'status' => 'terkirim'
            ],
            [
                'pengiriman_id' => 'P006',
                'toko_id' => 'TKO003',
                'barang_id' => 'BRGG6WPWPU',
                'nomer_pengiriman' => 'PNG006',
                'tanggal_pengiriman' => '2025-01-04',
                'jumlah_kirim' => 15,
                'status' => 'terkirim'
            ],
            [
                'pengiriman_id' => 'P007',
                'toko_id' => 'TKO003',
                'barang_id' => 'BRGHRUHXIF',
                'nomer_pengiriman' => 'PNG007',
                'tanggal_pengiriman' => '2025-01-04',
                'jumlah_kirim' => 10,
                'status' => 'terkirim'
            ],
            [
                'pengiriman_id' => 'P008',
                'toko_id' => 'TKO003',
                'barang_id' => 'BRGSNHRBU6',
                'nomer_pengiriman' => 'PNG008',
                'tanggal_pengiriman' => '2025-01-04',
                'jumlah_kirim' => 20,
                'status' => 'terkirim'
            ],
            [
                'pengiriman_id' => 'P009',
                'toko_id' => 'TKO003',
                'barang_id' => 'BRGQAVQCQR',
                'nomer_pengiriman' => 'PNG009',
                'tanggal_pengiriman' => '2025-01-04',
                'jumlah_kirim' => 15,
                'status' => 'terkirim'
            ],
            [
                'pengiriman_id' => 'P010',
                'toko_id' => 'TKO002',
                'barang_id' => 'BRGOGODSVM',
                'nomer_pengiriman' => 'PNG010',
                'tanggal_pengiriman' => '2025-01-07',
                'jumlah_kirim' => 6,
                'status' => 'terkirim'
            ],
            [
                'pengiriman_id' => 'P011',
                'toko_id' => 'TKO002',
                'barang_id' => 'BRGG6WPWPU',
                'nomer_pengiriman' => 'PNG011',
                'tanggal_pengiriman' => '2025-01-07',
                'jumlah_kirim' => 6,
                'status' => 'terkirim'
            ],
            [
                'pengiriman_id' => 'P012',
                'toko_id' => 'TKO002',
                'barang_id' => 'BRGHRUHXIF',
                'nomer_pengiriman' => 'PNG012',
                'tanggal_pengiriman' => '2025-01-07',
                'jumlah_kirim' => 5,
                'status' => 'terkirim'
            ],
            [
                'pengiriman_id' => 'P013',
                'toko_id' => 'TKO002',
                'barang_id' => 'BRGSNHRBU6',
                'nomer_pengiriman' => 'PNG013',
                'tanggal_pengiriman' => '2025-01-07',
                'jumlah_kirim' => 6,
                'status' => 'terkirim'
            ],
            [
                'pengiriman_id' => 'P014',
                'toko_id' => 'TKO002',
                'barang_id' => 'BRGQAVQCQR',
                'nomer_pengiriman' => 'PNG014',
                'tanggal_pengiriman' => '2025-01-07',
                'jumlah_kirim' => 6,
                'status' => 'terkirim'
            ]
        ]);
    }
}