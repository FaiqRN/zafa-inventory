<?php

namespace App\Services\DashboardInventoryOptimization;

use App\Exceptions\SsCalculationException;
use App\Models\Pengiriman;
use App\Models\Retur;
use App\Models\Zscore;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SsService
{
    public const DEFAULT_HARI_OBSERVASI = 365;

    // FIX #3: Default service level diubah dari 95 ke 95 — tetap sama, tapi sekarang
    // hanya dipakai sebagai fallback jika tidak ada baris is_active=true DAN
    // tidak ada baris service_level=95 sama sekali.
    private const DEFAULT_SERVICE_LEVEL = 95.00;
    private const DEFAULT_Z_SCORE       = 1.6449;

    private const DEFAULT_LEAD_TIME     = 5.0;
    private const DEFAULT_SIGMA_L       = 1.0;

    /**
     * Hitung Safety Stock.
     * Rumus: SS = Z × sqrt((L × σd²) + (d² × σL²))
     */
    public function hitung(
        string $tokoId,
        string $barangId,
        int    $hariObservasi = self::DEFAULT_HARI_OBSERVASI
    ): array {
        if ($hariObservasi <= 0) {
            throw new \InvalidArgumentException('Hari observasi harus lebih besar dari 0.');
        }

        // ── 1. Z-score ──────────────────────────────────────────────────────
        [$z, $serviceLevel] = $this->resolveZ($tokoId, $barangId);

        // ── 2. Demand harian (d) dan σd ─────────────────────────────────────
        [$d, $sigmaD] = $this->hitungDemandStats($tokoId, $barangId, $hariObservasi);

        // ── 3. Lead time (L) dan σL ─────────────────────────────────────────
        [$leadTime, $sigmaL] = $this->hitungLeadTimeStats($tokoId, $barangId, $hariObservasi);

        if ($leadTime <= 0) {
            $leadTime = self::DEFAULT_LEAD_TIME;
            Log::warning('SsService: leadTime=0 terdeteksi, pakai default', [
                'toko_id'   => $tokoId,
                'barang_id' => $barangId,
            ]);
        }
        if ($sigmaL <= 0) {
            $sigmaL = self::DEFAULT_SIGMA_L;
        }

        // ── 4. SS = Z × sqrt((L × σd²) + (d² × σL²)) ───────────────────────
        $inner  = ($leadTime * ($sigmaD ** 2)) + (($d ** 2) * ($sigmaL ** 2));
        $ssRaw  = $z * sqrt($inner);
        $ss     = (int) ceil($ssRaw);

        if ($d > 0 && $ss <= 0) {
            $ss = 1;
        }
        $ss = max(0, $ss);

        Log::debug('SsService::hitung result', [
            'toko_id'   => $tokoId,
            'barang_id' => $barangId,
            'd'         => round($d, 4),
            'sigma_d'   => round($sigmaD, 4),
            'L'         => $leadTime,
            'sigma_L'   => $sigmaL,
            'Z'         => $z,
            'ss_raw'    => round($ssRaw, 4),
            'ss'        => $ss,
        ]);

        return [
            'ss'                    => $ss,
            'd'                     => $d,
            'L'                     => $leadTime,
            'sigma_d'               => $sigmaD,
            'sigma_L'               => $sigmaL,
            'z_dipakai'             => $z,
            'service_level_dipakai' => $serviceLevel,
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // PRIVATE METHODS
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * FIX #3: Resolusi Z-score sekarang menggunakan is_active=true.
     *
     * Urutan prioritas:
     *   1. Baris dengan is_active=true untuk pasangan toko+barang ini   ← BARU
     *   2. Fallback ke service_level = DEFAULT_SERVICE_LEVEL (95%) jika tidak ada yang aktif
     *   3. Fallback ke DEFAULT_Z_SCORE hardcoded jika tidak ada data sama sekali
     *
     * Sebelumnya: orderByDesc(service_level)->first() → selalu ambil 99%
     */
    private function resolveZ(string $tokoId, string $barangId): array
    {
        // Prioritas 1: ambil yang di-set aktif oleh user
        $active = Zscore::where(Zscore::FIELD_TOKO_ID, $tokoId)
            ->where(Zscore::FIELD_BARANG_ID, $barangId)
            ->where(Zscore::FIELD_IS_ACTIVE, true)
            ->first();

        if ($active) {
            return [
                (float) $active->{Zscore::FIELD_Z_SCORE},
                (float) $active->{Zscore::FIELD_SERVICE_LEVEL},
            ];
        }

        // Prioritas 2: fallback ke service level default (95%)
        $default = Zscore::where(Zscore::FIELD_TOKO_ID, $tokoId)
            ->where(Zscore::FIELD_BARANG_ID, $barangId)
            ->where(Zscore::FIELD_SERVICE_LEVEL, self::DEFAULT_SERVICE_LEVEL)
            ->first();

        if ($default) {
            Log::info('SsService: tidak ada is_active=true, fallback ke service_level=95%', [
                'toko_id'   => $tokoId,
                'barang_id' => $barangId,
            ]);
            return [
                (float) $default->{Zscore::FIELD_Z_SCORE},
                (float) $default->{Zscore::FIELD_SERVICE_LEVEL},
            ];
        }

        // Prioritas 3: tidak ada data sama sekali, pakai hardcoded default
        Log::warning('SsService: fallback Z-score hardcoded default (tidak ada data zscore)', [
            'toko_id'   => $tokoId,
            'barang_id' => $barangId,
        ]);
        return [self::DEFAULT_Z_SCORE, self::DEFAULT_SERVICE_LEVEL];
    }

    /**
     * Hitung d dan σd dari data retur.
     */
    private function hitungDemandStats(
        string $tokoId,
        string $barangId,
        int    $hariObservasi
    ): array {
        $cutoff = Carbon::now()->subDays($hariObservasi)->toDateString();
        $result = $this->queryReturEvents($tokoId, $barangId, $cutoff, $hariObservasi);

        if ($result !== null) {
            return $result;
        }

        $latestTanggal = Retur::where(Retur::FIELD_TOKO_ID, $tokoId)
            ->where(Retur::FIELD_BARANG_ID, $barangId)
            ->max(Retur::FIELD_TANGGAL_RETUR);

        if ($latestTanggal) {
            $adaptiveCutoff = Carbon::parse($latestTanggal)
                ->subDays($hariObservasi)
                ->toDateString();

            Log::info('SsService: adaptive window demand', [
                'toko_id'         => $tokoId,
                'barang_id'       => $barangId,
                'adaptive_cutoff' => $adaptiveCutoff,
            ]);

            $result = $this->queryReturEvents($tokoId, $barangId, $adaptiveCutoff, $hariObservasi);
            if ($result !== null) {
                return $result;
            }
        }

        return $this->hitungDemandDariPengiriman($tokoId, $barangId, $hariObservasi);
    }

    private function queryReturEvents(
        string $tokoId,
        string $barangId,
        string $cutoff,
        int    $hariObservasi
    ): ?array {
        $rows = Retur::where(Retur::FIELD_TOKO_ID, $tokoId)
            ->where(Retur::FIELD_BARANG_ID, $barangId)
            ->whereDate(Retur::FIELD_TANGGAL_RETUR, '>=', $cutoff)
            ->where(Retur::FIELD_TOTAL_TERJUAL, '>', 0)
            ->orderBy(Retur::FIELD_TANGGAL_RETUR)
            ->get([Retur::FIELD_TANGGAL_RETUR, Retur::FIELD_TOTAL_TERJUAL]);

        if ($rows->isEmpty()) {
            return null;
        }

        $total = $rows->sum(fn($r) => (float) $r->{Retur::FIELD_TOTAL_TERJUAL});
        if ($total <= 0) {
            return null;
        }

        $n = $rows->count();
        $d = $total / $hariObservasi;

        // Hitung σd dari daily-rate riil per event (bukan asumsi interval merata)
        if ($n >= 2) {
            $windowEnd   = Carbon::now();
            $dailyRates  = [];

            foreach ($rows as $idx => $row) {
                $tglEvent = Carbon::parse($row->{Retur::FIELD_TANGGAL_RETUR});
                $qty      = (float) $row->{Retur::FIELD_TOTAL_TERJUAL};

                // Interval: jarak ke event berikutnya, atau ke batas window untuk event terakhir
                if ($idx < $n - 1) {
                    $nextTgl  = Carbon::parse($rows[$idx + 1]->{Retur::FIELD_TANGGAL_RETUR});
                    $interval = max(1.0, (float) $tglEvent->diffInDays($nextTgl));
                } else {
                    $interval = max(1.0, (float) $tglEvent->diffInDays($windowEnd));
                }

                $dailyRates[] = $qty / $interval;
            }

            $sigmaD = $this->standarDeviasi($dailyRates);
        } else {
            $sigmaD = sqrt($d);
        }

        Log::debug('SsService::queryReturEvents', [
            'toko_id'    => $tokoId,
            'barang_id'  => $barangId,
            'n_events'   => $n,
            'total'      => $total,
            'd'          => round($d, 4),
            'sigma_d'    => round($sigmaD, 4),
            'mode_sigma' => $n >= 2 ? 'interval_riil' : 'sqrt(d)',
        ]);

        return [$d, $sigmaD];
    }

    private function hitungDemandDariPengiriman(
        string $tokoId,
        string $barangId,
        int    $hariObservasi
    ): array {
        $cutoff = Carbon::now()->subDays($hariObservasi)->toDateString();

        $values = Pengiriman::where(Pengiriman::FIELD_TOKO_ID, $tokoId)
            ->where(Pengiriman::FIELD_BARANG_ID, $barangId)
            ->whereDate(Pengiriman::FIELD_TANGGAL_PENGIRIMAN, '>=', $cutoff)
            ->where(Pengiriman::FIELD_STATUS, 'terkirim')
            ->pluck(Pengiriman::FIELD_JUMLAH_KIRIM)
            ->map(fn($v) => (float) $v)
            ->toArray();

        if (empty($values)) {
            $latestKirim = Pengiriman::where(Pengiriman::FIELD_TOKO_ID, $tokoId)
                ->where(Pengiriman::FIELD_BARANG_ID, $barangId)
                ->where(Pengiriman::FIELD_STATUS, 'terkirim')
                ->max(Pengiriman::FIELD_TANGGAL_PENGIRIMAN);

            if (!$latestKirim) {
                throw new SsCalculationException(
                    "Tidak ada data retur/pengiriman untuk barang {$barangId} di toko {$tokoId}."
                );
            }

            $adaptiveCutoff = Carbon::parse($latestKirim)
                ->subDays($hariObservasi)
                ->toDateString();

            $values = Pengiriman::where(Pengiriman::FIELD_TOKO_ID, $tokoId)
                ->where(Pengiriman::FIELD_BARANG_ID, $barangId)
                ->whereDate(Pengiriman::FIELD_TANGGAL_PENGIRIMAN, '>=', $adaptiveCutoff)
                ->where(Pengiriman::FIELD_STATUS, 'terkirim')
                ->pluck(Pengiriman::FIELD_JUMLAH_KIRIM)
                ->map(fn($v) => (float) $v)
                ->toArray();

            if (empty($values)) {
                throw new SsCalculationException(
                    "Tidak ada data retur/pengiriman untuk barang {$barangId} di toko {$tokoId}."
                );
            }
        }

        $total           = array_sum($values);
        $d               = $total / $hariObservasi;
        $n               = count($values);
        $avgIntervalHari = $hariObservasi / $n;
        $sigmaD          = $n >= 2
            ? $this->standarDeviasi($values) / $avgIntervalHari
            : sqrt($d);

        Log::debug('SsService::hitungDemandDariPengiriman', [
            'toko_id'          => $tokoId,
            'barang_id'        => $barangId,
            'n_events'         => $n,
            'total'            => $total,
            'd'                => round($d, 4),
            'avg_interval_hari' => round($avgIntervalHari, 4),
            'sigma_d'          => round($sigmaD, 4),
        ]);

        return [$d, $sigmaD];
    }

    private function hitungLeadTimeStats(
        string $tokoId,
        string $barangId,
        int    $hariObservasi
    ): array {
        $cutoff    = Carbon::now()->subDays($hariObservasi)->toDateString();
        $leadTimes = $this->queryLeadTimes($tokoId, $barangId, $cutoff);

        if (empty($leadTimes)) {
            $latestKirim = Pengiriman::where(Pengiriman::FIELD_TOKO_ID, $tokoId)
                ->where(Pengiriman::FIELD_BARANG_ID, $barangId)
                ->whereNotNull(Pengiriman::FIELD_TANGGAL_TERIMA)
                ->max(Pengiriman::FIELD_TANGGAL_PENGIRIMAN);

            if ($latestKirim) {
                $adaptiveCutoff = Carbon::parse($latestKirim)
                    ->subDays($hariObservasi)
                    ->toDateString();
                $leadTimes = $this->queryLeadTimes($tokoId, $barangId, $adaptiveCutoff);
            }
        }

        if (empty($leadTimes)) {
            return [self::DEFAULT_LEAD_TIME, self::DEFAULT_SIGMA_L];
        }

        $L      = array_sum($leadTimes) / count($leadTimes);
        $sigmaL = count($leadTimes) >= 2
            ? $this->standarDeviasi($leadTimes)
            : self::DEFAULT_SIGMA_L;

        if ($L <= 0) {
            $L = self::DEFAULT_LEAD_TIME;
        }
        if ($sigmaL <= 0) {
            $sigmaL = self::DEFAULT_SIGMA_L;
        }

        return [$L, $sigmaL];
    }

    private function queryLeadTimes(
        string $tokoId,
        string $barangId,
        string $cutoff
    ): array {
        $rows = Pengiriman::where(Pengiriman::FIELD_TOKO_ID, $tokoId)
            ->where(Pengiriman::FIELD_BARANG_ID, $barangId)
            ->whereDate(Pengiriman::FIELD_TANGGAL_PENGIRIMAN, '>=', $cutoff)
            ->whereNotNull(Pengiriman::FIELD_TANGGAL_TERIMA)
            ->get([
                Pengiriman::FIELD_TANGGAL_PENGIRIMAN,
                Pengiriman::FIELD_TANGGAL_TERIMA,
            ]);

        if ($rows->isEmpty()) {
            return [];
        }

        return $rows
            ->map(fn($p) => (float) Carbon::parse($p->{Pengiriman::FIELD_TANGGAL_PENGIRIMAN})
                ->diffInDays(Carbon::parse($p->{Pengiriman::FIELD_TANGGAL_TERIMA}))
            )
            ->filter(fn($v) => $v >= 0)
            ->values()
            ->toArray();
    }

    private function standarDeviasi(array $values): float
    {
        $n = count($values);
        if ($n < 2) {
            return 0.0;
        }

        $mean = array_sum($values) / $n;
        $sum  = array_sum(array_map(fn($v) => ($v - $mean) ** 2, $values));

        return sqrt($sum / ($n - 1));
    }
}