<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tabel ini menyimpan daftar Z-score berdasarkan service level.
     * Digunakan sebagai sumber nilai Z pada rumus Safety Stock:
     *   SS = Z * sqrt(L * σd² + d² * σL²)
     *
     * Setiap toko memiliki setting Z-score sendiri (toko_id), sehingga bisa berbeda antar toko.
     * User dapat menambah / mengubah baris sesuai kebutuhan.
     * Baris default di-seed langsung di migration ini.
     */
    public function up(): void
    {
        Schema::create('ss_zscore_setting', function (Blueprint $table) {
            $table->id();
            $table->string('toko_id', 10)->charset('utf8mb4')->collation('utf8mb4_general_ci');
            $table->string('barang_id', 10)->charset('utf8mb4')->collation('utf8mb4_general_ci');
            $table->string('label', 50)->comment('Nama deskriptif, contoh: Standar, Tinggi, Sangat Tinggi');
            $table->decimal('service_level', 5, 2)->comment('Service level dalam persen, contoh: 95.00 untuk 95%');
            $table->decimal('z_score', 6, 4)->comment('Nilai Z-score sesuai service level, contoh: 1.6449 untuk 95%');
            $table->text('keterangan')->nullable();
            $table->unique(['toko_id', 'barang_id', 'service_level'], 'uq_ss_zscore_toko_barang_service_level');
            $table->index('toko_id', 'idx_ss_zscore_toko_id');
            $table->index('barang_id', 'idx_ss_zscore_barang_id');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->string('user_create')->nullable();
            $table->string('user_update')->nullable();
            $table->charset   = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ss_zscore_setting');
    }
};
