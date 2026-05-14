<?php

namespace App\Services\DashboardInventoryOptimization;

use App\Exceptions\EoqCalculationException;
use App\Models\EoqBiayaPesanGlobal;
use App\Models\EoqBiayaPesanToko;
use App\Models\EoqBiayaSimpan;
use App\Models\Pengiriman;
use App\Models\Retur;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class EoqService
{
    public const DEFAULT_HARI_OBSERVASI = 365;

    /**
     * Hitung EOQ untuk satu kombinasi toko + barang.
     *
     * @param  string $tokoId
     * @param  string $barangId
     * @param  int    $hariObservasi  Periode historis yang digunakan (default 365 hari)
     * @return array{
     *     eoq: int,
     *     q: int,
     *     batch_produksi_optimal: int,
     *     N: float,
     *     frekuensi_pemesanan_tahunan: float,
     *     T: float,
     *     interval_pemesanan_hari: float,
     *     D: int,
     *     s_dipakai: float,
     *     h_dipakai: float,
     *     s_dari_override: bool,
     *     total_kirim: int,
     *     total_retur: int,
     *     penjualan_aktual: int,
     * }
     * @throws \InvalidArgumentException  jika hariObservasi tidak valid
     * @throws EoqCalculationException    jika parameter S, H, atau D belum memenuhi syarat
     */
    public function hitung(string $tokoId, string $barangId, int $hariObservasi = self::DEFAULT_HARI_OBSERVASI): array
    {
        if ($hariObservasi <= 0) {
            throw new \InvalidArgumentException('Hari observasi harus lebih besar dari 0.');
        }

        // ── 1. Ambil S (dengan logika override per toko) ────────────────────
        [$s, $sDariOverride] = $this->resolveS($tokoId);

        if ($s <= 0) {
            throw new EoqCalculationException(
                "Biaya pemesanan (S) untuk toko {$tokoId} belum dikonfigurasi atau bernilai 0."
            );
        }

        // ── 2. Ambil H (sebelum query berat — guard division by zero) ───────
        $h = $this->resolveH($barangId);

        if ($h <= 0) {
            throw new EoqCalculationException(
                "Biaya penyimpanan (H) untuk barang {$barangId} belum dikonfigurasi atau bernilai 0."
            );
        }

        // ── 3. Hitung D dari data historis ──────────────────────────────────
        [$annualDemand, $totalKirim, $totalRetur, $penjualanAktual] =
            $this->hitungD($tokoId, $barangId, $hariObservasi);

        if ($annualDemand <= 0) {
            throw new EoqCalculationException(
                "Tidak ada data penjualan untuk barang {$barangId} di toko {$tokoId} "
                . "dalam {$hariObservasi} hari terakhir."
            );
        }

        // ── 4. Hitung EOQ — Q* = sqrt(2DS/H) ───────────────────────────────
        $q = (int) max(1, round(sqrt((2 * $annualDemand * $s) / $h)));

        // ── 5. Hitung N = D / Q* ────────────────────────────────────────────
        $orderFrequencyPerYear = round($annualDemand / $q, 2);

        // ── 6. Hitung T = (Q* / D) × 365 ───────────────────────────────────
        $orderIntervalDays = round(($q / $annualDemand) * 365, 2);

        return [
            'eoq'                         => $q,
            'q'                           => $q,
            'batch_produksi_optimal'      => $q,
            'N'                           => $orderFrequencyPerYear,
            'frekuensi_pemesanan_tahunan' => $orderFrequencyPerYear,
            'T'                           => $orderIntervalDays,
            'interval_pemesanan_hari'     => $orderIntervalDays,
            'D'                           => $annualDemand,
            's_dipakai'                   => $s,
            'h_dipakai'                   => $h,
            's_dari_override'             => $sDariOverride,
            'total_kirim'                 => $totalKirim,
            'total_retur'                 => $totalRetur,
            'penjualan_aktual'            => $penjualanAktual,
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // PRIVATE METHODS
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Resolusi nilai S: global + override per toko.
     *
     * @return array{0: float, 1: bool}  [total_S, ada_override]
     */
    private function resolveS(string $tokoId): array
    {
        $globalKomponen = EoqBiayaPesanGlobal::all();

        if ($globalKomponen->isEmpty()) {
            throw new EoqCalculationException(
                'Biaya pemesanan global (S) belum dikonfigurasi. '
                . 'Silakan isi di menu Setting EOQ terlebih dahulu.'
            );
        }

        $overrideToko = EoqBiayaPesanToko::where(
            EoqBiayaPesanToko::FIELD_TOKO_ID, $tokoId
        )->get()->keyBy(EoqBiayaPesanToko::FIELD_NAMA_BIAYA);

        $totalS      = 0;
        $adaOverride = false;

        foreach ($globalKomponen as $komponen) {
            $namaBiaya = $komponen->{EoqBiayaPesanGlobal::FIELD_NAMA_BIAYA};

            if ($overrideToko->has($namaBiaya)) {
                $totalS     += (float) $overrideToko[$namaBiaya]->{EoqBiayaPesanToko::FIELD_NOMINAL};
                $adaOverride = true;
            } else {
                $totalS += (float) $komponen->{EoqBiayaPesanGlobal::FIELD_NOMINAL};
            }
        }

        return [$totalS, $adaOverride];
    }

    /**
     * Resolusi nilai H untuk satu barang.
     * H = SUM(harga_pokok_i × persentase_i / 100)
     *
     * @return float
     */
    private function resolveH(string $barangId): float
    {
        $komponen = EoqBiayaSimpan::where(
            EoqBiayaSimpan::FIELD_BARANG_ID, $barangId
        )->get();

        if ($komponen->isEmpty()) {
            throw new EoqCalculationException(
                "Biaya penyimpanan (H) untuk barang {$barangId} belum dikonfigurasi. "
                . 'Silakan isi di menu Setting EOQ terlebih dahulu.'
            );
        }

        $hargaPokokUnik = $komponen
            ->pluck(EoqBiayaSimpan::FIELD_HARGA_POKOK)
            ->map(fn($v) => (float) $v)
            ->unique();

        if ($hargaPokokUnik->count() > 1) {
            throw new EoqCalculationException(
                "Data biaya simpan tidak konsisten untuk barang {$barangId}: harga_pokok berbeda antar komponen."
            );
        }

        return (float) $komponen->sum(function ($row) {
            return (float) $row->{EoqBiayaSimpan::FIELD_HARGA_POKOK}
                * (float) $row->{EoqBiayaSimpan::FIELD_PERSENTASE}
                / 100;
        });
    }

    /**
     * Hitung demand tahunan D dari data historis dengan adaptive window fallback.
     *
     * Logika:
     *   1. Coba ambil data dalam window $hariObservasi terakhir dari sekarang.
     *   2. Jika tidak ada data (karena data historis lebih lama dari window),
     *      cari tanggal retur terbaru yang tersedia, lalu gunakan window
     *      $hariObservasi mundur dari tanggal tersebut.
     *   3. Fallback ke pengiriman jika retur masih kosong.
     *
     * @return array{0: int, 1: int, 2: int, 3: int}
     *         [annualDemand, totalKirim, totalRetur, penjualanAktual]
     */
    private function hitungD(string $tokoId, string $barangId, int $hariObservasi): array
    {
        $cutoffDate = Carbon::now()->subDays($hariObservasi)->toDateString();

        [$totalKirim, $totalRetur, $penjualanAktual] =
            $this->queryPenjualan($tokoId, $barangId, $cutoffDate);

        // ── Adaptive fallback: data ada tapi di luar window normal ──────────
        if ($penjualanAktual <= 0 && $totalKirim <= 0) {
            $latestReturDate = $this->getLatestReturDate($tokoId, $barangId);

            if ($latestReturDate !== null) {
                $adaptiveCutoff = $latestReturDate->copy()
                    ->subDays($hariObservasi)
                    ->toDateString();

                [$totalKirim, $totalRetur, $penjualanAktual] =
                    $this->queryPenjualan($tokoId, $barangId, $adaptiveCutoff);

                Log::info('EoqService::hitungD menggunakan adaptive window (data historis di luar window normal)', [
                    'toko_id'         => $tokoId,
                    'barang_id'       => $barangId,
                    'window_normal'   => $cutoffDate,
                    'latest_retur'    => $latestReturDate->toDateString(),
                    'adaptive_cutoff' => $adaptiveCutoff,
                    'penjualan'       => $penjualanAktual,
                ]);
            }
        }

        // ── Fallback: toko baru yang belum punya data retur ─────────────────
        // ASUMSI: Untuk toko baru yang belum pernah melakukan retur
        // (total_terjual = 0), semua unit yang dikirim diasumsikan terjual.
        // Ini berpotensi overestimate demand — dicatat sebagai limitasi penelitian.
        // Relevan untuk toko yang baru bergabung dalam sistem konsinyasi
        // dan belum memiliki histori pengembalian produk.
        if ($penjualanAktual <= 0 && $totalKirim > 0) {
            $penjualanAktual = $totalKirim;
            Log::info('EoqService::hitungD fallback: toko baru, penjualan diasumsikan = total_kirim', [
                'toko_id'   => $tokoId,
                'barang_id' => $barangId,
                'total_kirim' => $totalKirim,
            ]);
        }

        $penjualanAktual = max(0, $penjualanAktual);

        // Annualize ke 365 hari
        $annualDemand = (int) round(($penjualanAktual / $hariObservasi) * 365);

        return [$annualDemand, $totalKirim, $totalRetur, $penjualanAktual];
    }

    /**
     * Query data penjualan dari tabel retur dan pengiriman mulai dari $cutoffDate.
     *
     * @return array{0: int, 1: int, 2: int} [totalKirim, totalRetur, penjualanAktual]
     */
    private function queryPenjualan(string $tokoId, string $barangId, string $cutoffDate): array
    {
        $totalKirim = (int) Pengiriman::where(Pengiriman::FIELD_TOKO_ID, $tokoId)
            ->where(Pengiriman::FIELD_BARANG_ID, $barangId)
            ->whereDate(Pengiriman::FIELD_TANGGAL_PENGIRIMAN, '>=', $cutoffDate)
            ->sum(Pengiriman::FIELD_JUMLAH_KIRIM);

        $totalRetur = (int) Retur::where(Retur::FIELD_TOKO_ID, $tokoId)
            ->where(Retur::FIELD_BARANG_ID, $barangId)
            ->whereDate(Retur::FIELD_TANGGAL_RETUR, '>=', $cutoffDate)
            ->sum(Retur::FIELD_JUMLAH_RETUR);

        $penjualanAktual = (int) Retur::where(Retur::FIELD_TOKO_ID, $tokoId)
            ->where(Retur::FIELD_BARANG_ID, $barangId)
            ->whereDate(Retur::FIELD_TANGGAL_RETUR, '>=', $cutoffDate)
            ->sum(Retur::FIELD_TOTAL_TERJUAL);

        return [$totalKirim, $totalRetur, $penjualanAktual];
    }

    /**
     * Ambil tanggal retur terbaru untuk kombinasi toko + barang.
     * Digunakan untuk adaptive window fallback.
     *
     * @return Carbon|null
     */
    private function getLatestReturDate(string $tokoId, string $barangId): ?Carbon
    {
        $latest = Retur::where(Retur::FIELD_TOKO_ID, $tokoId)
            ->where(Retur::FIELD_BARANG_ID, $barangId)
            ->latest(Retur::FIELD_TANGGAL_RETUR)
            ->value(Retur::FIELD_TANGGAL_RETUR);

        return $latest ? Carbon::parse($latest) : null;
    }
}