<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel override komponen biaya pemesanan (S) per toko.
     *
     * Logika resolusi S untuk toko tertentu:
     *   1. Ambil semua nama_biaya dari eoq_biaya_pesan_global (is_aktif = 1)
     *   2. Untuk setiap nama_biaya, cek apakah ada baris di tabel ini
     *      dengan toko_id yang sama dan nama_biaya yang sama
     *   3. Jika ADA  → pakai nominal dari tabel ini (override)
     *   4. Jika TIDAK ADA → pakai nominal dari eoq_biaya_pesan_global (global)
     *   5. Total S toko = SUM nominal setelah resolusi
     *
     * Contoh:
     *   Global       : transportasi=15000, packing=5000, pengiriman=3000
     *   Override T1  : transportasi=25000  (jarak lebih jauh)
     *   S untuk T1   : 25000 + 5000 + 3000 = 33000
     *
     * User juga bisa menambah komponen biaya KHUSUS toko tertentu
     * yang tidak ada di global, cukup tambah baris baru dengan
     * nama_biaya yang unik untuk toko tersebut.
     */
    public function up(): void
    {
        Schema::create('eoq_biaya_pesan_toko', function (Blueprint $table) {
            $table->id();
            $table->string('toko_id', 10)->charset('utf8mb4')->collation('utf8mb4_general_ci');
            $table->string('nama_biaya', 100)->comment('Nama komponen biaya — harus sama persis dengan nama di global untuk override');
            $table->decimal('nominal', 12, 2)->default(0)->comment('Nominal override untuk toko ini (Rp)');
            $table->text('keterangan')->nullable();
            $table->unique(['toko_id', 'nama_biaya'], 'uq_biaya_pesan_toko_nama');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->string('user_create')->nullable();
            $table->string('user_update')->nullable();
            
            $table->foreign('toko_id')->references('toko_id')->on('toko')->onUpdate('cascade')->onDelete('cascade');
            
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eoq_biaya_pesan_toko');
    }
};
