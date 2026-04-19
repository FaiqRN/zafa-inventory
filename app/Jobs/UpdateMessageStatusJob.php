<?php

namespace App\Jobs;

use App\Models\FollowUp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UpdateMessageStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120, 300];

    public function __construct(public int $followUpId)
    {
    }

    public function handle(): void
    {
        $followUp = FollowUp::query()->find($this->followUpId);

        if (!$this->isProcessableFollowUp($followUp)) {
            return;
        }

        $token = config('services.wablas.token') ?? env('WABLAS_TOKEN');
        $apiUrl = rtrim(config('services.wablas.api_url') ?? env('WABLAS_API_URL', 'https://texas.wablas.com/api'), '/');

        if (empty($token)) {
            Log::warning('UpdateMessageStatusJob skipped because WABLAS_TOKEN is missing.', [
                'follow_up_id' => $followUp->follow_up_id,
            ]);
            return;
        }

        $syncResult = $this->fetchMessageStatus($apiUrl, $token, (string) $followUp->wablas_message_id);
        $normalizedStatus = $this->resolveNormalizedStatus($followUp, $syncResult);

        if ($normalizedStatus === null) {
            return;
        }

        $this->applyStatusUpdate($followUp, $normalizedStatus, $syncResult['raw'] ?? null);
    }

    private function fetchMessageStatus(string $apiUrl, string $token, string $messageId): array
    {
        $headers = [
            'Authorization' => $token,
            'Accept' => 'application/json',
        ];

        $endpoints = [
            '/message/status',
            '/messages/status',
            '/chat/status',
            '/status/message',
        ];

        $queryVariants = [
            ['message_id' => $messageId],
            ['id' => $messageId],
            ['msgid' => $messageId],
        ];

        foreach ($endpoints as $endpoint) {
            foreach ($queryVariants as $query) {
                try {
                    $response = Http::timeout(20)
                        ->withHeaders($headers)
                        ->get($apiUrl . $endpoint, $query);

                    if (!$response->successful()) {
                        continue;
                    }

                    $payload = $response->json();
                    $status = $this->extractStatus($payload);

                    if ($status !== null) {
                        return [
                            'success' => true,
                            'status' => $status,
                            'raw' => $payload,
                        ];
                    }
                } catch (\Throwable $e) {
                    Log::debug('UpdateMessageStatusJob endpoint probe failed.', [
                        'endpoint' => $endpoint,
                        'query' => $query,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return [
            'success' => false,
            'reason' => 'no_working_status_endpoint',
        ];
    }

    private function extractStatus(mixed $payload): ?string
    {
        if (!is_array($payload)) {
            return null;
        }

        $paths = [
            'status',
            'message_status',
            'delivery_status',
            'state',
            'data.status',
            'data.message_status',
            'data.delivery_status',
            'data.state',
            'result.status',
            'message.status',
            'messages.0.status',
            'data.messages.0.status',
        ];

        return $this->firstStringAtPaths($payload, $paths);
    }

    private function isProcessableFollowUp(?FollowUp $followUp): bool
    {
        return $followUp instanceof FollowUp && !empty($followUp->wablas_message_id);
    }

    private function resolveNormalizedStatus(FollowUp $followUp, array $syncResult): ?string
    {
        if (!($syncResult['success'] ?? false)) {
            Log::info('UpdateMessageStatusJob could not resolve status from Wablas.', [
                'follow_up_id' => $followUp->follow_up_id,
                'wablas_message_id' => $followUp->wablas_message_id,
                'reason' => $syncResult['reason'] ?? 'unknown',
            ]);

            return null;
        }

        $rawStatus = (string) ($syncResult['status'] ?? '');
        $normalizedStatus = $this->normalizeStatus($rawStatus);

        if ($normalizedStatus === null) {
            Log::info('UpdateMessageStatusJob received unsupported status value.', [
                'follow_up_id' => $followUp->follow_up_id,
                'raw_status' => $syncResult['status'] ?? null,
            ]);

            return null;
        }

        $currentStatus = strtolower((string) $followUp->status);
        if ($this->statusRank($normalizedStatus) < $this->statusRank($currentStatus)) {
            $normalizedStatus = $currentStatus;
        }

        return $normalizedStatus;
    }

    private function applyStatusUpdate(FollowUp $followUp, string $normalizedStatus, mixed $rawPayload): void
    {
        $updateData = [
            FollowUp::FIELD_STATUS => $normalizedStatus,
            FollowUp::FIELD_WABLAS_RESPONSE => $this->mergeWablasResponse(
                $followUp->wablas_response,
                $rawPayload,
                $normalizedStatus
            ),
        ];

        $timestampUpdates = $this->buildTimestampUpdates($followUp, $normalizedStatus, Carbon::now());
        if ($timestampUpdates !== []) {
            $updateData = array_merge($updateData, $timestampUpdates);
        }

        if ($normalizedStatus === 'failed') {
            $updateData[FollowUp::FIELD_ERROR_MESSAGE] = 'Wablas reports message delivery failed.';
        }

        $followUp->fill($updateData);
        $followUp->save();
    }

    private function buildTimestampUpdates(FollowUp $followUp, string $normalizedStatus, Carbon $now): array
    {
        $updates = [];

        if ($normalizedStatus === 'delivered' && empty($followUp->delivered_at)) {
            $updates[FollowUp::FIELD_DELIVERED_AT] = $now;
        }

        if ($normalizedStatus === 'read') {
            if (empty($followUp->delivered_at)) {
                $updates[FollowUp::FIELD_DELIVERED_AT] = $now;
            }

            if (empty($followUp->read_at)) {
                $updates[FollowUp::FIELD_READ_AT] = $now;
            }
        }

        return $updates;
    }

    private function firstStringAtPaths(array $payload, array $paths): ?string
    {
        foreach ($paths as $path) {
            $value = Arr::get($payload, $path);
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function normalizeStatus(string $status): ?string
    {
        $normalized = strtolower(trim($status));

        if ($normalized === '') {
            return null;
        }

        $map = [
            'pending' => 'sent',
            'queued' => 'sent',
            'sent' => 'sent',
            'success' => 'sent',
            'delivered' => 'delivered',
            'delivery' => 'delivered',
            'received' => 'delivered',
            'read' => 'read',
            'seen' => 'read',
            'opened' => 'read',
            'failed' => 'failed',
            'error' => 'failed',
            'undelivered' => 'failed',
            'rejected' => 'failed',
        ];

        return $map[$normalized] ?? null;
    }

    private function statusRank(string $status): int
    {
        return match ($status) {
            'pending' => 0,
            'sent' => 1,
            'failed' => 1,
            'delivered' => 2,
            'read' => 3,
            default => -1,
        };
    }

    private function mergeWablasResponse(mixed $currentValue, mixed $rawPayload, string $normalizedStatus): array
    {
        $existing = is_array($currentValue) ? $currentValue : [];

        $existing['status_sync'] = [
            'checked_at' => now()->toIso8601String(),
            'normalized_status' => $normalizedStatus,
            'raw' => $rawPayload,
        ];

        return $existing;
    }
}
