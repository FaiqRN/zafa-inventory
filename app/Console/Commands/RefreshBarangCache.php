<?php

namespace App\Console\Commands;

use App\Services\BarangCacheService;
use Illuminate\Console\Command;

class RefreshBarangCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:refresh-barang';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh cache data barang untuk performa optimal';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Refreshing barang cache...');
        
        $startTime = microtime(true);
        
        $data = BarangCacheService::refreshCache();
        
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);
        
        $this->info("Cache refreshed successfully!");
        $this->info("Total barang: " . $data->count());
        $this->info("Duration: {$duration}ms");
        
        return Command::SUCCESS;
    }
}
