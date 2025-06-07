<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\FollowUp;
use Carbon\Carbon;

class CleanupFollowUpCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'followup:cleanup {--days=90 : Days to keep records}';

    /**
     * The console command description.
     */
    protected $description = 'Cleanup old follow up records and associated files';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = $this->option('days');
        $cutoffDate = Carbon::now()->subDays($days);

        $this->info("Cleaning up follow up records older than {$days} days (before {$cutoffDate->format('Y-m-d')})...");

        // Get old records
        $oldRecords = FollowUp::where('created_at', '<', $cutoffDate)->get();

        if ($oldRecords->isEmpty()) {
            $this->info('No old records found to cleanup.');
            return 0;
        }

        $this->info("Found {$oldRecords->count()} records to cleanup.");

        $deletedFiles = 0;
        $deletedRecords = 0;

        foreach ($oldRecords as $record) {
            // Delete associated image files
            if (!empty($record->images)) {
                foreach ($record->images as $imagePath) {
                    if (Storage::disk('public')->exists($imagePath)) {
                        Storage::disk('public')->delete($imagePath);
                        $deletedFiles++;
                    }
                }
            }

            // Delete the record
            $record->delete();
            $deletedRecords++;
        }

        $this->info("Cleanup completed!");
        $this->info("Deleted records: {$deletedRecords}");
        $this->info("Deleted files: {$deletedFiles}");

        return 0;
    }
}