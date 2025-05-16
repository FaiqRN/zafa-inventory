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
            $table->id();
            $table->string('tipe', 20); // 'barang', 'sumber', or 'pemesan'
            $table->string('reference_id', 100); // Could be barang_id, pemesanan_dari, or nama_pemesan
            $table->text('catatan')->nullable();
            $table->string('periode', 20); // '1_bulan', '6_bulan', '1_tahun'
            $table->integer('bulan');
            $table->integer('tahun');
            $table->timestamps();
            
            // Add index for faster lookups
            $table->index(['tipe', 'reference_id', 'periode', 'bulan', 'tahun']);
            
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