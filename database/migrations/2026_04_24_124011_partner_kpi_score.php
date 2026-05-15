<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * LAYER 1 — partner_kpi_scores
 *
 * Menyimpan hasil perhitungan KPI raw, KPI normalized, dan CBF score.
 *
 * Alasan KPI dan CBF digabung dalam satu tabel:
 *   CBF score = weighted sum dari KPI normalized (lihat ContentBasedFilteringService).
 *   Memisahkan keduanya hanya menambah JOIN tanpa manfaat — datanya satu sumber,
 *   satu waktu hitung, dan satu foreign key yang sama (toko_id + periode).
 *
 * Kolom KPI raw  : nilai mentah sebelum normalisasi, disimpan untuk keperluan
 *                  audit, debug, dan evaluasi model (Precision/Recall/NDCG).
 * Kolom KPI norm : nilai setelah min-max normalisasi ke rentang [0, 1].
 * Kolom CBF      : skor akhir Content-Based Filtering = hasil dot product
 *                  antara bobot (weights) dan KPI normalized.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_kpi_scores', function (Blueprint $table) {
            $table->id();
            $table->string('toko_id', 10);
            $table->date('period_start');
            $table->date('period_end');

            // ── KPI Raw ──────────────────────────────────────────────────────
            // Nilai asli sebelum normalisasi. Tipe disesuaikan dengan satuan:
            //   sales       → total unit terjual (bisa sangat besar)
            //   return_rate → rasio [0, 1] (jumlah_retur / jumlah_kirim)
            //   freq        → frekuensi transaksi per periode (count)
            //   consistency → nilai [0, 1] dari formula 1 - (std/mean)
            //   efficiency  → rasio [0, 1] (jumlah_terjual / jumlah_kirim)
            $table->unsignedBigInteger('kpi_raw_sales')->default(0)
                ->comment('Total unit terjual dalam periode (dari tabel retur.total_terjual)');
            $table->decimal('kpi_raw_return_rate', 10, 6)->default(0)
                ->comment('Rasio retur [0-1]: jumlah_retur / jumlah_kirim');
            $table->unsignedSmallInteger('kpi_raw_freq')->default(0)
                ->comment('Jumlah transaksi pengiriman dalam periode');
            $table->decimal('kpi_raw_consistency', 10, 6)->default(0)
                ->comment('Konsistensi transaksi [0-1]: 1 - (std_dev / mean)');
            $table->decimal('kpi_raw_efficiency', 10, 6)->default(0)
                ->comment('Efisiensi penjualan [0-1]: jumlah_terjual / jumlah_kirim');

            // ── KPI Normalized ───────────────────────────────────────────────
            // Semua nilai sudah dalam rentang [0, 1] setelah min-max normalisasi
            // di antara seluruh mitra dalam periode yang sama.
            // Disimpan juga sebagai JSON kpi_vector untuk audit dan transparansi
            // input model CBF/KPI similarity.
            $table->decimal('kpi_norm_sales', 12, 8)->default(0)
                ->comment('KPI sales setelah normalisasi [0-1]');
            $table->decimal('kpi_norm_return_rate', 12, 8)->default(0)
                ->comment('KPI return_rate setelah normalisasi [0-1] (nilai tinggi = retur rendah)');
            $table->decimal('kpi_norm_freq', 12, 8)->default(0)
                ->comment('KPI frekuensi setelah normalisasi [0-1]');
            $table->decimal('kpi_norm_consistency', 12, 8)->default(0)
                ->comment('KPI konsistensi setelah normalisasi [0-1]');
            $table->decimal('kpi_norm_efficiency', 12, 8)->default(0)
                ->comment('KPI efisiensi setelah normalisasi [0-1]');

            // ── CBF Score ────────────────────────────────────────────────────
            // Hasil akhir Content-Based Filtering.
            // Formula: cbf_score = Σ (weight_i × kpi_norm_i)
            // Bobot masing-masing KPI disimpan di cbf_weights (JSON)
            // agar bisa di-audit dan diubah tanpa mengubah skema tabel.
            $table->decimal('cbf_score', 12, 8)->default(0)
                ->comment('Skor CBF final [0-1]: weighted sum dari KPI normalized');
            $table->json('cbf_weights')
                ->comment('Bobot tiap KPI yang digunakan saat hitung: {sales, return_rate, freq, consistency, efficiency}');

            // ── Vektor KPI & Time-Series ─────────────────────────────────────
            // kpi_vector: fitur KPI normalized [sales, return_rate, freq, consistency, efficiency]
            // time_series_vector: pola penjualan bulanan untuk User-Based CF cosine similarity.
            $table->json('kpi_vector')
                ->comment('Array [sales_norm, return_rate_norm, freq_norm, consistency_norm, efficiency_norm]');
            $table->json('time_series_vector')
                ->comment('Array sold_qty bulanan [m1, m2, ...] untuk User-Based CF cosine similarity');

            // ── Metadata ─────────────────────────────────────────────────────
            $table->json('calculation_meta')->nullable()
                ->comment('Info debug: jumlah transaksi, jumlah retur, dsb.');

            $table->timestamps();

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            // Satu mitra hanya punya satu record per periode
            $table->unique(
                ['toko_id', 'period_start', 'period_end'],
                'pkpi_period_unique'
            );
            $table->index(['period_start', 'period_end'], 'pkpi_period_idx');

            $table->foreign('toko_id')
                ->references('toko_id')
                ->on('toko')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_kpi_scores');
    }
};