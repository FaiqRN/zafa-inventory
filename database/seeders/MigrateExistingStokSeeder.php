<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MigrateExistingStokSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Memulai migrasi data stok existing...');

        // Ambil semua barang yang punya stok > 0
        $barangList = \App\Models\Barang::where('stok', '>', 0)->get();

        $migrated = 0;
        $skipped = 0;

        foreach ($barangList as $barang) {
            // Cek apakah sudah ada record di barang_stok untuk barang ini
            $existingStok = \App\Models\BarangStok::where('barang_id', $barang->barang_id)->count();

            if ($existingStok > 0) {
                $this->command->warn("Barang {$barang->barang_kode} - {$barang->nama_barang} sudah memiliki data stok, dilewati");
                $skipped++;
                continue;
            }

            // Buat record baru di barang_stok
            \App\Models\BarangStok::create([
                'barang_id' => $barang->barang_id,
                'tanggal_stock_barang' => $barang->tanggal_stock_barang ?? now(),
                'stok' => $barang->stok,
                'sisa_stok' => $barang->stok,
                'stok_awal' => $barang->stok,
                'catatan' => 'Migrasi data existing',
                'user_create' => 'system',
            ]);

            $this->command->info("✓ Migrasi {$barang->barang_kode} - {$barang->nama_barang}: {$barang->stok} unit");
            $migrated++;
        }

        $this->command->info("\n=== Hasil Migrasi ===");
        $this->command->info("Total barang dimigrasi: {$migrated}");
        $this->command->info("Total barang dilewati: {$skipped}");
        $this->command->info("Migrasi selesai!");
    }
}
