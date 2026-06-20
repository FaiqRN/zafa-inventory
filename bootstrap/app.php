<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append([
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\EnforceHttps::class,
        ]);
        $middleware->alias([
            'auth.custom' => \App\Http\Middleware\RedirectIfNotAuthenticated::class,
            'nocache' => \App\Http\Middleware\NoCache::class,
            'verifysession' => \App\Http\Middleware\VerifySession::class,
            'session.timeout' => \App\Http\Middleware\SessionTimeout::class,
            'prevent.back' => \App\Http\Middleware\PreventBackAfterLogout::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'check.user.role' => \App\Http\Middleware\CheckUserHasRole::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash([
            'current_password',
            'password',
            'password_confirmation',
        ]);
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Anda tidak memiliki izin untuk mengakses resource ini.',
                    'error' => $e->getMessage(),
                ], 403);
            }
            return response()->view('errors.403', ['exception' => $e], 403);
        });
        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Aksi ini tidak diizinkan.',
                    'error' => $e->getMessage(),
                ], 403);
            }
            return response()->view('errors.403', ['exception' => $e], 403);
        });
    })
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule): void {
        $followUpEnabled = (bool) config('followup.enabled', true);

        if ($followUpEnabled) {
            $schedule->command('followup:sync-status --days=1 --status=sent --limit=50')
                ->everyFifteenMinutes()->withoutOverlapping()
                ->description('Sync follow up message status - sent messages')
                ->onFailure(fn() => \Illuminate\Support\Facades\Log::error('followup:sync-status failed'));

            $schedule->command('followup:sync-status --days=1 --status=delivered --limit=30')
                ->everyThirtyMinutes()->withoutOverlapping()
                ->description('Sync follow up message status - delivered messages');

            $schedule->command('whatsapp:debug --check-device')
                ->everyFiveMinutes()->withoutOverlapping()
                ->description('Monitor WhatsApp device connection status')
                ->onFailure(fn() => \Illuminate\Support\Facades\Log::warning('WhatsApp device status check failed'));

            $schedule->command('followup:cleanup --days=90')
                ->dailyAt('02:00')
                ->description('Cleanup old follow up records and files')
                ->onSuccess(fn() => \Illuminate\Support\Facades\Log::info('Follow up cleanup completed successfully'));

            $schedule->command('whatsapp:report --daily')
                ->dailyAt('08:00')->environments(['production'])
                ->description('Generate daily WhatsApp usage report');

            $schedule->command('whatsapp:report --weekly')
                ->weeklyOn(1, '09:00')->environments(['production'])
                ->description('Generate weekly WhatsApp performance summary');

            $schedule->command('whatsapp:cleanup-logs --days=30')
                ->monthlyOn(1, '03:00')
                ->description('Cleanup old WhatsApp logs and temporary files');

            $schedule->command('whatsapp:health-check')
                ->hourly()->withoutOverlapping()
                ->description('Check WhatsApp system health and send alerts')
                ->onFailure(fn() => \Illuminate\Support\Facades\Log::critical('WhatsApp health check failed'));

            $schedule->command('followup:optimize-database')
                ->weekly()->sundays()->at('04:00')
                ->description('Optimize follow up database tables');

            $schedule->command('followup:auto-campaign --type=pelangganTidakKembali --dry-run=false')
                ->weeklyOn(2, '10:00')->environments(['production'])
                ->description('Automated follow up for inactive customers')
                ->when(fn() => config('app.enable_auto_campaigns', false));

            $schedule->command('followup:auto-campaign --type=pelangganBaru --limit=50')
                ->dailyAt('14:00')->environments(['production'])
                ->description('Welcome message for new customers')
                ->when(fn() => config('app.enable_auto_campaigns', false));
        }

        $schedule->call(function () {
            $syncStats = app(\App\Services\ZscoreActivePairSyncService::class)
                ->syncActivePairs(180, true, 'scheduler');
            if (($syncStats['inserted_rows'] ?? 0) > 0) {
                \Illuminate\Support\Facades\Log::info('zscore active pair auto-sync menambah missing pair', $syncStats);
            }
        })->dailyAt('01:30')
          ->name('zscore-active-pairs-auto-sync')
          ->withoutOverlapping()
          ->description('Auto-sync default Z-score untuk pasangan aktif toko-barang (6 bulan)')
          ->onFailure(fn() => \Illuminate\Support\Facades\Log::error('zscore active pair auto-sync failed'));

        $schedule->command('dashboard-monitor:clean --days=30')
            ->dailyAt('03:30')->withoutOverlapping()
            ->description('Auto cleanup dashboard monitor logs (>30 hari)')
            ->onSuccess(fn() => \Illuminate\Support\Facades\Log::info('dashboard-monitor:clean completed successfully'))
            ->onFailure(fn() => \Illuminate\Support\Facades\Log::error('dashboard-monitor:clean failed'));

        $schedule->command('laravel-log:clean')
            ->monthlyOn(1, '04:00')->withoutOverlapping()
            ->description('Automatic monthly cleanup laravel.log')
            ->onSuccess(fn() => \Illuminate\Support\Facades\Log::info('laravel-log:clean completed successfully'))
            ->onFailure(fn() => \Illuminate\Support\Facades\Log::error('laravel-log:clean failed'));
    })
    ->create();