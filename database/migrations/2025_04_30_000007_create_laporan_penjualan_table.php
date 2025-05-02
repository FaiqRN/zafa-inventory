<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLaporanPenjualanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        
        // Buat tabel baru dengan struktur yang diperbarui
        Schema::create('laporan_penjualan', function (Blueprint $table) {
            $table->string('laporan_id', 10)->primary();
            $table->date('periode_awal');
            $table->date('periode_akhir');
            
            // Informasi penjualan toko
            $table->decimal('total_penjualan_toko', 12, 2)->default(0);
            $table->integer('total_barang_terjual_toko')->default(0);
            $table->integer('total_barang_retur_toko')->default(0);
            
            // Informasi penjualan pemesanan (non-toko)
            $table->decimal('total_penjualan_pemesanan', 12, 2)->default(0);
            $table->integer('total_barang_terjual_pemesanan')->default(0);
            
            // Total keseluruhan
            $table->decimal('total_penjualan_keseluruhan', 12, 2)->default(0);
            $table->integer('total_barang_terjual_keseluruhan')->default(0);
            $table->integer('total_barang_retur_keseluruhan')->default(0);
            
            // Informasi tambahan yang berguna
            $table->integer('jumlah_toko_aktif')->nullable();
            $table->integer('jumlah_pemesanan')->nullable();
            $table->string('barang_terlaris', 10)->nullable(); // barang_id dari barang terlaris
            $table->string('toko_terlaris', 10)->nullable(); // toko_id dari toko terlaris
            
            $table->text('catatan_laporan')->nullable();
            $table->string('dibuat_oleh', 50)->nullable();
            $table->timestamps();
            
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
            
            // Optional: tambahkan foreign key jika diperlukan
            // $table->foreign('barang_terlaris')->references('barang_id')->on('barang')->onDelete('set null')->onUpdate('cascade');
            // $table->foreign('toko_terlaris')->references('toko_id')->on('toko')->onDelete('set null')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Hapus tabel baru
        Schema::dropIfExists('laporan_penjualan');
        
        // Kembalikan tabel lama
        Schema::create('laporan_penjualan_per_bulan', function (Blueprint $table) {
            $table->string('laporan_id', 10)->primary();
            $table->string('toko_id', 10);
            $table->date('periode_awal');
            $table->date('periode_akhir');
            $table->decimal('total_penjualan', 12, 2)->nullable();
            $table->integer('total_barang_terjual')->nullable();
            $table->integer('total_barang_retur')->nullable();
            
            $table->foreign('toko_id')
                  ->references('toko_id')
                  ->on('toko')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
                  
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
        });
    }
}