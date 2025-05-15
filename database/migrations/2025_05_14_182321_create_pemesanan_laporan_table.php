<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePemesananLaporanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pemesanan_laporan', function (Blueprint $table) {
            $table->string('pesanlapor_id')->primary();
            $table->string('pemesanan_id', 10); // Ini tetap sama dengan tabel pemesanan agar kompatibel
            $table->string('periode', 50); // 1_bulan, 6_bulan, 1_tahun
            $table->integer('bulan');
            $table->integer('tahun');
            $table->text('catatan')->nullable();
            $table->timestamps();
            
            $table->foreign('pemesanan_id')
                  ->references('pemesanan_id')
                  ->on('pemesanan')
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
        Schema::dropIfExists('pemesanan_laporan');
    }
}