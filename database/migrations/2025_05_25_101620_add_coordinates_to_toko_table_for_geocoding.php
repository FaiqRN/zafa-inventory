<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('toko', function (Blueprint $table) {
            // Basic coordinates (jika belum ada)
            if (!Schema::hasColumn('toko', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable()->after('nomer_telpon')->comment('Koordinat GPS Latitude');
            }
            
            if (!Schema::hasColumn('toko', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable()->after('latitude')->comment('Koordinat GPS Longitude');
            }
            
            if (!Schema::hasColumn('toko', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('longitude')->comment('Status aktif toko');
            }
            
            if (!Schema::hasColumn('toko', 'catatan_lokasi')) {
                $table->text('catatan_lokasi')->nullable()->after('is_active')->comment('Catatan tambahan lokasi');
            }
            
            if (!Schema::hasColumn('toko', 'alamat_lengkap_geocoding')) {
                $table->text('alamat_lengkap_geocoding')->nullable()->after('catatan_lokasi')->comment('Alamat lengkap hasil geocoding');
            }
            
            // Enhanced geocoding metadata fields
            if (!Schema::hasColumn('toko', 'geocoding_provider')) {
                $table->string('geocoding_provider', 50)->nullable()->after('alamat_lengkap_geocoding')->comment('Provider used for geocoding');
            }
            
            if (!Schema::hasColumn('toko', 'geocoding_accuracy')) {
                $table->string('geocoding_accuracy', 20)->nullable()->after('geocoding_provider')->comment('Accuracy level');
            }
            
            if (!Schema::hasColumn('toko', 'geocoding_confidence')) {
                $table->decimal('geocoding_confidence', 5, 3)->nullable()->after('geocoding_accuracy')->comment('Confidence score');
            }
            
            if (!Schema::hasColumn('toko', 'geocoding_quality')) {
                $table->string('geocoding_quality', 20)->nullable()->after('geocoding_confidence')->comment('Overall quality assessment');
            }
            
            if (!Schema::hasColumn('toko', 'geocoding_score')) {
                $table->decimal('geocoding_score', 5, 2)->nullable()->after('geocoding_quality')->comment('Quality score 0-100');
            }
            
            if (!Schema::hasColumn('toko', 'geocoding_last_updated')) {
                $table->timestamp('geocoding_last_updated')->nullable()->after('geocoding_score')->comment('Last geocoding update');
            }
            
            // Add timestamps if not exists
            if (!Schema::hasColumn('toko', 'created_at')) {
                $table->timestamps();
            }
        });
        
        // Add indexes for better performance
        Schema::table('toko', function (Blueprint $table) {
            // Check if indexes don't exist before adding
            $indexes = Schema::getConnection()->getDoctrineSchemaManager()->listTableIndexes('toko');
            
            if (!isset($indexes['idx_toko_coordinates'])) {
                $table->index(['latitude', 'longitude'], 'idx_toko_coordinates');
            }
            
            if (!isset($indexes['idx_toko_geocoding_quality'])) {
                $table->index('geocoding_quality', 'idx_toko_geocoding_quality');
            }
            
            if (!isset($indexes['idx_toko_is_active'])) {
                $table->index('is_active', 'idx_toko_is_active');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('toko', function (Blueprint $table) {
            // Drop indexes first
            $indexes = ['idx_toko_coordinates', 'idx_toko_geocoding_quality', 'idx_toko_is_active'];
            foreach ($indexes as $index) {
                try {
                    $table->dropIndex($index);
                } catch (\Exception $e) {
                    // Index might not exist, continue
                }
            }
            
            // Drop columns if they exist
            $columnsToRemove = [
                'geocoding_last_updated',
                'geocoding_score',
                'geocoding_quality',
                'geocoding_confidence',
                'geocoding_accuracy',
                'geocoding_provider',
                'alamat_lengkap_geocoding',
                'catatan_lokasi',
                'is_active',
                'longitude',
                'latitude'
            ];
            
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('toko', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};