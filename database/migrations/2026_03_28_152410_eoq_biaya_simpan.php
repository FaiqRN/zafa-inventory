<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel komponen biaya penyimpanan (H) per produk.
     * Setiap baris = satu komponen biaya simpan untuk satu produk.
     *
     * H menggunakan pendekatan: H = harga_pokok × persentase_holding / 100
     * Persentase ini mencakup semua komponen penyimpanan yang user definisikan.
     *
     * Contoh isi untuk satu produk:
     * | id | barang_id | nama_komponen              | persentase |
     * |  1 | BRG001    | Biaya modal tertahan        |   15.00    |
     * |  2 | BRG001    | Risiko kerusakan/expired    |    7.00    |
     * |  3 | BRG001    | Biaya penyimpanan fisik     |    3.00    |
     * Total persentase = 25% → H = harga_pokok × 25 / 100
     *
     * harga_pokok disimpan di tabel barang atau di sini per record.
     * Disimpan di sini agar snapshot tidak berubah jika harga pokok diupdate.
     *
     * Total H per produk:
     *   persentase_total = SUM(persentase) WHERE barang_id = X AND is_aktif = 1
     *   H = harga_pokok × persentase_total / 100
     */
    public function up(): void
    {
        Schema::create('eoq_biaya_simpan', function (Blueprint $table) {
            $table->id();
            $table->string('barang_id', 10)->charset('utf8mb4')->collation('utf8mb4_general_ci');
            $table->decimal('harga_pokok', 12, 2)->comment('Harga pokok produksi per unit (Rp) — diisi per baris agar konsisten');
            $table->string('nama_komponen', 100)->comment('Nama komponen biaya simpan, bebas diisi user');
            $table->decimal('persentase', 5, 2)->default(0)->comment('Persentase komponen ini dari harga pokok per tahun (%)');
            $table->text('keterangan')->nullable();
            $table->unique(['barang_id', 'nama_komponen'], 'uq_biaya_simpan_barang_komponen');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->string('user_create')->nullable();
            $table->string('user_update')->nullable();
            
            $table->foreign('barang_id')->references('barang_id')->on('barang')->onUpdate('cascade')->onDelete('cascade');
            
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eoq_biaya_simpan');
    }
};
