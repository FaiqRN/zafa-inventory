<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_scores', function (Blueprint $table) {
            $table->id();
            $table->string('toko_id', 10);
            $table->date('period_start');
            $table->date('period_end');

            $table->unsignedBigInteger('kpi_score_id')->nullable()
                ->comment('FK ke partner_kpi_scores.id (Layer 1: KPI + CBF)');
            $table->unsignedBigInteger('cf_score_id')->nullable()
                ->comment('FK ke partner_cf_scores.id (Layer 2: CF)');

            $table->decimal('cbf_score', 12, 8)->default(0)
                ->comment('Salin dari partner_kpi_scores.cbf_score');
            $table->decimal('cf_user_score', 12, 8)->default(0)
                ->comment('Salin dari partner_cf_scores.cf_user_score');
            $table->decimal('cf_item_score', 12, 8)->default(0)
                ->comment('Salin dari partner_cf_scores.cf_item_score');
            $table->decimal('cf_score', 12, 8)->default(0)
                ->comment('Salin dari partner_cf_scores.cf_score');

            $table->decimal('hybrid_score', 12, 8)->default(0)
                ->comment('Skor hybrid final [0-1]: α × CBF + (1-α) × CF');

            $table->decimal('hybrid_alpha', 8, 6)->default(0.500000)
                ->comment('Parameter α [0-1]: bobot CBF dalam hybrid score');
            $table->decimal('hybrid_beta', 8, 6)->default(0.500000)
                ->comment('Parameter β [0-1]: bobot user-based dalam CF score');

            $table->decimal('contribution_cbf', 12, 8)->default(0)
                ->comment('Kontribusi CBF ke hybrid score: α × cbf_score');
            $table->decimal('contribution_cf', 12, 8)->default(0)
                ->comment('Kontribusi CF total ke hybrid score: (1-α) × cf_score');
            $table->decimal('contribution_cf_user', 12, 8)->default(0)
                ->comment('Kontribusi user-based CF: (1-α) × β × cf_user_score');
            $table->decimal('contribution_cf_item', 12, 8)->default(0)
                ->comment('Kontribusi item-based CF: (1-α) × (1-β) × cf_item_score');

            $table->char('category', 1)->default('D')
                ->comment('Kategori kinerja: A (terbaik) / B / C / D (perlu evaluasi)');
            $table->unsignedSmallInteger('rank')->default(0)
                ->comment('Peringkat mitra dalam periode (1 = terbaik)');

            $table->timestamps();

            $table->unique(
                ['toko_id', 'period_start', 'period_end'],
                'ps_period_unique'
            );
            $table->index(['period_start', 'period_end'], 'ps_period_idx');
            $table->index(['period_start', 'period_end', 'category'], 'ps_period_category_idx');
            $table->index(['period_start', 'period_end', 'rank'], 'ps_period_rank_idx');

            //  Hanya FK ke toko — tabel partner_kpi_scores & partner_cf_scores
            //  belum ada saat migration ini dijalankan, FK-nya ada di file terpisah
            $table->foreign('toko_id')
                ->references('toko_id')
                ->on('toko')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_scores');
    }
};