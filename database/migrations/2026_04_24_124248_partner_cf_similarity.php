<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 *  LAYER 4 — partner_cf_similarity
 *
 * Menyimpan matriks similarity antar mitra (user similarity) dan antar produk
 * (item similarity) untuk keperluan audit, debugging, dan evaluasi model.
 *
 * KAPAN TABEL INI DIPERLUKAN:
 *   - Saat ingin melihat "mengapa mitra A mendapat skor tinggi/rendah"
 *   - Saat ingin mengecek apakah cache similarity sudah kadaluarsa
 *   - Saat evaluasi model (membandingkan similarity antar skenario α/β)
 *
 * KAPAN BISA DIABAIKAN:
 *   - Jika Redis/Cache Laravel sudah cukup untuk menyimpan similarity matrix
 *   - Jika tidak ada kebutuhan audit trail similarity
 *
 * Catatan: Tabel ini menyimpan PASANGAN (toko_a, toko_b), bukan full matrix N×N.
 * Untuk 25 mitra: 25×24/2 = 300 pasangan unik per periode — tetap ringan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_cf_similarity', function (Blueprint $table) {
            $table->id();
            $table->date('period_start');
            $table->date('period_end');

            // ── User Similarity (antar mitra) ─────────────────────────────────
            $table->string('toko_id_a', 10);
            $table->string('toko_id_b', 10);

            $table->decimal('sim_total', 12, 8)->default(0)
                ->comment('Similarity gabungan: w_loc × sim_location + w_dist × sim_district + w_pat × sim_pattern');
            $table->decimal('sim_location', 12, 8)->default(0)
                ->comment('Similarity berdasarkan jarak geografis: 1 / (1 + haversine_km)');
            $table->decimal('sim_district', 12, 8)->default(0)
                ->comment('Similarity berdasarkan kecamatan: 1.0 jika sama, 0.0 jika berbeda');
            $table->decimal('sim_pattern', 12, 8)->default(0)
                ->comment('Similarity berdasarkan pola transaksi: cosine similarity time-series vector');

            $table->json('weights_used')->nullable()
                ->comment('Bobot yang digunakan: {location, district, pattern}');

            $table->timestamps();

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            // Pasangan mitra unik per periode (a < b untuk efisiensi storage)
            $table->unique(
                ['period_start', 'period_end', 'toko_id_a', 'toko_id_b'],
                'pcs_pair_unique'
            );
            $table->index(['period_start', 'period_end'], 'pcs_period_idx');

            $table->foreign('toko_id_a')
                ->references('toko_id')
                ->on('toko')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('toko_id_b')
                ->references('toko_id')
                ->on('toko')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_cf_similarity');
    }
};