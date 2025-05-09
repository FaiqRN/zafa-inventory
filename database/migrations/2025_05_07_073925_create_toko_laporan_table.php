<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTokoLaporanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('toko_laporan', function (Blueprint $table) {
            $table->string('laporan_id', 15)->primary();
            $table->string('toko_id', 10);
            // References untuk detail pengiriman (dapat menyimpan referensi pengiriman terakhir)
            $table->string('pengiriman_id', 10)->nullable();
            // References untuk detail retur (dapat menyimpan referensi retur terakhir)
            $table->integer('retur_id')->unsigned()->nullable();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->enum('periode', ['bulanan', 'semester', 'tahunan']);
            $table->decimal('total_penjualan', 12, 2)->comment('Total hasil penjualan dari tabel retur');
            $table->integer('total_barang_dikirim')->comment('Total barang yang dikirim dari tabel pengiriman');
            $table->integer('total_barang_retur')->comment('Total barang yang diretur dari tabel retur');
            $table->text('catatan')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            
            // Relasi dengan tabel toko
            $table->foreign('toko_id')
                  ->references('toko_id')
                  ->on('toko')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
            
            // Relasi dengan tabel pengiriman
            $table->foreign('pengiriman_id')
                  ->references('pengiriman_id')
                  ->on('pengiriman')
                  ->onDelete('set null')
                  ->onUpdate('cascade');
            
            // Relasi dengan tabel retur
            $table->foreign('retur_id')
                  ->references('retur_id')
                  ->on('retur')
                  ->onDelete('set null')
                  ->onUpdate('cascade');
                  
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
            
            // Indexing untuk mempercepat pencarian berdasarkan toko dan periode
            $table->index(['toko_id', 'periode']);
            $table->index(['tanggal_mulai', 'tanggal_selesai']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('toko_laporan');
    }
}