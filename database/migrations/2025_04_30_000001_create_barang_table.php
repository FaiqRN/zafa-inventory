<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBarangTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('barang', function (Blueprint $table) {
            $table->string('barang_id', 10)->primary();
            $table->string('barang_kode', 20);
            $table->string('nama_barang', 100);
            $table->decimal('harga_awal_barang', 10, 2);
            $table->string('satuan', 20);
            $table->integer('shelf_life')->unsigned()->comment('Durasi barang bisa disimpan dalam satuan hari sejak tanggal produksi');
            $table->string('keterangan', 255)->nullable();
            
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->string('user_create')->nullable();
            $table->string('user_update')->nullable();
            
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
        Schema::dropIfExists('barang');
    }
}