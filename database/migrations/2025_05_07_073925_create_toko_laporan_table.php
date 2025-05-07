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
            $table->id();
            $table->string('toko_id', 10);
            $table->string('periode', 20); // '1_bulan', '6_bulan', '1_tahun'
            $table->integer('bulan');
            $table->integer('tahun');
            $table->text('catatan')->nullable();
            $table->timestamps();
            
            $table->foreign('toko_id')
                  ->references('toko_id')
                  ->on('toko')
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
        Schema::dropIfExists('toko_laporan');
    }
}