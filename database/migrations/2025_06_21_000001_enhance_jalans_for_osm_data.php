<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Enhancement untuk tabel jalans agar support data OSM dengan multiple koordinat
     */
    public function up(): void
    {
        // 1. Tambah kolom baru ke tabel jalans
        Schema::table('jalans', function (Blueprint $table) {
            $table->bigInteger('osm_id')->nullable()->after('id')->comment('OpenStreetMap ID');
            $table->string('highway_type', 50)->nullable()->after('nama_normalized')->comment('Tipe jalan: residential, secondary, tertiary, primary');
            $table->string('surface', 50)->nullable()->after('highway_type')->comment('Permukaan jalan: asphalt, paving_stones, dll');
            $table->decimal('total_length_meters', 10, 2)->nullable()->after('longitude')->comment('Total panjang jalan dalam meter');
            $table->decimal('center_lat', 10, 8)->nullable()->after('total_length_meters')->comment('Latitude titik tengah jalan');
            $table->decimal('center_lng', 11, 8)->nullable()->after('center_lat')->comment('Longitude titik tengah jalan');
            
            // Index untuk osm_id
            $table->index('osm_id', 'idx_jalans_osm_id');
            $table->index('highway_type', 'idx_jalans_highway_type');
        });

        // 2. Buat tabel jalan_segments untuk menyimpan polyline koordinat
        Schema::create('jalan_segments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('jalan_id')->comment('Foreign key ke jalans');
            $table->unsignedSmallInteger('sequence')->comment('Urutan titik dalam polyline (1, 2, 3, ...)');
            $table->decimal('latitude', 10, 8)->comment('Koordinat GPS Latitude');
            $table->decimal('longitude', 11, 8)->comment('Koordinat GPS Longitude');
            $table->decimal('distance_from_start', 10, 2)->default(0)->comment('Jarak dari titik awal dalam meter');
            $table->timestamps();

            // Foreign key
            $table->foreign('jalan_id')
                  ->references('id')
                  ->on('jalans')
                  ->onDelete('cascade');

            // Indexes untuk performa
            $table->index(['jalan_id', 'sequence'], 'idx_segments_jalan_sequence');
            $table->index(['latitude', 'longitude'], 'idx_segments_coordinates');
            $table->index('distance_from_start', 'idx_segments_distance');

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
        });

        // 3. Buat tabel pois untuk Point of Interest (landmark)
        Schema::create('pois', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('osm_id')->nullable()->comment('OpenStreetMap ID');
            $table->string('nama', 200)->comment('Nama POI');
            $table->string('nama_normalized', 200)->comment('Nama normalized untuk search');
            $table->string('kategori', 50)->comment('Kategori: convenience, restaurant, school, hospital, dll');
            $table->decimal('latitude', 10, 8)->comment('Koordinat GPS Latitude');
            $table->decimal('longitude', 11, 8)->comment('Koordinat GPS Longitude');
            $table->string('alamat_jalan', 200)->nullable()->comment('Nama jalan dari OSM');
            $table->string('nomor_rumah', 20)->nullable()->comment('Nomor rumah/bangunan');
            $table->string('kode_pos', 10)->nullable()->comment('Kode pos');
            $table->unsignedBigInteger('kelurahan_id')->nullable()->comment('Foreign key ke kelurahan_coordinates');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Foreign key
            $table->foreign('kelurahan_id')
                  ->references('id')
                  ->on('kelurahan_coordinates')
                  ->onDelete('set null');

            // Indexes
            $table->index('osm_id', 'idx_pois_osm_id');
            $table->index('nama_normalized', 'idx_pois_nama_normalized');
            $table->index('kategori', 'idx_pois_kategori');
            $table->index(['latitude', 'longitude'], 'idx_pois_coordinates');
            $table->index('kelurahan_id', 'idx_pois_kelurahan_id');
            $table->index('is_active', 'idx_pois_is_active');

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop tabel POIs
        Schema::dropIfExists('pois');

        // Drop tabel jalan_segments
        Schema::dropIfExists('jalan_segments');

        // Remove kolom tambahan dari jalans
        Schema::table('jalans', function (Blueprint $table) {
            $table->dropIndex('idx_jalans_osm_id');
            $table->dropIndex('idx_jalans_highway_type');
            
            $table->dropColumn([
                'osm_id',
                'highway_type', 
                'surface',
                'total_length_meters',
                'center_lat',
                'center_lng'
            ]);
        });
    }
};
