<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            KonfigurasiIntervalKirimSeeder::class,
            MigrateRolesToSpatieSeeder::class,
            // TokoSeeder::class,
            BarangSeeder::class,
            // BarangStokSeeder::class,
            // BarangTokoSeeder::class,
            // PengirimanSeeder::class,
            // SsZscoreSettingSeeder::class,
            // EoqBiayaPesanGlobalSeeder::class,
            // EOQBiayaSimpanSeeder::class,
        ]);
    }
}
