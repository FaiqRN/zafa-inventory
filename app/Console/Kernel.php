<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Sync WhatsApp message status setiap 15 menit
        $schedule->command('followup:sync-status --days=1 --status=sent --limit=50')
                 ->everyFifteenMinutes()
                 ->withoutOverlapping()
                 ->description('Sync follow up message status - sent messages');

        // Sync delivered messages setiap 30 menit
        $schedule->command('followup:sync-status --days=1 --status=delivered --limit=30')
                 ->everyThirtyMinutes()
                 ->withoutOverlapping()
                 ->description('Sync follow up message status - delivered messages');

        // Cleanup old follow up records (older than 90 days) setiap hari jam 2 pagi
        $schedule->command('followup:cleanup --days=90')
                 ->dailyAt('02:00')
                 ->description('Cleanup old follow up records');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}