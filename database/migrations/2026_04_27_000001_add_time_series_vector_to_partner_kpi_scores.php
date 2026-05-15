<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('partner_kpi_scores', 'time_series_vector')) {
            Schema::table('partner_kpi_scores', function (Blueprint $table) {
                $table->json('time_series_vector')
                    ->nullable()
                    ->after('kpi_vector')
                    ->comment('Array sold_qty bulanan [m1, m2, ...] untuk User-Based CF cosine similarity');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('partner_kpi_scores', 'time_series_vector')) {
            Schema::table('partner_kpi_scores', function (Blueprint $table) {
                $table->dropColumn('time_series_vector');
            });
        }
    }
};