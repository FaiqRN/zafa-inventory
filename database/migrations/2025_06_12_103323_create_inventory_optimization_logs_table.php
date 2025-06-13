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
        Schema::create('inventory_optimization_logs', function (Blueprint $table) {
            $table->id();
            $table->string('toko_id')->nullable();
            $table->string('barang_id')->nullable();
            $table->enum('action_type', [
                'recommendation_generated', 
                'recommendation_applied', 
                'recommendation_customized', 
                'recommendation_rejected',
                'seasonal_updated',
                'bulk_applied'
            ]);
            $table->integer('old_quantity')->nullable();
            $table->integer('new_quantity')->nullable();
            $table->json('metadata')->nullable()->comment('Additional data in JSON format');
            $table->string('performed_by');
            $table->timestamp('performed_at');
            $table->timestamps();
            
            $table->index(['toko_id', 'barang_id']);
            $table->index(['action_type', 'performed_at']);
            $table->index('performed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_optimization_logs');
    }
};