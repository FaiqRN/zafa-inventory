<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel komponen biaya pemesanan (S) global.
     * Setiap baris = satu komponen biaya.
     * Total S = SUM(nominal) 
     */
    public function up(): void
    {
        Schema::create('eoq_biaya_pesan_global', function (Blueprint $table) {
            $table->id();
            $table->string('nama_biaya', 100)->comment('Nama komponen biaya pemesanan, bebas diisi user');
            $table->decimal('nominal', 12, 2)->default(0)->comment('Nominal komponen biaya ini per order (Rp)');
            $table->text('keterangan')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->string('user_create')->nullable();
            $table->string('user_update')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eoq_biaya_pesan_global');
    }
};
