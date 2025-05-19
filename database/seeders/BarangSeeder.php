<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BarangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('barang')->insert([
            [
                'barang_id' => 'BRGG6WPWPU',
                'barang_kode' => 'BRG002',
                'nama_barang' => 'Kering Kentang Tempe',
                'harga_awal_barang' => 25000.00,
                'satuan' => 'Pcs',
                'keterangan' => 'Olahan Kering Kentang yang dipadukan dengan Tempe yang kriuk dengan bumbu pedas manis yang lezat',
                'is_deleted' => 0
            ],
            [
                'barang_id' => 'BRGHRUHXIF',
                'barang_kode' => 'BRG003',
                'nama_barang' => 'Kering Kentang Original',
                'harga_awal_barang' => 25000.00,
                'satuan' => 'Pcs',
                'keterangan' => 'Olahan kentang original yang gurih dan kriuk',
                'is_deleted' => 0
            ],
            [
                'barang_id' => 'BRGOGODSVM',
                'barang_kode' => 'BRG001',
                'nama_barang' => 'Kering Kentang',
                'harga_awal_barang' => 25000.00,
                'satuan' => 'Pcs',
                'keterangan' => 'Olahan Kentang yang diiris tipis lebar dengan bumbu pedas manis',
                'is_deleted' => 0
            ],
            [
                'barang_id' => 'BRGQAVQCQR',
                'barang_kode' => 'BRG005',
                'nama_barang' => 'Balado Teri Kacang',
                'harga_awal_barang' => 26000.00,
                'satuan' => 'Pcs',
                'keterangan' => 'Olahan teri dan kacang yang gurih dengan bumbu pedas manis yang lezat',
                'is_deleted' => 0
            ],
            [
                'barang_id' => 'BRGSNHRBU6',
                'barang_kode' => 'BRG004',
                'nama_barang' => 'Kering kentang Mustofa Teri',
                'harga_awal_barang' => 26000.00,
                'satuan' => 'Pcs',
                'keterangan' => 'olahan kering kentang yang panjang dengan perpaduan ikan teri yang gurih dengan bumbu pedas manis',
                'is_deleted' => 0
            ]
        ]);
    }
}