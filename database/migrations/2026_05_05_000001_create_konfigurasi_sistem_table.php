<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel konfigurasi_sistem menyimpan parameter sistem yang dapat diubah
 * tanpa perlu deploy ulang aplikasi.
 *
 * Baris pertama yang wajib ada:
 *   key  = 'min_interval_kirim_hari'
 *   nilai = 14  (fallback default)
 *
 * Desain key-value memungkinkan penambahan parameter lain di masa depan
 * tanpa perlu menambah kolom atau tabel baru.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('konfigurasi_sistem', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique()->comment('Kunci unik konfigurasi');
            $table->text('nilai')->comment('Nilai konfigurasi (string, akan di-cast sesuai kebutuhan)');
            $table->string('tipe', 20)->default('integer')
                ->comment('Tipe data nilai: integer, string, boolean, float');
            $table->string('label', 255)->nullable()
                ->comment('Label tampilan untuk UI');
            $table->text('keterangan')->nullable()
                ->comment('Deskripsi konfigurasi untuk admin');
            $table->unsignedBigInteger('user_update')->nullable();
            $table->timestamps();

            $table->foreign('user_update')->references('user_id')->on('user')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('konfigurasi_sistem');
    }
};
