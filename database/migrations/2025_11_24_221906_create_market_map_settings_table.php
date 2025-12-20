<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMarketMapSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('market_map_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique()->comment('Setting key');
            $table->string('value', 255)->comment('Setting value');
            $table->string('label', 255)->comment('Label untuk display');
            $table->string('description', 500)->nullable()->comment('Deskripsi setting');
            $table->string('type', 50)->default('number')->comment('Type: number, text, percentage');
            $table->string('unit', 20)->nullable()->comment('Unit: km, %, Rp, etc');
            $table->string('category', 50)->default('general')->comment('Category: clustering, profit, stock, etc');
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
        DB::table('market_map_settings')->insert([
            [
                'key' => 'cluster_radius',
                'value' => '1.5',
                'label' => 'Radius Clustering',
                'description' => 'Radius untuk mengelompokkan toko secara geografis (dalam kilometer)',
                'type' => 'number',
                'unit' => 'km',
                'category' => 'clustering',
                'min_value' => 0.5,
                'max_value' => 10,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'max_stores_per_cluster',
                'value' => '5',
                'label' => 'Maksimal Toko per Cluster',
                'description' => 'Jumlah maksimal toko yang dapat dikelompokkan dalam satu cluster',
                'type' => 'number',
                'unit' => 'toko',
                'category' => 'clustering',
                'min_value' => 2,
                'max_value' => 20,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'min_profit_margin',
                'value' => '10',
                'label' => 'Margin Profit Minimum',
                'description' => 'Margin profit minimum untuk kategori "Good Performance"',
                'type' => 'percentage',
                'unit' => '%',
                'category' => 'profit',
                'min_value' => 0,
                'max_value' => 100,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'good_profit_margin',
                'value' => '20',
                'label' => 'Margin Profit Bagus',
                'description' => 'Margin profit untuk kategori "Excellent Performance"',
                'type' => 'percentage',
                'unit' => '%',
                'category' => 'profit',
                'min_value' => 0,
                'max_value' => 100,
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'default_harga_awal',
                'value' => '12000',
                'label' => 'Harga Awal Default',
                'description' => 'Harga awal default untuk perhitungan jika data tidak tersedia',
                'type' => 'number',
                'unit' => 'Rp',
                'category' => 'stock',
                'min_value' => 1000,
                'max_value' => 1000000,
                'sort_order' => 5,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'default_initial_stock',
                'value' => '100',
                'label' => 'Stok Awal Default',
                'description' => 'Jumlah stok awal default untuk toko baru',
                'type' => 'number',
                'unit' => 'unit',
                'category' => 'stock',
                'min_value' => 10,
                'max_value' => 10000,
                'sort_order' => 6,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'cache_duration',
                'value' => '30',
                'label' => 'Durasi Cache',
                'description' => 'Durasi cache untuk data Market Map (dalam menit)',
                'type' => 'number',
                'unit' => 'menit',
                'category' => 'system',
                'min_value' => 5,
                'max_value' => 1440,
                'sort_order' => 7,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'sold_percentage_terkirim',
                'value' => '80',
                'label' => 'Persentase Terjual (Terkirim)',
                'description' => 'Asumsi persentase barang terjual dari total yang terkirim',
                'type' => 'percentage',
                'unit' => '%',
                'category' => 'calculation',
                'min_value' => 50,
                'max_value' => 100,
                'sort_order' => 8,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'sold_percentage_all',
                'value' => '60',
                'label' => 'Persentase Terjual (Semua)',
                'description' => 'Asumsi persentase barang terjual dari total pengiriman (termasuk proses)',
                'type' => 'percentage',
                'unit' => '%',
                'category' => 'calculation',
                'min_value' => 30,
                'max_value' => 100,
                'sort_order' => 9,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('market_map_settings');
    }
}
