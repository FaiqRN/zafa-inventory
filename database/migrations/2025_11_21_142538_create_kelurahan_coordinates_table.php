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
            $table->string('nama', 100);
            $table->string('nama_normalized', 100);
            $table->string('kecamatan', 100);
            $table->string('kota', 100);
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Indexes for performance
            $table->index('nama_normalized', 'idx_nama_normalized');
            $table->index('kecamatan', 'idx_kecamatan');
            $table->index('kota', 'idx_kota');
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
