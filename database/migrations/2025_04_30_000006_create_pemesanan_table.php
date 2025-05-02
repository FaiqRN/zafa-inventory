<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePemesananTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pemesanan', function (Blueprint $table) {
            $table->string('pemesanan_id', 10)->primary();
            $table->string('barang_id', 10);
            $table->string('nama_pemesan', 100);
            $table->date('tanggal_pemesanan');
            $table->text('alamat_pemesan');
            $table->integer('jumlah_pesanan');
            $table->decimal('total', 12, 2);
            $table->string('pemesanan_dari', 50); // shopee, whatsapp, instagram, dll
            $table->string('metode_pembayaran', 50); // tunai, transfer, qris, dll
            $table->enum('status_pemesanan', ['pending','diproses','dikirim','selesai','dibatalkan'])->default('pending'); // pending, diproses, dikirim, selesai, dibatalkan
            $table->string('no_telp_pemesan', 20);
            $table->string('email_pemesan', 100);
            $table->text('catatan_pemesanan')->nullable();
            $table->timestamps(); // created_at dan updated_at
            
            $table->foreign('barang_id')
                  ->references('barang_id')
                  ->on('barang')
                  ->onDelete('restrict')
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
        Schema::dropIfExists('pemesanan');
    }
}