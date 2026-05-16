<?php

namespace App\Console\Commands;

use App\Models\DashboardMonitorLog;
use Illuminate\Console\Command;

class CleanDashboardMonitorLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dashboard-monitor:clean {--days=30 : Hapus log yang lebih tua dari N hari}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hapus activity log yang sudah lebih tua dari jumlah hari yang ditentukan (default: 30 hari)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days    = (int) $this->option('days');
        $deleted = DashboardMonitorLog::cleanup($days);

        $this->info("✓ {$deleted} record activity log yang lebih tua dari {$days} hari berhasil dihapus.");

        return Command::SUCCESS;
    }
}
