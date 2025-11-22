<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBarangStokTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('barang_stok', function (Blueprint $table) {
            $table->id();
            $table->string('barang_id', 10);
            $table->date('tanggal_stock_barang');
            $table->integer('stok')->default(0)->comment('Jumlah stok yang ditambahkan');
            $table->text('catatan')->nullable()->comment('Catatan untuk stok ini');
            
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->string('user_create')->nullable();
            $table->string('user_update')->nullable();
            
            // Foreign key constraint
            $table->foreign('barang_id')
                  ->references('barang_id')
                  ->on('barang')
                  ->onDelete('cascade');
            
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
        Schema::dropIfExists('barang_stok');
    }
}
