<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FIX #4: Tambah kolom min_interval_kirim_hari ke tabel toko.
 *
 * Kolom ini menyimpan frekuensi pengiriman minimum operasional per toko (hari).
 * Digunakan oleh RekomendasiService::resolveMinIntervalKirim() untuk memastikan
 * interval_kirim_hari tidak lebih pendek dari jadwal operasional yang sudah berjalan.
 *
 * Contoh: jika toko biasanya menerima kiriman setiap 14 hari,
 * set min_interval_kirim_hari = 14 sehingga rekomendasi tidak menyarankan
 * pengiriman lebih sering dari kapasitas operasional.
 *
 * Nilai 0 = tidak ada batasan minimum (default, perilaku seperti sebelumnya).
 *
 * CATATAN PENTING: min_interval tidak pernah melebihi batas_aman shelf life.
 * Keamanan produk selalu menjadi batasan atas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('toko', function (Blueprint $table) {
            $table->unsignedSmallInteger('min_interval_kirim_hari')
                ->default(0)
                ->after('is_active')
                ->comment('Interval minimum pengiriman operasional ke toko ini (hari). 0 = tidak ada batasan.');
        });
    }

    public function down(): void
    {
        Schema::table('toko', function (Blueprint $table) {
            $table->dropColumn('min_interval_kirim_hari');
        });
    }
};
