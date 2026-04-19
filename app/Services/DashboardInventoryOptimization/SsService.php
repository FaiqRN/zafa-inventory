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

    private const DEFAULT_SERVICE_LEVEL = 95.00;
    private const DEFAULT_Z_SCORE       = 1.6449;

    // Default lead time operasional Zafa (3–7 hari kerja, tengah = 5)
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
        // PERBAIKAN: gunakan default eksplisit jika tidak ada data tanggal_terima
        [$leadTime, $sigmaL] = $this->hitungLeadTimeStats($tokoId, $barangId, $hariObservasi);

        // Pastikan L dan σL tidak pernah 0 agar SS tidak collapse
        // Jika L=0 maka komponen L×σd² = 0 dan d²×σL² = 0 → SS = 0 → wrong
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

    private function resolveZ(string $tokoId, string $barangId): array
    {
        $setting = Zscore::where(Zscore::FIELD_TOKO_ID, $tokoId)
            ->where(Zscore::FIELD_BARANG_ID, $barangId)
            ->orderByDesc(Zscore::FIELD_SERVICE_LEVEL)
            ->first();

        if (!$setting) {
            Log::warning('SsService: fallback Z-score default', [
                'toko_id'   => $tokoId,
                'barang_id' => $barangId,
            ]);
            return [self::DEFAULT_Z_SCORE, self::DEFAULT_SERVICE_LEVEL];
        }

        return [
            (float) $setting->{Zscore::FIELD_Z_SCORE},
            (float) $setting->{Zscore::FIELD_SERVICE_LEVEL},
        ];
    }

    /**
     * Hitung d dan σd dari data retur.
     *
     * σd diambil dari variasi total_terjual per EVENT pengiriman
     * (bukan per hari) — lebih representatif untuk pola UMKM periodik.
     * Untuk n < 2 event: gunakan Poisson approx (σ ≈ √mean).
     */
    private function hitungDemandStats(
        string $tokoId,
        string $barangId,
        int    $hariObservasi
    ): array {
        // Coba window normal dari sekarang
        $cutoff = Carbon::now()->subDays($hariObservasi)->toDateString();
        $result = $this->queryReturEvents($tokoId, $barangId, $cutoff, $hariObservasi);

        if ($result !== null) {
            return $result;
        }

        // Adaptive fallback: mundur dari tanggal retur terbaru
        $latestTanggal = Retur::where(Retur::FIELD_TOKO_ID, $tokoId)
            ->where(Retur::FIELD_BARANG_ID, $barangId)
            ->max(Retur::FIELD_TANGGAL_RETUR);

        if ($latestTanggal) {
            $adaptiveCutoff = Carbon::parse($latestTanggal)
                ->subDays($hariObservasi)
                ->toDateString();

            Log::info('SsService: adaptive window demand', [
                'toko_id'        => $tokoId,
                'barang_id'      => $barangId,
                'adaptive_cutoff'=> $adaptiveCutoff,
            ]);

            $result = $this->queryReturEvents($tokoId, $barangId, $adaptiveCutoff, $hariObservasi);
            if ($result !== null) {
                return $result;
            }
        }

        return $this->hitungDemandDariPengiriman($tokoId, $barangId, $hariObservasi);
    }

    /**
     * Query event retur dan hitung [d, sigma_d].
     * Mengambil nilai per EVENT (bukan groupBy tanggal).
     */
    private function queryReturEvents(
        string $tokoId,
        string $barangId,
        string $cutoff,
        int    $hariObservasi
    ): ?array {
        $values = Retur::where(Retur::FIELD_TOKO_ID, $tokoId)
            ->where(Retur::FIELD_BARANG_ID, $barangId)
            ->whereDate(Retur::FIELD_TANGGAL_RETUR, '>=', $cutoff)
            ->where(Retur::FIELD_TOTAL_TERJUAL, '>', 0)
            ->pluck(Retur::FIELD_TOTAL_TERJUAL)
            ->map(fn($v) => (float) $v)
            ->toArray();

        if (empty($values)) {
            return null;
        }

        $total = array_sum($values);
        if ($total <= 0) {
            return null;
        }

        $d      = $total / $hariObservasi;
        $n      = count($values);
        $sigmaD = $n >= 2
            ? $this->standarDeviasi($values)
            : sqrt($total / $n); // Poisson approximation

        Log::debug('SsService::queryReturEvents', [
            'toko_id'    => $tokoId,
            'barang_id'  => $barangId,
            'n_events'   => $n,
            'total'      => $total,
            'd'          => round($d, 4),
            'sigma_d'    => round($sigmaD, 4),
        ]);

        return [$d, $sigmaD];
    }

    /**
     * Fallback demand dari pengiriman (sebelum ada data retur).
     */
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
            // Adaptive dari pengiriman terakhir
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

        $total  = array_sum($values);
        $d      = $total / $hariObservasi;
        $n      = count($values);
        $sigmaD = $n >= 2 ? $this->standarDeviasi($values) : sqrt($total / $n);

        return [$d, $sigmaD];
    }

    /**
     * Hitung L dan σL dari selisih tanggal_pengiriman → tanggal_terima.
     *
     * PERBAIKAN: Jika tidak ada data tanggal_terima sama sekali,
     * kembalikan default [5.0, 1.0] secara eksplisit.
     * Sebelumnya fallback tidak berjalan karena queryLeadTimes()
     * mengembalikan [] dan kondisi empty tidak ter-trigger dengan benar.
     */
    private function hitungLeadTimeStats(
        string $tokoId,
        string $barangId,
        int    $hariObservasi
    ): array {
        $cutoff    = Carbon::now()->subDays($hariObservasi)->toDateString();
        $leadTimes = $this->queryLeadTimes($tokoId, $barangId, $cutoff);

        if (empty($leadTimes)) {
            // Adaptive: cari dari pengiriman terakhir yang punya tanggal_terima
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

        // Jika masih kosong (tidak ada tanggal_terima di DB) → pakai default
        if (empty($leadTimes)) {
            return [self::DEFAULT_LEAD_TIME, self::DEFAULT_SIGMA_L];
        }

        $L      = array_sum($leadTimes) / count($leadTimes);
        $sigmaL = count($leadTimes) >= 2
            ? $this->standarDeviasi($leadTimes)
            : self::DEFAULT_SIGMA_L;

        // Pastikan tidak return 0
        if ($L <= 0) {
            $L = self::DEFAULT_LEAD_TIME;
        }
        if ($sigmaL <= 0) {
            $sigmaL = self::DEFAULT_SIGMA_L;
        }

        return [$L, $sigmaL];
    }

    /**
     * Query lead time (selisih hari tanggal_kirim → tanggal_terima).
     *
     * @return float[]
     */
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

    /**
     * Standar deviasi sampel (n-1).
     *
     * @param  float[] $values
     * @return float
     */
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