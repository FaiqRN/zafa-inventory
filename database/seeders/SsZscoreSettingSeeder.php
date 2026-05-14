<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * FIX #3: Seeder diperbarui untuk mengisi kolom is_active.
 * Service level "Standar" (95%) dijadikan aktif secara default untuk setiap
 * pasangan toko-barang baru.
 */
class SsZscoreSettingSeeder extends Seeder
{
    public function run(): void
    {
        $tokoBarangPairs = DB::table('barang_toko')
            ->select('toko_id', 'barang_id')
            ->distinct()
            ->get();

        if ($tokoBarangPairs->isEmpty()) {
            return;
        }

        $defaultRows = [
            [
                'label'         => 'Rendah',
                'service_level' => 90.00,
                'z_score'       => 1.2816,
                'keterangan'    => 'Cocok untuk produk dengan permintaan sangat stabil',
                'is_active'     => false,
            ],
            [
                'label'         => 'Standar',
                'service_level' => 95.00,
                'z_score'       => 1.6449,
                'keterangan'    => 'Nilai default yang umum digunakan pada sistem consignment UMKM',
                'is_active'     => true, // ← DEFAULT AKTIF
            ],
            [
                'label'         => 'Tinggi',
                'service_level' => 97.00,
                'z_score'       => 1.8808,
                'keterangan'    => 'Untuk produk prioritas atau mitra strategis',
                'is_active'     => false,
            ],
            [
                'label'         => 'Sangat Tinggi',
                'service_level' => 99.00,
                'z_score'       => 2.3263,
                'keterangan'    => 'Untuk produk dengan risiko stockout sangat tinggi',
                'is_active'     => false,
            ],
        ];

        $rows = [];
        $now = now();

        foreach ($tokoBarangPairs as $pair) {
            foreach ($defaultRows as $row) {
                $rows[] = [
                    'toko_id'       => $pair->toko_id,
                    'barang_id'     => $pair->barang_id,
                    'label'         => $row['label'],
                    'service_level' => $row['service_level'],
                    'z_score'       => $row['z_score'],
                    'keterangan'    => $row['keterangan'],
                    'is_active'     => $row['is_active'],
                    'user_create'   => 'system',
                    'user_update'   => 'system',
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ];
            }
        }

        DB::table('ss_zscore_setting')->upsert(
            $rows,
            ['toko_id', 'barang_id', 'service_level'],
            ['label', 'z_score', 'keterangan', 'is_active', 'user_update', 'updated_at']
        );
    }
}