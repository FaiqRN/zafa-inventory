<?php

namespace App\Services;

use App\Exceptions\RekomendasiCalculationException;
use App\Models\Barang;
use App\Models\InventoryRekomendasi;
use App\Models\Pengiriman;
use App\Models\Retur;
use App\Services\DashboardInventoryOptimization\EoqService;
use App\Services\DashboardInventoryOptimization\RopService;
use App\Services\DashboardInventoryOptimization\SsService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RekomendasiService
{
    // 70% dari shelf life sebagai batas aman distribusi
    const SHELF_LIFE_FACTOR = 0.7;
    const DEFAULT_HARI_OBSERVASI = 365;
    const MIN_Q_KIRIM_OPERASIONAL = 5;
    const PACK_SIZE_Q_KIRIM = 5;
    const DEFAULT_ZSCORE_SYNC_WINDOW_DAYS = 180;

    public function __construct(
        private EoqService $eoqService,
        private SsService  $ssService,
        private RopService $ropService,
        private ZscoreActivePairSyncService $zscoreActivePairSyncService,
    ) {}

    // ──────────────────────────────────────────────────────────────────────────
    // PUBLIC METHODS
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Hitung rekomendasi untuk SATU kombinasi toko + barang.
     *
     * @param  string $tokoId
     * @param  string $barangId
     * @param  int    $hariObservasi
     * @return array  Hasil lengkap EOQ, SS, ROP, Q_kirim
     * @throws RekomendasiCalculationException
     */
    public function hitung(string $tokoId, string $barangId, int $hariObservasi = self::DEFAULT_HARI_OBSERVASI): array
    {
        // ── Step 1: EOQ ─────────────────────────────────────────────────────
        $eoqHasil = $this->eoqService->hitung($tokoId, $barangId, $hariObservasi);

        // ── Step 2: SS ──────────────────────────────────────────────────────
        $ssHasil = $this->ssService->hitung($tokoId, $barangId, $hariObservasi);

        // ── Step 3: ROP ─────────────────────────────────────────────────────
        $ropHasil = $this->ropService->hitung(
            d:        $ssHasil['d'],
            leadTime: $ssHasil['L'],
            ss:       $ssHasil['ss'],
        );

        // ── Step 4: Layer 2 — Rekomendasi Kirim ────────────────────────────
        $layer2Hasil = $this->hitungLayer2(
            barangId:        $barangId,
            eoq:             $eoqHasil['eoq'],
            intervalEoqHari: (float) $eoqHasil['T'],
            avgJualHarian:   $ssHasil['d'],
        );

        // ── Step 5: Stok aktual per toko ────────────────────────────────────
        $stokAktual = $this->getStokAktual($tokoId, $barangId);

        // ── Step 6: is_below_rop ────────────────────────────────────────────
        $isBelowRop = $stokAktual <= $ropHasil['rop'] ? 1 : 0;

        // ── Step 7: Susun hasil ─────────────────────────────────────────────
        $hasil = [
            'toko_id'               => $tokoId,
            'barang_id'             => $barangId,

            // Snapshot parameter
            's_dipakai'             => $eoqHasil['s_dipakai'],
            's_dari_override'       => $eoqHasil['s_dari_override'] ? 1 : 0,
            'h_dipakai'             => $eoqHasil['h_dipakai'],
            'z_dipakai'             => $ssHasil['z_dipakai'],
            'service_level_dipakai' => $ssHasil['service_level_dipakai'],
            'hari_observasi'        => $hariObservasi,

            // Hasil EOQ
            'eoq_result'            => $eoqHasil['eoq'],
            'frekuensi_per_tahun'   => (int) round($eoqHasil['D'] / $eoqHasil['eoq']),
            'interval_eoq_hari'     => $eoqHasil['T'],

            // Hasil SS
            'ss_result'             => $ssHasil['ss'],

            // Hasil ROP
            'rop_result'            => $ropHasil['rop'],

            // Hasil Layer 2
            'shelf_life_days'       => $layer2Hasil['shelf_life_days'],
            'batas_aman_hari'       => $layer2Hasil['batas_aman_hari'],
            'shelf_life_flag'       => $layer2Hasil['shelf_life_flag'],
            'interval_kirim_hari'   => $layer2Hasil['interval_kirim_hari'],
            'avg_jual_harian'       => round($ssHasil['d'], 4),
            'q_kirim_result'        => $layer2Hasil['q_kirim'],

            // Status stok
            'stok_aktual'           => $stokAktual,
            'is_below_rop'          => $isBelowRop,

            // Data historis
            'total_kirim_historis'  => $eoqHasil['total_kirim'],
            'total_retur_historis'  => $eoqHasil['total_retur'],
            'penjualan_aktual'      => $eoqHasil['penjualan_aktual'],

            'calculated_at'         => now(),
        ];

        // ── Step 8: Simpan ──────────────────────────────────────────────────
        $this->simpan($hasil);

        return $hasil;
    }

    /**
     * Hitung rekomendasi untuk SEMUA kombinasi toko + barang yang aktif.
     *
     * @param  int $hariObservasi
     * @return array{berhasil: int, gagal: int, error: array}
     */
    public function hitungSemua(int $hariObservasi = self::DEFAULT_HARI_OBSERVASI): array
    {
        $this->sinkronkanZscorePasanganAktif();

        $berhasil = 0;
        $gagal    = 0;
        $errors   = [];

        foreach ($this->getKombinasiAktif() as $item) {
            try {
                $this->hitung($item['toko_id'], $item['barang_id'], $hariObservasi);
                $berhasil++;
            } catch (\RuntimeException $e) {
                $gagal++;
                $errors[] = [
                    'toko_id'   => $item['toko_id'],
                    'barang_id' => $item['barang_id'],
                    'pesan'     => $e->getMessage(),
                ];
                Log::warning('RekomendasiService::hitungSemua gagal', [
                    'toko_id'   => $item['toko_id'],
                    'barang_id' => $item['barang_id'],
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        return compact('berhasil', 'gagal', 'errors');
    }

    /**
     * Pastikan pasangan aktif toko-barang sudah memiliki baseline default Z-score.
     */
    private function sinkronkanZscorePasanganAktif(): void
    {
        try {
            $syncStats = $this->zscoreActivePairSyncService->syncActivePairs(
                self::DEFAULT_ZSCORE_SYNC_WINDOW_DAYS,
                true,
                'system-sync'
            );

            if (($syncStats['inserted_rows'] ?? 0) > 0) {
                Log::info('RekomendasiService::hitungSemua sinkronisasi Z-score menambah missing pair', $syncStats);
            }
        } catch (\Throwable $e) {
            Log::warning('RekomendasiService::hitungSemua gagal sinkronisasi Z-score aktif', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // PRIVATE METHODS
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Hitung Layer 2: Rekomendasi Kirim dengan constraint shelf life.
     */
    private function hitungLayer2(
        string $barangId,
        int    $eoq,
        float  $intervalEoqHari,
        float  $avgJualHarian,
    ): array {
        $barang = Barang::findOrFail($barangId);

        $shelfLifeDays = (int) $barang->{Barang::FIELD_SHELF_LIFE};

        if ($shelfLifeDays <= 0) {
            throw new RekomendasiCalculationException(
                "Shelf life untuk barang {$barangId} belum dikonfigurasi. "
                . 'Silakan isi kolom shelf_life di data barang.'
            );
        }

        $batasAman = (int) round($shelfLifeDays * self::SHELF_LIFE_FACTOR);

        if ($intervalEoqHari > $batasAman) {
            $shelfLifeFlag = 1;
            $intervalKirim = (float) $batasAman;
            $qKirimRaw     = $avgJualHarian * $batasAman;
        } else {
            $shelfLifeFlag = 0;
            $intervalKirim = $intervalEoqHari;
            $qKirimRaw     = $avgJualHarian * $intervalEoqHari;
        }

        $qKirim = $this->normalisasiQKirim($qKirimRaw, $eoq);

        return [
            'shelf_life_days'     => $shelfLifeDays,
            'batas_aman_hari'     => $batasAman,
            'shelf_life_flag'     => $shelfLifeFlag,
            'interval_kirim_hari' => round($intervalKirim, 2),
            'q_kirim'             => $qKirim,
        ];
    }

    /**
     * Normalisasi kuantitas kirim.
     */
    private function normalisasiQKirim(float $qKirimRaw, int $eoq): int
    {
        $qKirimRaw = max(0.0, $qKirimRaw);
        $eoq       = max(0, $eoq);

        if ($qKirimRaw <= 0.0 || $eoq <= 0) {
            return 0;
        }

        $qDasar = min($qKirimRaw, (float) $eoq);
        $qPack  = (int) (floor($qDasar / self::PACK_SIZE_Q_KIRIM) * self::PACK_SIZE_Q_KIRIM);

        if ($qPack < self::MIN_Q_KIRIM_OPERASIONAL) {
            return self::MIN_Q_KIRIM_OPERASIONAL;  // ← kembalikan 5
        }
        return $qPack;

    }

    /**
     * Ambil stok aktual barang di toko.
     *
     * FIX: barang_stok adalah stok gudang produksi (tidak per toko) dan
     * sisa_stok = 0 semua karena sudah terdistribusi.
     * Gunakan kalkulasi langsung: total_kirim_ke_toko - total_terjual_di_toko.
     *
     * @return int
     */
    private function getStokAktual(string $tokoId, string $barangId): int
    {
        // Total yang pernah dikirim ke toko ini
        $totalKirim = (int) Pengiriman::where(Pengiriman::FIELD_TOKO_ID, $tokoId)
            ->where(Pengiriman::FIELD_BARANG_ID, $barangId)
            ->sum(Pengiriman::FIELD_JUMLAH_KIRIM);

        // Total yang sudah terjual (dari kolom total_terjual di tabel retur)
        $totalTerjual = (int) Retur::where(Retur::FIELD_TOKO_ID, $tokoId)
            ->where(Retur::FIELD_BARANG_ID, $barangId)
            ->sum(Retur::FIELD_TOTAL_TERJUAL);

        return max(0, $totalKirim - $totalTerjual);
    }

    /**
     * Simpan hasil ke inventory_rekomendasi.
     * Selalu insert baru — tidak update — agar histori terjaga.
     */
    private function simpan(array $hasil): void
    {
        InventoryRekomendasi::create($hasil);
    }

    /**
     * Ambil semua kombinasi toko-barang yang aktif (ada pengiriman dalam 2 tahun terakhir).
     * Diperluas dari 1 tahun ke 2 tahun agar mencakup data historis yang ada.
     *
     * @return array  [['toko_id' => ..., 'barang_id' => ...], ...]
     */
    private function getKombinasiAktif(): array
    {
        $cutoff = Carbon::now()->subYears(2);

        return Pengiriman::where(Pengiriman::FIELD_TANGGAL_PENGIRIMAN, '>=', $cutoff)
            ->select([Pengiriman::FIELD_TOKO_ID, Pengiriman::FIELD_BARANG_ID])
            ->distinct()
            ->get()
            ->map(fn($item) => [
                'toko_id'   => $item->{Pengiriman::FIELD_TOKO_ID},
                'barang_id' => $item->{Pengiriman::FIELD_BARANG_ID},
            ])
            ->toArray();
    }
}