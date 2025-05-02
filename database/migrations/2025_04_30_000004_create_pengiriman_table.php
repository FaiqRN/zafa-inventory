<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePengirimanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pengiriman', function (Blueprint $table) {
            $table->string('pengiriman_id', 10)->primary();
            $table->string('toko_id', 10);
            $table->string('barang_id', 10);
            $table->string('nomer_pengiriman', 50);
            $table->date('tanggal_pengiriman');
            $table->integer('jumlah_kirim');
            $table->enum('status', ['terkirim', 'proses','batal'])->default('proses');
            $table->foreign('toko_id')
                  ->references('toko_id')
                  ->on('toko')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
                  
            $table->foreign('barang_id') 
                  ->references('barang_id')
                  ->on('barang')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
                  
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pengiriman');
    }
}