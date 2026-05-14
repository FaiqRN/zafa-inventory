<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * FIX #3: Tambah kolom is_active ke tabel ss_zscore_setting.
 *
 * Sebelumnya SsService::resolveZ() menggunakan orderByDesc(service_level)->first()
 * sehingga SELALU mengambil service level tertinggi (99%) tanpa bisa dipilih user.
 *
 * Dengan kolom is_active, user bisa memilih service level mana yang aktif
 * per pasangan toko-barang via UI Setting Z-Score.
 *
 * Migrasi ini juga menetapkan default: untuk setiap pasangan toko-barang yang
 * sudah ada, service_level=95 (Standar) dijadikan aktif secara default.
 * Jika tidak ada service_level=95, diambil yang tertinggi sebagai fallback.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ss_zscore_setting', function (Blueprint $table) {
            $table->boolean('is_active')
                ->default(false)
                ->after('z_score')
                ->comment('Menandai service level yang dipakai untuk kalkulasi SS pada pasangan toko-barang ini');
        });

        // Tetapkan default is_active untuk data yang sudah ada.
        // Prioritas: service_level = 95 (Standar). Fallback: service_level tertinggi.
        $pairs = DB::table('ss_zscore_setting')
            ->select('toko_id', 'barang_id')
            ->distinct()
            ->get();

        foreach ($pairs as $pair) {
            // Cari baris dengan service_level = 95 untuk pasangan ini
            $defaultRow = DB::table('ss_zscore_setting')
                ->where('toko_id', $pair->toko_id)
                ->where('barang_id', $pair->barang_id)
                ->where('service_level', 95.00)
                ->orderBy('id')
                ->first();

            // Jika tidak ada service_level=95, ambil yang tertinggi
            if (!$defaultRow) {
                $defaultRow = DB::table('ss_zscore_setting')
                    ->where('toko_id', $pair->toko_id)
                    ->where('barang_id', $pair->barang_id)
                    ->orderByDesc('service_level')
                    ->first();
            }

            if ($defaultRow) {
                DB::table('ss_zscore_setting')
                    ->where('id', $defaultRow->id)
                    ->update(['is_active' => true]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('ss_zscore_setting', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
