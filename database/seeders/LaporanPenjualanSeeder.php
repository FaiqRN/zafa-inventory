<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LaporanPenjualanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('laporan_penjualan')->insert([
            [
                'laporan_id' => 'LP0001',
                'periode_awal' => '2025-04-01',
                'periode_akhir' => '2025-04-30',
                
                // Informasi penjualan toko
                // Total dari retur: 27+18+19+13+19 = 96 barang terjual melalui toko
                // (810000+540000+589000+377000+608000) = 2,924,000 pendapatan dari toko
                'total_penjualan_toko' => 2924000.00,
                'total_barang_terjual_toko' => 96,
                'total_barang_retur_toko' => 9, // 3+2+1+2+1 = 9 barang retur
                
                // Informasi penjualan pemesanan (non-toko)
                // Total pesanan: 5+3+4+2+6+10 = 30 barang terjual melalui pemesanan
                // (135000+78000+108000+52000+156000+270000) = 799,000 pendapatan dari pemesanan
                'total_penjualan_pemesanan' => 799000.00,
                'total_barang_terjual_pemesanan' => 30,
                
                // Total keseluruhan
                'total_penjualan_keseluruhan' => 3723000.00, // 2,924,000 + 799,000
                'total_barang_terjual_keseluruhan' => 126, // 96 + 30
                'total_barang_retur_keseluruhan' => 9,
                
                // Informasi tambahan
                'jumlah_toko_aktif' => 5,
                'jumlah_pemesanan' => 6,
                'barang_terlaris' => 'B0001', // Balado Teri Kacang (paling banyak terjual)
                'toko_terlaris' => 'T0001', // Toko Snack Berkah
                'catatan_laporan' => 'Laporan penjualan periode April 2025',
                'dibuat_oleh' => 'admin',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}