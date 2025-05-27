<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            TokoSeeder::class,
            BarangSeeder::class,
            BarangTokoSeeder::class,
            PengirimanSeeder::class,
            ReturSeeder::class,
            PemesananSeeder::class,
            LaporanPenjualanSeeder::class,
        ]);
    }
}
