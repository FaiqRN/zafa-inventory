<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReturTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('retur', function (Blueprint $table) {
            $table->increments('retur_id');
            $table->string('pengiriman_id', 10);
            $table->string('toko_id', 10);
            $table->string('barang_id', 10);
            $table->string('nomer_pengiriman', 50);
            $table->date('tanggal_pengiriman');
            $table->date('tanggal_retur');
            $table->decimal('harga_awal_barang', 10, 2);
            $table->integer('jumlah_kirim');
            $table->integer('jumlah_retur');
            $table->integer('total_terjual');
            $table->decimal('hasil', 10, 2);
            $table->string('kondisi', 50)->nullable();
            $table->text('keterangan')->nullable();
            
            $table->foreign('pengiriman_id')
                  ->references('pengiriman_id')
                  ->on('pengiriman')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
                  
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
        Schema::dropIfExists('retur');
    }
}