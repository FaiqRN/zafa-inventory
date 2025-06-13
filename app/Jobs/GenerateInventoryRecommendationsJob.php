<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Http\Controllers\InventoryOptimizationController;
use Illuminate\Support\Facades\Log;

class GenerateInventoryRecommendationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;

    protected $batchSize;
    protected $offset;

    /**
     * Create a new job instance.
     */
    public function __construct($batchSize = 100, $offset = 0)
    {
        $this->batchSize = $batchSize;
        $this->offset = $offset;
    }

    /**
     * Execute the job.
     */
    public function handle(InventoryOptimizationController $controller): void
    {
        try {
            Log::info("Starting inventory recommendations generation", [
                'batch_size' => $this->batchSize,
                'offset' => $this->offset
            ]);

            // Generate recommendations with batching
            $count = $controller->generateInventoryRecommendations();

            Log::info("Completed inventory recommendations generation", [
                'recommendations_generated' => $count
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to generate inventory recommendations", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Inventory recommendations job failed", [
            'error' => $exception->getMessage(),
            'batch_size' => $this->batchSize,
            'offset' => $this->offset
        ]);
    }
}