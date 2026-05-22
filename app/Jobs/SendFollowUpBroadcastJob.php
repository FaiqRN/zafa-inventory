<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendFollowUpBroadcastJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Jumlah percobaan ulang jika gagal.
     */
    public int $tries = 2;

    /**
     * Timeout maksimum per job (detik).
     */
    public int $timeout = 180;

    /**
     * Interval backoff antar percobaan (detik).
     */
    public array $backoff = [30, 60];

    public function __construct(
        public readonly int    $followUpId,
        public readonly string $phone,
        public readonly string $customerName,
        public readonly string $message,
        public readonly array  $imageUrls,
        public readonly string $targetType,
    ) {}

    public function handle(): void
    {
        // Batalkan jika batch sudah di-cancel
        if ($this->batch()?->cancelled()) {
            return;
        }

        $wablasToken = config('services.wablas.token') ?? env('WABLAS_TOKEN');
        $wablasUrl   = rtrim(config('services.wablas.api_url') ?? env('WABLAS_API_URL', 'https://texas.wablas.com/api'), '/');

        if (empty($wablasToken)) {
            $this->markFollowUpFailed('Wablas token tidak dikonfigurasi');
            return;
        }

        $headers = [
            'Authorization' => $wablasToken,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ];

        $hasError      = false;
        $lastMessageId = null;
        $results       = [];

        // Kirim gambar terlebih dahulu
        if (!empty($this->imageUrls)) {
            foreach ($this->imageUrls as $index => $imageUrl) {
                $caption = ($index === 0 && !empty($this->message))
                    ? $this->message
                    : ($index === 0 ? "Gambar untuk {$this->customerName}" : '');

                $imageResult = $this->sendImageMessage($wablasUrl, $headers, $imageUrl, $caption);
                $results[]   = $imageResult;

                if (!$imageResult['success']) {
                    $hasError = true;
                    Log::warning("SendFollowUpBroadcastJob: gagal kirim gambar [{$index}] ke {$this->phone}", [
                        'error' => $imageResult['error'],
                    ]);
                } else {
                    $lastMessageId = $imageResult['message_id'];
                }

                // Jeda antar gambar agar tidak rate-limit
                if ($index < count($this->imageUrls) - 1) {
                    sleep(2);
                }
            }
        }

        // Kirim pesan teks jika tidak ada gambar, atau gambar gagal semua
        if (!empty($this->message) && (empty($this->imageUrls) || $hasError)) {
            if (!empty($this->imageUrls)) {
                sleep(2);
            }

            $textResult = $this->sendTextMessage($wablasUrl, $headers);
            $results[]  = $textResult;

            if (!$textResult['success']) {
                $hasError = true;
            } else {
                $lastMessageId = $textResult['message_id'];
            }
        }

        // Perbarui status follow_up di database
        if ($hasError) {
            $this->markFollowUpFailed('Beberapa pesan/gambar gagal dikirim');
        } else {
            $this->markFollowUpSent($lastMessageId, $results);
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function sendImageMessage(string $apiUrl, array $headers, string $imageUrl, string $caption): array
    {
        try {
            $response = Http::timeout(90)
                ->withHeaders($headers)
                ->post($apiUrl . '/send-image', [
                    'phone'   => $this->phone,
                    'image'   => $imageUrl,
                    'caption' => $caption,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['status']) && $data['status'] === true) {
                    return [
                        'success'    => true,
                        'message_id' => $this->extractMessageId($data),
                        'response'   => $data,
                    ];
                }

                return [
                    'success'  => false,
                    'error'    => $data['message'] ?? 'Unknown error from Wablas',
                    'response' => $data,
                ];
            }

            return [
                'success'  => false,
                'error'    => 'HTTP ' . $response->status() . ': ' . $response->body(),
                'response' => $response->json(),
            ];
        } catch (\Throwable $e) {
            return [
                'success'  => false,
                'error'    => $e->getMessage(),
                'response' => null,
            ];
        }
    }

    private function sendTextMessage(string $apiUrl, array $headers): array
    {
        try {
            $response = Http::timeout(60)
                ->withHeaders($headers)
                ->post($apiUrl . '/send-message', [
                    'phone'   => $this->phone,
                    'message' => $this->message,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['status']) && $data['status'] === true) {
                    return [
                        'success'    => true,
                        'message_id' => $this->extractMessageId($data),
                        'response'   => $data,
                    ];
                }

                return [
                    'success'  => false,
                    'error'    => $data['message'] ?? 'Unknown error from Wablas',
                    'response' => $data,
                ];
            }

            return [
                'success'  => false,
                'error'    => 'HTTP ' . $response->status() . ': ' . $response->body(),
                'response' => $response->json(),
            ];
        } catch (\Throwable $e) {
            return [
                'success'  => false,
                'error'    => $e->getMessage(),
                'response' => null,
            ];
        }
    }

    private function extractMessageId(array $data): ?string
    {
        return $data['data']['messages'][0]['id']
            ?? $data['data']['id']
            ?? $data['id']
            ?? $data['message_id']
            ?? null;
    }

    private function markFollowUpSent(?string $messageId, array $results): void
    {
        DB::table('follow_up')
            ->where('follow_up_id', $this->followUpId)
            ->update([
                'status'            => 'sent',
                'sent_at'           => now(),
                'wablas_message_id' => $messageId,
                'wablas_response'   => json_encode($results),
                'updated_at'        => now(),
            ]);

        Log::info("SendFollowUpBroadcastJob: berhasil kirim ke {$this->customerName} ({$this->phone})", [
            'follow_up_id' => $this->followUpId,
            'message_id'   => $messageId,
        ]);
    }

    private function markFollowUpFailed(string $reason): void
    {
        DB::table('follow_up')
            ->where('follow_up_id', $this->followUpId)
            ->update([
                'status'        => 'failed',
                'error_message' => $reason,
                'updated_at'    => now(),
            ]);

        Log::error("SendFollowUpBroadcastJob: gagal kirim ke {$this->customerName} ({$this->phone}): {$reason}", [
            'follow_up_id' => $this->followUpId,
        ]);
    }

    /**
     * Tangani job yang gagal setelah semua percobaan habis.
     */
    public function failed(\Throwable $exception): void
    {
        $this->markFollowUpFailed('Job gagal: ' . $exception->getMessage());
    }
}
