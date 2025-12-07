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
        Schema::table('barang_stok', function (Blueprint $table) {
            // Add FIFO tracking fields
            $table->integer('sisa_stok')->default(0)->after('stok')->comment('Sisa stok dari batch ini (untuk FIFO)');
            $table->integer('stok_awal')->default(0)->after('sisa_stok')->comment('Backup jumlah awal untuk history');
            
            // Add index for FIFO query performance
            $table->index(['barang_id', 'tanggal_stock_barang'], 'idx_barang_tanggal_fifo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('barang_stok', function (Blueprint $table) {
            // Drop index first
            $table->dropIndex('idx_barang_tanggal_fifo');
            
            // Drop FIFO fields
            $table->dropColumn(['sisa_stok', 'stok_awal']);
        });
    }
};
