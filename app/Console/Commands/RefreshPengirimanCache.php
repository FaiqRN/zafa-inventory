<?php

namespace App\Console\Commands;

use App\Services\PengirimanCacheService;
use Illuminate\Console\Command;

class RefreshPengirimanCache extends Command
{

    protected $signature = 'cache:refresh-pengiriman';

    protected $description = 'Refresh cache data pengiriman untuk performa optimal';

    public function handle()
    {
        $this->info('Refreshing pengiriman cache...');
        
        $startTime = microtime(true);
        
        $stats = PengirimanCacheService::refreshCache();
        
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);
        
        $this->info("Cache refreshed successfully!");
        $this->info("Total pengiriman: " . $stats['total_pengiriman']);
        $this->info("Total items: " . $stats['total_items']);
        $this->info("Duration: {$duration}ms");
        
        return Command::SUCCESS;
    }
}
