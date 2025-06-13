<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('seasonal_adjustments', function (Blueprint $table) {
            $table->id();
            $table->integer('month')->unique()->comment('1-12 untuk Januari-Desember');
            $table->decimal('multiplier', 8, 2)->default(1.00)->comment('Seasonal multiplier');
            $table->string('description')->comment('Deskripsi seasonal period');
            $table->boolean('is_active')->default(true);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            
            $table->index(['month', 'is_active']);
        });

        // Insert default seasonal adjustments
        DB::table('seasonal_adjustments')->insert([
            [
                'month' => 1, 
                'multiplier' => 1.10, 
                'description' => 'Perayaan Tahun Baru - peningkatan konsumsi', 
                'is_active' => true, 
                'created_at' => now(), 
                'updated_at' => now()
            ],
            [
                'month' => 2, 
                'multiplier' => 0.95, 
                'description' => 'Periode normalisasi pasca liburan', 
                'is_active' => true, 
                'created_at' => now(), 
                'updated_at' => now()
            ],
            [
                'month' => 3, 
                'multiplier' => 1.20, 
                'description' => 'Persiapan Ramadan - stocking merchandise', 
                'is_active' => true, 
                'created_at' => now(), 
                'updated_at' => now()
            ],
            [
                'month' => 4, 
                'multiplier' => 1.15, 
                'description' => 'Bulan Ramadan - konsumsi tinggi', 
                'is_active' => true, 
                'created_at' => now(), 
                'updated_at' => now()
            ],
            [
                'month' => 5, 
                'multiplier' => 1.00, 
                'description' => 'Kembali ke pola konsumsi normal', 
                'is_active' => true, 
                'created_at' => now(), 
                'updated_at' => now()
            ],
            [
                'month' => 6, 
                'multiplier' => 1.10, 
                'description' => 'Awal liburan sekolah - peningkatan aktivitas', 
                'is_active' => true, 
                'created_at' => now(), 
                'updated_at' => now()
            ],
            [
                'month' => 7, 
                'multiplier' => 1.10, 
                'description' => 'Liburan sekolah berlanjut', 
                'is_active' => true, 
                'created_at' => now(), 
                'updated_at' => now()
            ],
            [
                'month' => 8, 
                'multiplier' => 1.00, 
                'description' => 'Persiapan kembali sekolah', 
                'is_active' => true, 
                'created_at' => now(), 
                'updated_at' => now()
            ],
            [
                'month' => 9, 
                'multiplier' => 1.00, 
                'description' => 'Periode konsumsi normal', 
                'is_active' => true, 
                'created_at' => now(), 
                'updated_at' => now()
            ],
            [
                'month' => 10, 
                'multiplier' => 0.95, 
                'description' => 'Awal musim hujan - sedikit penurunan', 
                'is_active' => true, 
                'created_at' => now(), 
                'updated_at' => now()
            ],
            [
                'month' => 11, 
                'multiplier' => 0.95, 
                'description' => 'Musim hujan - dampak pada distribusi', 
                'is_active' => true, 
                'created_at' => now(), 
                'updated_at' => now()
            ],
            [
                'month' => 12, 
                'multiplier' => 1.15, 
                'description' => 'Liburan akhir tahun - peningkatan konsumsi', 
                'is_active' => true, 
                'created_at' => now(), 
                'updated_at' => now()
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seasonal_adjustments');
    }
};