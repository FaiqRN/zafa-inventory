<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\FollowUp;
use App\Jobs\UpdateMessageStatusJob;
use Carbon\Carbon;

class SyncFollowUpStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'followup:sync-status 
                            {--days=7 : Number of days to check back}
                            {--status=sent : Status to sync (sent, delivered)}
                            {--limit=100 : Maximum number of records to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync WhatsApp message status for follow up messages';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = $this->option('days');
        $status = $this->option('status');
        $limit = $this->option('limit');

        $this->info("Starting sync of follow up message status...");
        $this->info("Parameters: days={$days}, status={$status}, limit={$limit}");

        try {
            // Get follow ups that need status update
            $query = FollowUp::whereNotNull('wablas_message_id')
                ->where('created_at', '>=', Carbon::now()->subDays($days))
                ->where('status', $status)
                ->orderBy('created_at', 'desc');

            if ($limit > 0) {
                $query->limit($limit);
            }

            $followUps = $query->get();

            if ($followUps->isEmpty()) {
                $this->info("No follow up messages found to sync.");
                return 0;
            }

            $this->info("Found {$followUps->count()} follow up messages to sync.");

            $bar = $this->output->createProgressBar($followUps->count());
            $bar->start();

            $successCount = 0;
            $errorCount = 0;

            foreach ($followUps as $followUp) {
                try {
                    // Dispatch job to update status
                    UpdateMessageStatusJob::dispatch($followUp->follow_up_id)
                        ->delay(now()->addSeconds(rand(1, 10))); // Add random delay to avoid rate limiting

                    $successCount++;
                } catch (\Exception $e) {
                    $this->error("Error dispatching job for FollowUp {$followUp->follow_up_id}: " . $e->getMessage());
                    $errorCount++;
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine();

            $this->info("Sync completed!");
            $this->info("Jobs dispatched: {$successCount}");
            
            if ($errorCount > 0) {
                $this->warn("Errors encountered: {$errorCount}");
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("Command failed: " . $e->getMessage());
            Log::error("SyncFollowUpStatusCommand failed: " . $e->getMessage());
            return 1;
        }
    }
}