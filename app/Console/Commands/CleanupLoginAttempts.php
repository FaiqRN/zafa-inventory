<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LoginAttempt;

class CleanupLoginAttempts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'login:cleanup {--days=7 : Number of days to keep login attempts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup old login attempts from database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        
        $this->info("Cleaning up login attempts older than {$days} days...");
        
        $deleted = LoginAttempt::where('attempted_at', '<', now()->subDays($days))->delete();
        
        $this->info("Deleted {$deleted} old login attempt records.");
        
        return Command::SUCCESS;
    }
}
