<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventory_turnover_tracking', function (Blueprint $table) {
            $table->id();
            $table->date('period_date')->comment('Tanggal periode (biasanya akhir bulan)');
            $table->string('toko_id')->nullable()->comment('Null untuk aggregate semua toko');
            $table->string('barang_id')->nullable()->comment('Null untuk aggregate semua barang');
            $table->integer('total_shipped')->default(0);
            $table->integer('total_sold')->default(0);
            $table->integer('total_returned')->default(0);
            $table->decimal('turnover_rate', 8, 2)->default(0);
            $table->decimal('efficiency_percentage', 8, 2)->default(0);
            $table->integer('avg_days_to_sell')->default(0);
            $table->decimal('cash_cycle_days', 8, 2)->default(0);
            $table->timestamps();
            
            $table->index(['period_date', 'toko_id', 'barang_id']);
            $table->index('period_date');
            $table->index(['toko_id', 'period_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_turnover_tracking');
    }
};