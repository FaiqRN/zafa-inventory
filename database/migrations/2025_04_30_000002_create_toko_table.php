<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTokoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('toko', function (Blueprint $table) {
            $table->string('toko_id', 10)->primary();
            $table->string('nama_toko', 100);
            $table->string('pemilik', 100);
            $table->text('alamat');
            $table->string('wilayah_kecamatan', 100);
            $table->string('wilayah_kelurahan', 100);
            $table->string('wilayah_kota_kabupaten', 100);
            $table->string('nomer_telpon', 20);
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
        Schema::dropIfExists('toko');
    }
}