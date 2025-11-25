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
        Schema::create('partner_performance_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique()->comment('Setting key');
            $table->string('value', 255)->comment('Setting value');
            $table->string('label', 255)->comment('Label untuk display');
            $table->string('description', 500)->nullable()->comment('Deskripsi setting');
            $table->string('type', 50)->default('number')->comment('Type: number, decimal, text, percentage');
            $table->string('unit', 20)->nullable()->comment('Unit: %, point, etc');
            $table->string('category', 50)->default('general')->comment('Category: grading, weights, performance, etc');
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
        DB::table('partner_performance_settings')->insert([
            // Grading System
            [
                'key' => 'grade_a_plus_min',
                'value' => '85',
                'label' => 'Grade A+ Minimum',
                'description' => 'Minimum sell-through rate untuk grade A+ (Excellent Partner)',
                'type' => 'number',
                'unit' => '%',
                'category' => 'grading',
                'min_value' => 80,
                'max_value' => 100,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'grade_a_min',
                'value' => '75',
                'label' => 'Grade A Minimum',
                'description' => 'Minimum sell-through rate untuk grade A (Good Partner)',
                'type' => 'number',
                'unit' => '%',
                'category' => 'grading',
                'min_value' => 70,
                'max_value' => 90,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'grade_b_plus_min',
                'value' => '65',
                'label' => 'Grade B+ Minimum',
                'description' => 'Minimum sell-through rate untuk grade B+ (Average Partner)',
                'type' => 'number',
                'unit' => '%',
                'category' => 'grading',
                'min_value' => 60,
                'max_value' => 80,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'grade_b_min',
                'value' => '55',
                'label' => 'Grade B Minimum',
                'description' => 'Minimum sell-through rate untuk grade B (Below Average)',
                'type' => 'number',
                'unit' => '%',
                'category' => 'grading',
                'min_value' => 50,
                'max_value' => 70,
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'grade_c_min',
                'value' => '45',
                'label' => 'Grade C Minimum',
                'description' => 'Minimum sell-through rate untuk grade C (Poor Partner)',
                'type' => 'number',
                'unit' => '%',
                'category' => 'grading',
                'min_value' => 30,
                'max_value' => 60,
                'sort_order' => 5,
                'created_at' => now(),
                'updated_at' => now()
            ],
            // Performance Weights
            [
                'key' => 'weight_sell_through',
                'value' => '0.4',
                'label' => 'Bobot Sell-Through Rate',
                'description' => 'Bobot untuk sell-through rate dalam perhitungan performance score',
                'type' => 'decimal',
                'unit' => '',
                'category' => 'weights',
                'min_value' => 0,
                'max_value' => 1,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'weight_speed',
                'value' => '0.25',
                'label' => 'Bobot Kecepatan Return',
                'description' => 'Bobot untuk kecepatan return dalam perhitungan performance score',
                'type' => 'decimal',
                'unit' => '',
                'category' => 'weights',
                'min_value' => 0,
                'max_value' => 1,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'weight_revenue',
                'value' => '0.25',
                'label' => 'Bobot Revenue',
                'description' => 'Bobot untuk revenue dalam perhitungan performance score',
                'type' => 'decimal',
                'unit' => '',
                'category' => 'weights',
                'min_value' => 0,
                'max_value' => 1,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'weight_volume',
                'value' => '0.1',
                'label' => 'Bobot Volume',
                'description' => 'Bobot untuk volume transaksi dalam perhitungan performance score',
                'type' => 'decimal',
                'unit' => '',
                'category' => 'weights',
                'min_value' => 0,
                'max_value' => 1,
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now()
            ],
            // Performance Thresholds
            [
                'key' => 'good_performance_min',
                'value' => '70',
                'label' => 'Good Performance Minimum',
                'description' => 'Threshold minimum untuk kategori Good Performance',
                'type' => 'number',
                'unit' => '%',
                'category' => 'performance',
                'min_value' => 50,
                'max_value' => 100,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'warning_performance_max',
                'value' => '50',
                'label' => 'Warning Performance Maximum',
                'description' => 'Threshold maksimum untuk warning performance (dibawah ini perlu perhatian)',
                'type' => 'number',
                'unit' => '%',
                'category' => 'performance',
                'min_value' => 30,
                'max_value' => 70,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'alert_days_no_transaction',
                'value' => '30',
                'label' => 'Alert Days No Transaction',
                'description' => 'Jumlah hari tanpa transaksi untuk memicu alert',
                'type' => 'number',
                'unit' => 'hari',
                'category' => 'performance',
                'min_value' => 7,
                'max_value' => 90,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now()
            ],
            // Calculation Settings
            [
                'key' => 'analysis_period_months',
                'value' => '3',
                'label' => 'Periode Analisis',
                'description' => 'Periode analisis performance dalam bulan',
                'type' => 'number',
                'unit' => 'bulan',
                'category' => 'calculation',
                'min_value' => 1,
                'max_value' => 12,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'min_transactions_for_grading',
                'value' => '5',
                'label' => 'Minimum Transaksi untuk Grading',
                'description' => 'Jumlah minimum transaksi yang diperlukan untuk memberikan grade',
                'type' => 'number',
                'unit' => 'transaksi',
                'category' => 'calculation',
                'min_value' => 1,
                'max_value' => 20,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'cache_duration',
                'value' => '60',
                'label' => 'Durasi Cache',
                'description' => 'Durasi cache untuk data partner performance (dalam menit)',
                'type' => 'number',
                'unit' => 'menit',
                'category' => 'system',
                'min_value' => 15,
                'max_value' => 1440,
                'sort_order' => 1,
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
        Schema::dropIfExists('partner_performance_settings');
    }
};
