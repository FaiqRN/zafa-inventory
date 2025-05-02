<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PemesananSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('pemesanan')->insert([
            [
                'pemesanan_id' => 'PS0001',
                'barang_id' => 'B0001',
                'nama_pemesan' => 'Anita Wijaya',
                'tanggal_pemesanan' => '2025-04-02',
                'alamat_pemesan' => 'Jl. Teratai No. 45, Jakarta Selatan',
                'jumlah_pesanan' => 5,
                'total' => 135000.00, // 5 * 27000
                'pemesanan_dari' => 'instagram',
                'metode_pembayaran' => 'transfer',
                'status_pemesanan' => 'selesai',
                'no_telp_pemesan' => '081234567001',
                'email_pemesan' => 'anita.wijaya@gmail.com',
                'catatan_pemesanan' => 'Tolong kirim secepatnya',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'pemesanan_id' => 'PS0002',
                'barang_id' => 'B0003',
                'nama_pemesan' => 'Deni Hermawan',
                'tanggal_pemesanan' => '2025-04-05',
                'alamat_pemesan' => 'Jl. Merdeka No. 17, Bandung',
                'jumlah_pesanan' => 3,
                'total' => 78000.00, // 3 * 26000
                'pemesanan_dari' => 'whatsapp',
                'metode_pembayaran' => 'qris',
                'status_pemesanan' => 'selesai',
                'no_telp_pemesan' => '081234567002',
                'email_pemesan' => 'deni.hermawan@yahoo.com',
                'catatan_pemesanan' => 'Untuk oleh-oleh',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'pemesanan_id' => 'PS0003',
                'barang_id' => 'B0004',
                'nama_pemesan' => 'Rini Susanti',
                'tanggal_pemesanan' => '2025-04-10',
                'alamat_pemesan' => 'Jl. Anggrek No. 23, Surabaya',
                'jumlah_pesanan' => 4,
                'total' => 108000.00, // 4 * 27000
                'pemesanan_dari' => 'shopee',
                'metode_pembayaran' => 'transfer',
                'status_pemesanan' => 'selesai',
                'no_telp_pemesan' => '081234567003',
                'email_pemesan' => 'rini.susanti@outlook.com',
                'catatan_pemesanan' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'pemesanan_id' => 'PS0004',
                'barang_id' => 'B0002',
                'nama_pemesan' => 'Hadi Santoso',
                'tanggal_pemesanan' => '2025-04-15',
                'alamat_pemesan' => 'Jl. Mawar No. 7, Malang',
                'jumlah_pesanan' => 2,
                'total' => 52000.00, // 2 * 26000
                'pemesanan_dari' => 'instagram',
                'metode_pembayaran' => 'transfer',
                'status_pemesanan' => 'diproses',
                'no_telp_pemesan' => '081234567004',
                'email_pemesan' => 'hadi.santoso@gmail.com',
                'catatan_pemesanan' => 'Mohon dikirim hari ini',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'pemesanan_id' => 'PS0005',
                'barang_id' => 'B0005',
                'nama_pemesan' => 'Dewi Anggraini',
                'tanggal_pemesanan' => '2025-04-20',
                'alamat_pemesan' => 'Jl. Dahlia No. 15, Semarang',
                'jumlah_pesanan' => 6,
                'total' => 156000.00, // 6 * 26000
                'pemesanan_dari' => 'whatsapp',
                'metode_pembayaran' => 'qris',
                'status_pemesanan' => 'diproses',
                'no_telp_pemesan' => '081234567005',
                'email_pemesan' => 'dewi.anggraini@yahoo.co.id',
                'catatan_pemesanan' => 'Untuk acara arisan keluarga',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'pemesanan_id' => 'PS0006',
                'barang_id' => 'B0001',
                'nama_pemesan' => 'Joko Susilo',
                'tanggal_pemesanan' => '2025-04-22',
                'alamat_pemesan' => 'Jl. Flamboyan No. 8, Yogyakarta',
                'jumlah_pesanan' => 10,
                'total' => 270000.00, // 10 * 27000
                'pemesanan_dari' => 'tokopedia',
                'metode_pembayaran' => 'transfer',
                'status_pemesanan' => 'pending',
                'no_telp_pemesan' => '081234567006',
                'email_pemesan' => 'joko.susilo@gmail.com',
                'catatan_pemesanan' => 'Untuk acara hajatan',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}