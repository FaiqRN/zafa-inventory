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
        Schema::create('inventory_recommendations', function (Blueprint $table) {
            $table->id();
            $table->string('toko_id');
            $table->string('barang_id');
            $table->integer('historical_avg_shipped')->default(0);
            $table->integer('historical_avg_sold')->default(0);
            $table->integer('recommended_quantity');
            $table->decimal('seasonal_multiplier', 8, 2)->default(1.00);
            $table->decimal('trend_multiplier', 8, 2)->default(1.00);
            $table->enum('confidence_level', ['Very Low', 'Low', 'Medium', 'High'])->default('Medium');
            $table->decimal('potential_savings', 15, 2)->default(0);
            $table->decimal('improvement_percentage', 8, 2)->default(0);
            $table->enum('status', ['pending', 'applied', 'customized', 'rejected'])->default('pending');
            $table->integer('applied_quantity')->nullable()->comment('Actual quantity applied');
            $table->text('notes')->nullable();
            $table->string('applied_by')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();
            
            $table->index(['toko_id', 'barang_id']);
            $table->index(['confidence_level', 'status']);
            $table->index('created_at');
            
            // Foreign keys jika tabel toko dan barang menggunakan foreign key constraints
            // $table->foreign('toko_id')->references('toko_id')->on('toko')->onDelete('cascade');
            // $table->foreign('barang_id')->references('barang_id')->on('barang')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_recommendations');
    }
};