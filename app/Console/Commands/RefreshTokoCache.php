<?php

namespace App\Console\Commands;

use App\Services\TokoCacheService;
use Illuminate\Console\Command;

class RefreshTokoCache extends Command
{

    protected $signature = 'cache:refresh-toko';


    protected $description = 'Refresh cache data toko untuk performa optimal';

    public function handle()
    {
        $this->info('Refreshing toko cache...');
        
        $startTime = microtime(true);
        
        $data = TokoCacheService::refreshCache();
        
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);
        
        $this->info("Cache refreshed successfully!");
        $this->info("Total toko: " . $data->count());
        $this->info("Duration: {$duration}ms");
        
        return Command::SUCCESS;
    }
}
