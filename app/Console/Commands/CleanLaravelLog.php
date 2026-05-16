<?php

namespace App\Console\Commands;

use App\Helpers\DashboardMonitorLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanLaravelLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laravel-log:clean
                            {--force : Paksa hapus meskipun file kosong}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bersihkan isi file laravel.log (Automatic Monthly Cleanup via Scheduler)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $logPath = storage_path('logs/laravel.log');

        if (!file_exists($logPath)) {
            $this->warn('File laravel.log tidak ditemukan. Tidak ada yang perlu dibersihkan.');
            return Command::SUCCESS;
        }

        $sizeBefore = filesize($logPath);

        if ($sizeBefore === 0 && !$this->option('force')) {
            $this->info('File laravel.log sudah kosong. Tidak ada yang perlu dibersihkan.');
            return Command::SUCCESS;
        }

        // Truncate the log file
        file_put_contents($logPath, '');

        $sizeBeforeKb = round($sizeBefore / 1024, 2);

        $this->info("✓ laravel.log berhasil dibersihkan. Ukuran sebelumnya: {$sizeBeforeKb} KB.");

        // Catat ke DashboardMonitor activity log
        try {
            DashboardMonitorLogger::delete(
                'Laravel Log',
                "Automatic monthly cleanup laravel.log ({$sizeBeforeKb} KB dihapus via scheduler)",
                [
                    'file'        => 'storage/logs/laravel.log',
                    'size_before' => "{$sizeBeforeKb} KB",
                    'triggered'   => 'scheduler',
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('CleanLaravelLog: gagal catat ke DashboardMonitor — ' . $e->getMessage());
        }

        return Command::SUCCESS;
    }
}
