<?php

namespace App\Console\Commands;

use App\Services\ZscoreActivePairSyncService;
use Illuminate\Console\Command;

class SyncZscoreActivePairs extends Command
{
    protected $signature = 'zscore:sync-active-pairs
                            {--days=180 : Periode hari untuk menentukan pasangan aktif}
                            {--dry-run : Hanya simulasi, tanpa insert data}';

    protected $description = 'Sinkronisasi default Z-score untuk pasangan toko-barang aktif agar tidak ada missing pair';

    public function __construct(private ZscoreActivePairSyncService $syncService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $dryRun = (bool) $this->option('dry-run');

        $this->info('Menjalankan sinkronisasi pasangan aktif Z-score...');
        $this->line('Mode: ' . ($dryRun ? 'dry-run' : 'persist'));
        $this->line("Window aktif: {$days} hari");

        $start = microtime(true);
        $stats = $this->syncService->syncActivePairs($days, !$dryRun);
        $durationMs = round((microtime(true) - $start) * 1000, 2);

        $this->table(
            ['Metric', 'Value'],
            [
                ['active_pairs', (string) ($stats['active_pairs'] ?? 0)],
                ['default_levels_per_pair', (string) ($stats['default_levels_per_pair'] ?? 0)],
                ['expected_default_rows', (string) ($stats['expected_default_rows'] ?? 0)],
                ['existing_default_rows', (string) ($stats['existing_default_rows'] ?? 0)],
                ['missing_default_rows_before', (string) ($stats['missing_default_rows_before'] ?? 0)],
                ['inserted_rows', (string) ($stats['inserted_rows'] ?? 0)],
                ['missing_default_rows_after', (string) ($stats['missing_default_rows_after'] ?? 0)],
                ['duration_ms', (string) $durationMs],
            ]
        );

        if (($stats['missing_default_rows_after'] ?? 0) > 0) {
            $this->warn('Masih ada missing pair setelah sinkronisasi. Cek log atau jalankan ulang command.');
        } else {
            $this->info('Sinkronisasi selesai. Tidak ada missing pair untuk pasangan aktif.');
        }

        return self::SUCCESS;
    }
}
