<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCoordinatesToTokoTableForGeocoding extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('toko', function (Blueprint $table) {
            // Cek apakah kolom sudah ada untuk menghindari duplikasi
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
            
            // Tambah timestamps jika belum ada
            if (!Schema::hasColumn('toko', 'created_at')) {
                $table->timestamps();
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
            $columnsToRemove = [];
            
            if (Schema::hasColumn('toko', 'latitude')) {
                $columnsToRemove[] = 'latitude';
            }
            
            if (Schema::hasColumn('toko', 'longitude')) {
                $columnsToRemove[] = 'longitude';
            }
            
            if (Schema::hasColumn('toko', 'is_active')) {
                $columnsToRemove[] = 'is_active';
            }
            
            if (Schema::hasColumn('toko', 'catatan_lokasi')) {
                $columnsToRemove[] = 'catatan_lokasi';
            }
            
            if (Schema::hasColumn('toko', 'alamat_lengkap_geocoding')) {
                $columnsToRemove[] = 'alamat_lengkap_geocoding';
            }
            
            if (!empty($columnsToRemove)) {
                $table->dropColumn($columnsToRemove);
            }
        });
    }
}