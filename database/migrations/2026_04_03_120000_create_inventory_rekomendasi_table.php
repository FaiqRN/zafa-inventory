<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_rekomendasi', function (Blueprint $table) {
            $table->string('barang_toko_id', 10)->collation('utf8mb4_general_ci')->primary();

            $table->string('toko_id', 10)->collation('utf8mb4_general_ci');
            $table->string('barang_id', 10)->collation('utf8mb4_general_ci');

            // Snapshot parameter kalkulasi
            $table->decimal('s_dipakai', 14, 2)->default(0);
            $table->boolean('s_dari_override')->default(false);
            $table->decimal('h_dipakai', 14, 2)->default(0);
            $table->decimal('z_dipakai', 8, 4)->default(0);
            $table->decimal('service_level_dipakai', 5, 2)->default(0);
            $table->unsignedSmallInteger('hari_observasi')->default(180);

            // Hasil EOQ
            $table->unsignedInteger('eoq_result')->default(0);
            $table->unsignedInteger('frekuensi_per_tahun')->default(0);
            $table->decimal('interval_eoq_hari', 8, 2)->default(0);

            // Hasil SS dan ROP
            $table->unsignedInteger('ss_result')->default(0);
            $table->unsignedInteger('rop_result')->default(0);

            // Hasil layer rekomendasi kirim
            $table->unsignedInteger('shelf_life_days')->default(0);
            $table->unsignedInteger('batas_aman_hari')->default(0);
            $table->boolean('shelf_life_flag')->default(false);
            $table->decimal('interval_kirim_hari', 8, 2)->default(0);
            $table->decimal('avg_jual_harian', 12, 4)->default(0);
            $table->unsignedInteger('q_kirim_result')->default(0);

            // Status stok saat kalkulasi
            $table->unsignedInteger('stok_aktual')->default(0);
            $table->boolean('is_below_rop')->default(false);

            // Data historis
            $table->unsignedInteger('total_kirim_historis')->default(0);
            $table->unsignedInteger('total_retur_historis')->default(0);
            $table->unsignedInteger('penjualan_aktual')->default(0);

            // Timestamps
            $table->timestamp('calculated_at')->useCurrent();
            $table->timestamps();
            $table->string('user_create')->nullable();
            $table->string('user_update')->nullable();

            // Foreign keys
            $table->foreign('barang_toko_id')->references('barang_toko_id')->on('barang_toko')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('toko_id')->references('toko_id')->on('toko')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('barang_id')->references('barang_id')->on('barang')->cascadeOnUpdate()->cascadeOnDelete();

            // Indexes
            $table->index(['toko_id', 'barang_id'], 'idx_rekomendasi_toko_barang');
            $table->index('calculated_at', 'idx_rekomendasi_calculated_at');
            $table->index(['barang_id', 'calculated_at'], 'idx_rekomendasi_barang_calculated');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_rekomendasi');
    }
};