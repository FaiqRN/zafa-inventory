<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('kelurahan_coordinates', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100)->comment('Nama kelurahan');
            $table->string('nama_normalized', 100)->comment('Nama kelurahan normalized (lowercase, no spaces)');
            $table->string('kecamatan', 100)->comment('Nama kecamatan');
            $table->string('kota', 100)->comment('Nama kota/kabupaten');
            $table->decimal('latitude', 10, 8)->comment('Koordinat GPS Latitude');
            $table->decimal('longitude', 11, 8)->comment('Koordinat GPS Longitude');
            $table->boolean('is_active')->default(true)->comment('Status aktif');
            $table->string('source', 50)->default('generated')->comment('Sumber data: manual, generated, api');
            $table->string('accuracy', 20)->default('low')->comment('Tingkat akurasi: high, medium, low');
            $table->timestamps();
            
            // Indexes untuk performa pencarian
            $table->index('nama_normalized', 'idx_kelurahan_nama_normalized');
            $table->index('kecamatan', 'idx_kelurahan_kecamatan');
            $table->index('kota', 'idx_kelurahan_kota');
            $table->index(['latitude', 'longitude'], 'idx_kelurahan_coordinates');
            $table->index('is_active', 'idx_kelurahan_is_active');
            
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kelurahan_coordinates');
    }
};
