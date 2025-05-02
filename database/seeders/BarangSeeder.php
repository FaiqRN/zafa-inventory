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
                'barang_id' => 'B0001',
                'barang_kode' => 'PRD-001',
                'nama_barang' => 'Balado Teri Kacang',
                'harga_awal_barang' => 27000.00,
                'satuan' => 'pcs',
                'keterangan' => 'Kemasan 180 gram, balado teri kacang pedas manis',
            ],
            [
                'barang_id' => 'B0002',
                'barang_kode' => 'PRD-002',
                'nama_barang' => 'Kentang Kriuuuk',
                'harga_awal_barang' => 26000.00,
                'satuan' => 'pcs',
                'keterangan' => 'Kemasan 270 gram, kentang kering renyah',
            ],
            [
                'barang_id' => 'B0003',
                'barang_kode' => 'PRD-003',
                'nama_barang' => 'Kering Kentang',
                'harga_awal_barang' => 26000.00,
                'satuan' => 'pcs',
                'keterangan' => 'Kemasan 270 gram, kering kentang original',
            ],
            [
                'barang_id' => 'B0004',
                'barang_kode' => 'PRD-004',
                'nama_barang' => 'Kering Kentang Mustofa',
                'harga_awal_barang' => 27000.00,
                'satuan' => 'pcs',
                'keterangan' => 'Kemasan 180 gram, kering kentang ala mustofa pedas',
            ],
            [
                'barang_id' => 'B0005',
                'barang_kode' => 'PRD-005',
                'nama_barang' => 'Kering Kentang Tempe',
                'harga_awal_barang' => 26000.00,
                'satuan' => 'pcs',
                'keterangan' => 'Kemasan 270 gram, kering kentang dengan tempe',
            ],
        ]);
    }
}