<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * LAYER 2 — partner_cf_scores
 *
 * Menyimpan hasil perhitungan Collaborative Filtering (CF) per mitra per periode.
 * Terdiri dari dua sub-pendekatan yang hasilnya digabung menjadi cf_score:
 *
 *   1. User-Based CF  → kemiripan antar mitra berdasarkan profil + pola transaksi
 *                        (lokasi, wilayah kecamatan, cosine similarity time-series vector)
 *   2. Item-Based CF  → kemiripan antar produk berdasarkan pola penjualan per mitra
 *                        (matriks mitra × produk, cosine similarity antar item)
 *
 * Formula penggabungan:
 *   cf_score = β × cf_user_score + (1 - β) × cf_item_score
 *   (β disimpan di cf_beta agar bisa di-audit)
 *
 * Tabel similarity TIDAK disimpan di sini karena bersifat N×N (matrix).
 * Similarity disimpan terpisah di partner_cf_similarity untuk keperluan
 * debug dan evaluasi, serta sudah di-cache di Redis/Cache Laravel
 * (lihat SimilarityCollaborativeFilteringService & ItemCollaborativeFilteringService).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_cf_scores', function (Blueprint $table) {
            $table->id();
            $table->string('toko_id', 10);
            $table->date('period_start');
            $table->date('period_end');

            // ── User-Based CF ─────────────────────────────────────────────────
            // Dihitung oleh UserCollaborativeFilteringService.
            // Menggunakan kemiripan profil mitra (lokasi, kecamatan, pola transaksi).
            $table->decimal('cf_user_score', 12, 8)->default(0)
                ->comment('Skor User-Based CF [0-1]: weighted avg skor KPI dari tetangga terdekat');
            $table->decimal('cf_user_avg_similarity', 12, 8)->default(0)
                ->comment('Rata-rata similarity dengan top-N tetangga yang digunakan');
            $table->unsignedTinyInteger('cf_user_neighbor_count')->default(0)
                ->comment('Jumlah tetangga (neighbor) yang digunakan dalam perhitungan');
            $table->json('cf_user_top_neighbors')->nullable()
                ->comment('Detail top-N tetangga: [{toko_id, similarity, score_kpi}]');

            // ── Item-Based CF ─────────────────────────────────────────────────
            // Dihitung oleh ItemCollaborativeFilteringService.
            // Menggunakan matriks mitra × produk (pola penjualan dari tabel retur).
            $table->decimal('cf_item_score', 12, 8)->default(0)
                ->comment('Skor Item-Based CF [0-1]: setelah normalisasi dari raw_score');
            $table->decimal('cf_item_raw_score', 12, 8)->default(0)
                ->comment('Raw score sebelum normalisasi: avg_sales_norm × diversity × balance × relation');
            $table->decimal('cf_item_relation_score', 12, 8)->default(0)
                ->comment('Rata-rata predicted item score untuk produk yang aktif terjual');
            $table->decimal('cf_item_diversity_factor', 12, 8)->default(0)
                ->comment('Keragaman produk [0-1]: active_products / total_products');
            $table->decimal('cf_item_balance_factor', 12, 8)->default(0)
                ->comment('Keseimbangan penjualan [0-1]: 1 / (1 + CV); CV = std_dev / mean');
            $table->decimal('cf_item_avg_sales_norm', 12, 8)->default(0)
                ->comment('Rata-rata penjualan per produk aktif setelah normalisasi [0-1]');
            $table->unsignedSmallInteger('cf_item_active_products')->default(0)
                ->comment('Jumlah produk yang memiliki penjualan > 0 dalam periode');
            $table->unsignedSmallInteger('cf_item_total_products')->default(0)
                ->comment('Total produk yang ada dalam matriks (dikirim atau terjual)');

            // ── CF Combined Score ─────────────────────────────────────────────
            // Formula: cf_score = β × cf_user_score + (1 - β) × cf_item_score
            $table->decimal('cf_score', 12, 8)->default(0)
                ->comment('Skor CF final [0-1]: gabungan user-based dan item-based');
            $table->decimal('cf_beta', 8, 6)->default(0.500000)
                ->comment('Parameter β yang digunakan: bobot user-based vs item-based');

            // ── Similarity Weights ────────────────────────────────────────────
            // Bobot yang digunakan dalam SimilarityCollaborativeFilteringService
            // untuk menghitung user similarity (location, district, pattern).
            $table->json('similarity_weights')->nullable()
                ->comment('Bobot similarity: {location, district, pattern} yang digunakan');

            // ── Metadata ──────────────────────────────────────────────────────
            $table->string('similarity_cache_key', 100)->nullable()
                ->comment('Cache key similarity matrix yang digunakan (untuk invalidasi cache)');
            $table->json('calculation_meta')->nullable()
                ->comment('Info debug: item_count, top_k_items, top_n_neighbors, dsb.');

            $table->timestamps();

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            // Satu mitra hanya punya satu record CF per periode
            $table->unique(
                ['toko_id', 'period_start', 'period_end'],
                'pcf_period_unique'
            );
            $table->index(['period_start', 'period_end'], 'pcf_period_idx');

            $table->foreign('toko_id')
                ->references('toko_id')
                ->on('toko')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_cf_scores');
    }
};