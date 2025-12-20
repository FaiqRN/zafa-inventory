<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTokoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('toko', function (Blueprint $table) {
            $table->string('toko_id', 10)->primary();
            $table->string('nama_toko', 100);
            $table->string('pemilik', 100);
            $table->text('alamat');
            $table->string('wilayah_kecamatan', 100);
            $table->string('wilayah_kelurahan', 100);
            
            // Add jalan_id column
            $table->unsignedBigInteger('jalan_id')->nullable()->comment('Foreign key ke jalans table - referensi jalan yang dipilih');
            $table->foreign('jalan_id')->references('id')->on('jalans')->onDelete('set null');
            $table->index('jalan_id', 'idx_toko_jalan_id');

            $table->string('wilayah_kota_kabupaten', 100);
            $table->string('nomer_telpon', 20);
            
            // Geocoding fields
            $table->decimal('latitude', 10, 8)->nullable()->comment('Koordinat GPS Latitude');
            $table->decimal('longitude', 11, 8)->nullable()->comment('Koordinat GPS Longitude');
            $table->boolean('is_active')->default(true)->comment('Status aktif toko');
            $table->text('catatan_lokasi')->nullable()->comment('Catatan tambahan lokasi');
            $table->text('alamat_lengkap_geocoding')->nullable()->comment('Alamat lengkap hasil geocoding');
            $table->string('geocoding_provider', 50)->nullable()->comment('Provider used for geocoding');
            $table->string('geocoding_accuracy', 20)->nullable()->comment('Accuracy level');
            $table->decimal('geocoding_confidence', 5, 3)->nullable()->comment('Confidence score');
            $table->string('geocoding_quality', 20)->nullable()->comment('Overall quality assessment');
            $table->decimal('geocoding_score', 5, 2)->nullable()->comment('Quality score 0-100');
            $table->timestamp('geocoding_last_updated')->nullable()->comment('Last geocoding update');
            
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->string('user_create')->nullable();
            $table->string('user_update')->nullable();
            
            // Indexes
            $table->index(['latitude', 'longitude'], 'idx_toko_coordinates');
            $table->index('geocoding_quality', 'idx_toko_geocoding_quality');
            $table->index('is_active', 'idx_toko_is_active');
            
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('toko');
    }
}