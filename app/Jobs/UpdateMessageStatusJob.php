<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\FollowUp;
use App\Services\WablasService;
use Carbon\Carbon;

class UpdateMessageStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $followUpId;
    protected $maxRetries = 5;

    /**
     * Create a new job instance.
     */
    public function __construct($followUpId)
    {
        $this->followUpId = $followUpId;
        $this->onQueue('follow-up-status');
    }

    /**
     * Execute the job.
     */
    public function handle(WablasService $wablasService): void
    {
        try {
            $followUp = FollowUp::find($this->followUpId);
            
            if (!$followUp || !$followUp->wablas_message_id) {
                Log::warning("FollowUp not found or missing message ID: {$this->followUpId}");
                return;
            }

            // Skip if already delivered or read
            if (in_array($followUp->status, ['delivered', 'read'])) {
                return;
            }

            // Check message status from Wablas
            $statusResponse = $wablasService->checkMessageStatus($followUp->wablas_message_id);
            
            if ($statusResponse['success'] ?? false) {
                $messageStatus = $statusResponse['data']['status'] ?? null;
                $this->updateFollowUpStatus($followUp, $messageStatus);
                
                Log::info("Updated message status for FollowUp {$this->followUpId}: {$messageStatus}");
            } else {
                Log::warning("Failed to check status for FollowUp {$this->followUpId}: " . ($statusResponse['error'] ?? 'Unknown error'));
                
                // Retry the job with delay if not max retries
                if ($this->attempts() < $this->maxRetries) {
                    $this->release(300); // Retry after 5 minutes
                }
            }

        } catch (\Exception $e) {
            Log::error("Error updating message status for FollowUp {$this->followUpId}: " . $e->getMessage());
            
            // Retry the job with delay if not max retries
            if ($this->attempts() < $this->maxRetries) {
                $this->release(600); // Retry after 10 minutes
            } else {
                // Mark as failed after max retries
                $followUp = FollowUp::find($this->followUpId);
                if ($followUp && $followUp->status === 'sent') {
                    $followUp->update([
                        'status' => 'failed',
                        'error_message' => 'Max retries exceeded for status check'
                    ]);
                }
            }
        }
    }

    /**
     * Update follow up status based on Wablas response
     */
    private function updateFollowUpStatus(FollowUp $followUp, $messageStatus)
    {
        $now = Carbon::now();
        $updateData = [];

        switch ($messageStatus) {
            case 'sent':
                if ($followUp->status !== 'sent') {
                    $updateData['status'] = 'sent';
                    $updateData['sent_at'] = $now;
                }
                break;
                
            case 'delivered':
                $updateData['status'] = 'delivered';
                $updateData['delivered_at'] = $now;
                if (!$followUp->sent_at) {
                    $updateData['sent_at'] = $now;
                }
                break;
                
            case 'read':
                $updateData['status'] = 'read';
                $updateData['read_at'] = $now;
                if (!$followUp->delivered_at) {
                    $updateData['delivered_at'] = $now;
                }
                if (!$followUp->sent_at) {
                    $updateData['sent_at'] = $now;
                }
                break;
                
            case 'failed':
                $updateData['status'] = 'failed';
                $updateData['error_message'] = 'Message delivery failed';
                break;
        }

        if (!empty($updateData)) {
            $followUp->update($updateData);
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("UpdateMessageStatusJob failed for FollowUp {$this->followUpId}: " . $exception->getMessage());
        
        // Mark follow up as failed
        $followUp = FollowUp::find($this->followUpId);
        if ($followUp && $followUp->status === 'sent') {
            $followUp->update([
                'status' => 'failed',
                'error_message' => 'Status check job failed: ' . $exception->getMessage()
            ]);
        }
    }
}