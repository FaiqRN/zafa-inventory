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
        Schema::create('seasonal_inventory_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique()->comment('Setting key');
            $table->string('value', 255)->comment('Setting value');
            $table->string('label', 255)->comment('Label untuk display');
            $table->string('description', 500)->nullable()->comment('Deskripsi setting');
            $table->string('type', 50)->default('number')->comment('Type: number, text, percentage');
            $table->string('unit', 20)->nullable()->comment('Unit: hari, %, bulan, etc');
            $table->string('category', 50)->default('general')->comment('Category: forecast, safety_stock, seasonal, etc');
            $table->integer('min_value')->nullable()->comment('Minimum value allowed');
            $table->integer('max_value')->nullable()->comment('Maximum value allowed');
            $table->integer('sort_order')->default(0)->comment('Display order');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->string('user_create')->nullable();
            $table->string('user_update')->nullable();

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
        });

        // Insert default values
        DB::table('seasonal_inventory_settings')->insert([
            [
                'key' => 'forecast_period',
                'value' => '90',
                'label' => 'Periode Forecast',
                'description' => 'Periode waktu untuk melakukan forecast inventory (dalam hari)',
                'type' => 'number',
                'unit' => 'hari',
                'category' => 'forecast',
                'min_value' => 30,
                'max_value' => 365,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'historical_data_months',
                'value' => '12',
                'label' => 'Data Historis',
                'description' => 'Jumlah bulan data historis yang digunakan untuk analisis',
                'type' => 'number',
                'unit' => 'bulan',
                'category' => 'forecast',
                'min_value' => 3,
                'max_value' => 24,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'safety_stock_percentage',
                'value' => '20',
                'label' => 'Safety Stock Percentage',
                'description' => 'Persentase tambahan untuk safety stock',
                'type' => 'percentage',
                'unit' => '%',
                'category' => 'safety_stock',
                'min_value' => 10,
                'max_value' => 50,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'lead_time_days',
                'value' => '7',
                'label' => 'Lead Time',
                'description' => 'Waktu tunggu pengiriman dari supplier (dalam hari)',
                'type' => 'number',
                'unit' => 'hari',
                'category' => 'safety_stock',
                'min_value' => 1,
                'max_value' => 60,
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'high_season_multiplier',
                'value' => '1.5',
                'label' => 'Multiplier Musim Ramai',
                'description' => 'Faktor pengali untuk periode musim ramai',
                'type' => 'number',
                'unit' => 'x',
                'category' => 'seasonal',
                'min_value' => 1,
                'max_value' => 3,
                'sort_order' => 5,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'low_season_multiplier',
                'value' => '0.7',
                'label' => 'Multiplier Musim Sepi',
                'description' => 'Faktor pengali untuk periode musim sepi',
                'type' => 'number',
                'unit' => 'x',
                'category' => 'seasonal',
                'min_value' => 0.3,
                'max_value' => 1,
                'sort_order' => 6,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'reorder_point_days',
                'value' => '14',
                'label' => 'Reorder Point',
                'description' => 'Titik pemesanan ulang berdasarkan estimasi kebutuhan (dalam hari)',
                'type' => 'number',
                'unit' => 'hari',
                'category' => 'safety_stock',
                'min_value' => 7,
                'max_value' => 30,
                'sort_order' => 7,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'max_stock_months',
                'value' => '3',
                'label' => 'Maksimal Stok',
                'description' => 'Maksimal stok yang disimpan (dalam bulan kebutuhan)',
                'type' => 'number',
                'unit' => 'bulan',
                'category' => 'safety_stock',
                'min_value' => 1,
                'max_value' => 6,
                'sort_order' => 8,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'alert_threshold',
                'value' => '30',
                'label' => 'Threshold Alert',
                'description' => 'Persentase stok untuk memicu alert (dibawah nilai ini akan ada warning)',
                'type' => 'percentage',
                'unit' => '%',
                'category' => 'safety_stock',
                'min_value' => 10,
                'max_value' => 50,
                'sort_order' => 9,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'cache_duration',
                'value' => '60',
                'label' => 'Durasi Cache',
                'description' => 'Durasi cache untuk data seasonal inventory (dalam menit)',
                'type' => 'number',
                'unit' => 'menit',
                'category' => 'system',
                'min_value' => 15,
                'max_value' => 1440,
                'sort_order' => 10,
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
        Schema::dropIfExists('seasonal_inventory_settings');
    }
};
