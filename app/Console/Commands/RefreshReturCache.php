<?php

namespace App\Console\Commands;

use App\Services\ReturCacheService;
use Illuminate\Console\Command;

class RefreshReturCache extends Command
{

    protected $signature = 'cache:refresh-retur';

    protected $description = 'Refresh cache data retur untuk performa optimal';

    public function handle()
    {
        $this->info('Refreshing retur cache...');
        
        $startTime = microtime(true);
        
        $stats = ReturCacheService::refreshCache();
        
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);
        
        $this->info("Cache refreshed successfully!");
        $this->info("Total retur: " . $stats['total_retur']);
        $this->info("Total items: " . $stats['total_items']);
        $this->info("Total hasil: Rp " . number_format($stats['total_hasil'], 0, ',', '.'));
        $this->info("Duration: {$duration}ms");
        
        return Command::SUCCESS;
    }
}
