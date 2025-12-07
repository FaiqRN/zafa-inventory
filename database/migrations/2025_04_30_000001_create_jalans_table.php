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
        Schema::create('jalans', function (Blueprint $table) {
            $table->id();
            $table->string('nama_jalan', 200)->comment('Nama jalan lengkap');
            $table->string('nama_normalized', 200)->comment('Nama jalan normalized (lowercase, no spaces) untuk fuzzy matching');
            $table->unsignedBigInteger('kelurahan_id')->nullable()->comment('Foreign key ke kelurahan_coordinates');
            $table->decimal('latitude', 10, 8)->comment('Koordinat GPS Latitude');
            $table->decimal('longitude', 11, 8)->comment('Koordinat GPS Longitude');
            $table->boolean('is_active')->default(true)->comment('Status aktif jalan');
            $table->string('source', 50)->default('manual')->comment('Sumber data: manual, csv, api');
            $table->string('accuracy', 20)->default('medium')->comment('Tingkat akurasi: high, medium, low');
            $table->timestamps();
            
            // Foreign key constraint
            $table->foreign('kelurahan_id')
                  ->references('id')
                  ->on('kelurahan_coordinates')
                  ->onDelete('set null');
            
            // Indexes untuk performa pencarian
            $table->index('nama_normalized', 'idx_jalans_nama_normalized');
            $table->index('kelurahan_id', 'idx_jalans_kelurahan_id');
            $table->index(['latitude', 'longitude'], 'idx_jalans_coordinates');
            $table->index('is_active', 'idx_jalans_is_active');
            
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jalans');
    }
};
