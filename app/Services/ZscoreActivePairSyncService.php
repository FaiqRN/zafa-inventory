<?php

namespace App\Services;

use App\Models\Pengiriman;
use App\Models\Zscore;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ZscoreActivePairSyncService
{
    private const DEFAULT_ZSCORE_ROWS = [
        [
            'label' => 'Rendah',
            'service_level' => 90.00,
            'z_score' => 1.2816,
            'keterangan' => 'Cocok untuk produk dengan permintaan sangat stabil',
        ],
        [
            'label' => 'Standar',
            'service_level' => 95.00,
            'z_score' => 1.6449,
            'keterangan' => 'Nilai default yang umum digunakan pada sistem consignment UMKM',
        ],
        [
            'label' => 'Tinggi',
            'service_level' => 97.00,
            'z_score' => 1.8808,
            'keterangan' => 'Untuk produk prioritas atau mitra strategis',
        ],
        [
            'label' => 'Sangat Tinggi',
            'service_level' => 99.00,
            'z_score' => 2.3263,
            'keterangan' => 'Untuk produk dengan risiko stockout sangat tinggi',
        ],
    ];

    /**
     * Sinkronkan default Z-score untuk semua pasangan toko-barang aktif.
     *
     * Pasangan aktif didefinisikan dari pengiriman dalam rentang hari tertentu.
     * Baris yang sudah ada tidak diubah; hanya menambah baris default yang masih missing.
     *
     * @return array{
     *   active_pairs: int,
     *   default_levels_per_pair: int,
     *   expected_default_rows: int,
     *   existing_default_rows: int,
     *   missing_default_rows_before: int,
     *   inserted_rows: int,
     *   missing_default_rows_after: int,
     *   dry_run: bool,
     * }
     */
    public function syncActivePairs(int $activeWindowDays = 180, bool $persist = true, string $actor = 'system'): array
    {
        $activeWindowDays = max(1, $activeWindowDays);

        $activePairs = $this->getActivePairs($activeWindowDays);
        $defaultRows = self::DEFAULT_ZSCORE_ROWS;
        $defaultLevels = array_map(
            static fn(array $row): float => (float) $row['service_level'],
            $defaultRows
        );

        if ($activePairs->isEmpty()) {
            return [
                'active_pairs' => 0,
                'default_levels_per_pair' => count($defaultRows),
                'expected_default_rows' => 0,
                'existing_default_rows' => 0,
                'missing_default_rows_before' => 0,
                'inserted_rows' => 0,
                'missing_default_rows_after' => 0,
                'dry_run' => !$persist,
            ];
        }

        $activePairSet = [];
        $tokoIds = [];
        $barangIds = [];

        foreach ($activePairs as $pair) {
            $tokoId = (string) $pair->{Pengiriman::FIELD_TOKO_ID};
            $barangId = (string) $pair->{Pengiriman::FIELD_BARANG_ID};

            $activePairSet[$this->composePairKey($tokoId, $barangId)] = [
                'toko_id' => $tokoId,
                'barang_id' => $barangId,
            ];

            $tokoIds[$tokoId] = true;
            $barangIds[$barangId] = true;
        }

        $existingKeys = $this->getExistingRowKeys(
            array_keys($tokoIds),
            array_keys($barangIds),
            $defaultLevels,
            $activePairSet
        );

        $rowsToInsert = [];
        $now = now();

        foreach ($activePairSet as $pair) {
            foreach ($defaultRows as $defaultRow) {
                $serviceLevel = (float) $defaultRow['service_level'];
                $rowKey = $this->composeRowKey($pair['toko_id'], $pair['barang_id'], $serviceLevel);

                if (isset($existingKeys[$rowKey])) {
                    continue;
                }

                $rowsToInsert[] = [
                    Zscore::FIELD_TOKO_ID => $pair['toko_id'],
                    Zscore::FIELD_BARANG_ID => $pair['barang_id'],
                    Zscore::FIELD_LABEL => $defaultRow['label'],
                    Zscore::FIELD_SERVICE_LEVEL => $serviceLevel,
                    Zscore::FIELD_Z_SCORE => (float) $defaultRow['z_score'],
                    Zscore::FIELD_KETERANGAN => $defaultRow['keterangan'],
                    Zscore::FIELD_USER_CREATE => $actor,
                    Zscore::FIELD_USER_UPDATE => $actor,
                    Zscore::FIELD_CREATED_AT => $now,
                    Zscore::FIELD_UPDATED_AT => $now,
                ];
            }
        }

        $expectedRows = count($activePairSet) * count($defaultRows);
        $missingBefore = max(0, $expectedRows - count($existingKeys));

        $insertedRows = 0;
        if ($persist && !empty($rowsToInsert)) {
            foreach (array_chunk($rowsToInsert, 1000) as $chunk) {
                $insertedRows += DB::table(Zscore::TABLE)->insertOrIgnore($chunk);
            }
        }

        $missingAfter = $persist
            ? max(0, $missingBefore - $insertedRows)
            : $missingBefore;

        return [
            'active_pairs' => count($activePairSet),
            'default_levels_per_pair' => count($defaultRows),
            'expected_default_rows' => $expectedRows,
            'existing_default_rows' => count($existingKeys),
            'missing_default_rows_before' => $missingBefore,
            'inserted_rows' => $persist ? $insertedRows : 0,
            'missing_default_rows_after' => $missingAfter,
            'dry_run' => !$persist,
        ];
    }

    private function getActivePairs(int $activeWindowDays): Collection
    {
        $cutoff = Carbon::now()->subDays($activeWindowDays);

        return Pengiriman::query()
            ->where(Pengiriman::FIELD_TANGGAL_PENGIRIMAN, '>=', $cutoff)
            ->select([Pengiriman::FIELD_TOKO_ID, Pengiriman::FIELD_BARANG_ID])
            ->distinct()
            ->get();
    }

    /**
     * @param string[] $tokoIds
     * @param string[] $barangIds
     * @param float[] $serviceLevels
     * @param array<string, array{toko_id: string, barang_id: string}> $activePairSet
     * @return array<string, true>
     */
    private function getExistingRowKeys(array $tokoIds, array $barangIds, array $serviceLevels, array $activePairSet): array
    {
        $existingRows = Zscore::query()
            ->select([
                Zscore::FIELD_TOKO_ID,
                Zscore::FIELD_BARANG_ID,
                Zscore::FIELD_SERVICE_LEVEL,
            ])
            ->whereIn(Zscore::FIELD_TOKO_ID, $tokoIds)
            ->whereIn(Zscore::FIELD_BARANG_ID, $barangIds)
            ->whereIn(Zscore::FIELD_SERVICE_LEVEL, $serviceLevels)
            ->get();

        $existingKeys = [];

        foreach ($existingRows as $row) {
            $tokoId = (string) $row->{Zscore::FIELD_TOKO_ID};
            $barangId = (string) $row->{Zscore::FIELD_BARANG_ID};
            $pairKey = $this->composePairKey($tokoId, $barangId);

            if (!isset($activePairSet[$pairKey])) {
                continue;
            }

            $serviceLevel = (float) $row->{Zscore::FIELD_SERVICE_LEVEL};
            $rowKey = $this->composeRowKey($tokoId, $barangId, $serviceLevel);
            $existingKeys[$rowKey] = true;
        }

        return $existingKeys;
    }

    private function composePairKey(string $tokoId, string $barangId): string
    {
        return $tokoId . '|' . $barangId;
    }

    private function composeRowKey(string $tokoId, string $barangId, float $serviceLevel): string
    {
        return $this->composePairKey($tokoId, $barangId)
            . '|' . number_format($serviceLevel, 2, '.', '');
    }
}
