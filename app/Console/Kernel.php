<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // ========================================
        // WHATSAPP FOLLOW UP AUTOMATION SCHEDULE
        // ========================================
        
        // Sync WhatsApp message status setiap 15 menit
        $schedule->command('followup:sync-status --days=1 --status=sent --limit=50')
                 ->everyFifteenMinutes()
                 ->withoutOverlapping()
                 ->description('Sync follow up message status - sent messages')
                 ->onFailure(function () {
                     Log::error('followup:sync-status command failed');
                 });

        // Sync delivered messages setiap 30 menit
        $schedule->command('followup:sync-status --days=1 --status=delivered --limit=30')
                 ->everyThirtyMinutes()
                 ->withoutOverlapping()
                 ->description('Sync follow up message status - delivered messages');

        // Check WhatsApp device status setiap 5 menit
        $schedule->command('whatsapp:debug --check-device')
                 ->everyFiveMinutes()
                 ->withoutOverlapping()
                 ->description('Monitor WhatsApp device connection status')
                 ->onFailure(function () {
                     // Send alert to admin if device is disconnected
                     Log::warning('WhatsApp device status check failed');
                 });

        // Cleanup old follow up records (older than 90 days) setiap hari jam 2 pagi
        $schedule->command('followup:cleanup --days=90')
                 ->dailyAt('02:00')
                 ->description('Cleanup old follow up records and files')
                 ->onSuccess(function () {
                     Log::info('Follow up cleanup completed successfully');
                 });

        // Daily WhatsApp statistics report jam 8 pagi
        $schedule->command('whatsapp:report --daily')
                 ->dailyAt('08:00')
                 ->description('Generate daily WhatsApp usage report')
                 ->environments(['production']);

        // Weekly WhatsApp performance summary setiap Senin jam 9 pagi  
        $schedule->command('whatsapp:report --weekly')
                 ->weeklyOn(1, '09:00')
                 ->description('Generate weekly WhatsApp performance summary')
                 ->environments(['production']);

        // Monthly cleanup of old WhatsApp logs
        $schedule->command('whatsapp:cleanup-logs --days=30')
                 ->monthlyOn(1, '03:00')
                 ->description('Cleanup old WhatsApp logs and temporary files');

        // ========================================
        // HEALTH CHECKS AND MONITORING
        // ========================================
        
        // Check system health setiap jam
        $schedule->command('whatsapp:health-check')
                 ->hourly()
                 ->withoutOverlapping()
                 ->description('Check WhatsApp system health and send alerts')
                 ->onFailure(function () {
                     // Send critical alert
                     Log::critical('WhatsApp health check failed');
                 });

        // Database maintenance untuk follow up table
        $schedule->command('followup:optimize-database')
                 ->weekly()
                 ->sundays()
                 ->at('04:00')
                 ->description('Optimize follow up database tables');

        // ========================================
        // AUTOMATED FOLLOW UP CAMPAIGNS (Optional)
        // ========================================
        
        // Auto follow up untuk pelanggan tidak kembali (setiap Selasa jam 10 pagi)
        $schedule->command('followup:auto-campaign --type=pelangganTidakKembali --dry-run=false')
                 ->weeklyOn(2, '10:00')
                 ->description('Automated follow up for inactive customers')
                 ->environments(['production'])
                 ->when(function () {
                     // Only run if auto campaigns are enabled
                     return config('app.enable_auto_campaigns', false);
                 });

        // Auto follow up untuk pelanggan baru (setiap hari jam 14:00)
        $schedule->command('followup:auto-campaign --type=pelangganBaru --limit=50')
                 ->dailyAt('14:00')
                 ->description('Welcome message for new customers')
                 ->environments(['production'])
                 ->when(function () {
                     return config('app.enable_auto_campaigns', false);
                 });
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    /**
     * Get the timezone that should be used by default for scheduled events.
     *
     * @return \DateTimeZone|string|null
     */
    protected function scheduleTimezone()
    {
        return 'Asia/Jakarta'; // Indonesian timezone
    }
}