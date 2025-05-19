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
                'retur_id' => 1,
                'pengiriman_id' => 'P010',
                'toko_id' => 'TKO002',
                'barang_id' => 'BRGOGODSVM',
                'nomer_pengiriman' => 'PNG010',
                'tanggal_pengiriman' => '2025-01-07',
                'tanggal_retur' => '2025-01-23',
                'harga_awal_barang' => 25000.00,
                'jumlah_kirim' => 6,
                'jumlah_retur' => 4,
                'total_terjual' => 2,
                'hasil' => 50000.00,
                'kondisi' => 'Tidak Laku',
                'keterangan' => null
            ],
            [
                'retur_id' => 3,
                'pengiriman_id' => 'P011',
                'toko_id' => 'TKO002',
                'barang_id' => 'BRGG6WPWPU',
                'nomer_pengiriman' => 'PNG011',
                'tanggal_pengiriman' => '2025-01-07',
                'tanggal_retur' => '2025-01-23',
                'harga_awal_barang' => 25000.00,
                'jumlah_kirim' => 6,
                'jumlah_retur' => 1,
                'total_terjual' => 5,
                'hasil' => 125000.00,
                'kondisi' => 'Tidak Laku',
                'keterangan' => null
            ],
            [
                'retur_id' => 4,
                'pengiriman_id' => 'P013',
                'toko_id' => 'TKO002',
                'barang_id' => 'BRGSNHRBU6',
                'nomer_pengiriman' => 'PNG013',
                'tanggal_pengiriman' => '2025-01-07',
                'tanggal_retur' => '2025-01-23',
                'harga_awal_barang' => 26000.00,
                'jumlah_kirim' => 6,
                'jumlah_retur' => 1,
                'total_terjual' => 5,
                'hasil' => 130000.00,
                'kondisi' => 'Tidak Laku',
                'keterangan' => null
            ],
            [
                'retur_id' => 5,
                'pengiriman_id' => 'P012',
                'toko_id' => 'TKO002',
                'barang_id' => 'BRGHRUHXIF',
                'nomer_pengiriman' => 'PNG012',
                'tanggal_pengiriman' => '2025-01-07',
                'tanggal_retur' => '2025-01-23',
                'harga_awal_barang' => 25000.00,
                'jumlah_kirim' => 5,
                'jumlah_retur' => 1,
                'total_terjual' => 4,
                'hasil' => 100000.00,
                'kondisi' => 'Tidak Laku',
                'keterangan' => null
            ],
            [
                'retur_id' => 6,
                'pengiriman_id' => 'P014',
                'toko_id' => 'TKO002',
                'barang_id' => 'BRGQAVQCQR',
                'nomer_pengiriman' => 'PNG014',
                'tanggal_pengiriman' => '2025-01-07',
                'tanggal_retur' => '2025-01-23',
                'harga_awal_barang' => 26000.00,
                'jumlah_kirim' => 6,
                'jumlah_retur' => 1,
                'total_terjual' => 5,
                'hasil' => 130000.00,
                'kondisi' => 'Tidak Laku',
                'keterangan' => null
            ]
        ]);
    }
}