<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partner_scores', function (Blueprint $table) {
            $table->foreign('kpi_score_id')
                ->references('id')
                ->on('partner_kpi_scores')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->foreign('cf_score_id')
                ->references('id')
                ->on('partner_cf_scores')
                ->onDelete('set null')
                ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('partner_scores', function (Blueprint $table) {
            $table->dropForeign(['kpi_score_id']);
            $table->dropForeign(['cf_score_id']);
        });
    }
};