<?php

namespace App\Services;

use App\Exceptions\RekomendasiCalculationException;
use App\Models\Barang;
use App\Models\BarangToko;
use App\Models\InventoryRekomendasi;
use App\Models\KonfigurasiSistem;
use App\Models\Pengiriman;
use App\Models\Retur;
use App\Services\DashboardInventoryOptimization\EoqService;
use App\Services\DashboardInventoryOptimization\RopService;
use App\Services\DashboardInventoryOptimization\SsService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RekomendasiService
{
    const SHELF_LIFE_FACTOR               = 0.7;
    const DEFAULT_HARI_OBSERVASI          = 365;
    const MIN_Q_KIRIM_OPERASIONAL         = 5;
    const PACK_SIZE_Q_KIRIM               = 5;
    const DEFAULT_ZSCORE_SYNC_WINDOW_DAYS = 180;
    // MIN_INTERVAL_KIRIM_HARI tidak lagi hardcoded — nilai dibaca dari konfigurasi_sistem
    // via KonfigurasiSistem::get(). Fallback default ada di KonfigurasiSistem::DEFAULT_MIN_INTERVAL_KIRIM_HARI.

    public function __construct(
        private EoqService $eoqService,
        private SsService  $ssService,
        private RopService $ropService,
        private ZscoreActivePairSyncService $zscoreActivePairSyncService,
    ) {}

    // ──────────────────────────────────────────────────────────────────────────
    // PUBLIC METHODS
    // ──────────────────────────────────────────────────────────────────────────

    public function hitung(string $tokoId, string $barangId, int $hariObservasi = self::DEFAULT_HARI_OBSERVASI): array
    {
        // ── Resolve barang_toko_id dari toko_id + barang_id ──────────────────
        // Dibutuhkan sebagai PK untuk upsert ke inventory_rekomendasi
        $barangTokoId = $this->resolveBarangTokoId($tokoId, $barangId);

        // ── Step 1: EOQ ──────────────────────────────────────────────────────
        $eoqHasil = $this->eoqService->hitung($tokoId, $barangId, $hariObservasi);

        // ── Step 2: SS ───────────────────────────────────────────────────────
        $ssHasil = $this->ssService->hitung($tokoId, $barangId, $hariObservasi);

        // ── Step 3: ROP ──────────────────────────────────────────────────────
        $ropHasil = $this->ropService->hitung(
            d:        $ssHasil['d'],
            leadTime: $ssHasil['L'],
            ss:       $ssHasil['ss'],
        );

        // ── Step 4: Layer 2 — Rekomendasi Kirim ─────────────────────────────
        $layer2Hasil = $this->hitungLayer2(
            barangId:        $barangId,
            tokoId:          $tokoId,
            eoq:             $eoqHasil['eoq'],
            intervalEoqHari: (float) $eoqHasil['T'],
            avgJualHarian:   $ssHasil['d'],
        );

        // ── Step 5: Stok aktual ──────────────────────────────────────────────
        $stokAktual = $this->getStokAktual($tokoId, $barangId);

        // ── Step 6: is_below_rop ─────────────────────────────────────────────
        $isBelowRop = $stokAktual < $ropHasil['rop'] ? 1 : 0;

        // ── Step 7: Susun hasil ──────────────────────────────────────────────
        $hasil = [
            'barang_toko_id'        => $barangTokoId,
            'toko_id'               => $tokoId,
            'barang_id'             => $barangId,
            's_dipakai'             => $eoqHasil['s_dipakai'],
            's_dari_override'       => $eoqHasil['s_dari_override'] ? 1 : 0,
            'h_dipakai'             => $eoqHasil['h_dipakai'],
            'z_dipakai'             => $ssHasil['z_dipakai'],
            'service_level_dipakai' => $ssHasil['service_level_dipakai'],
            'hari_observasi'        => $hariObservasi,
            'eoq_result'            => $eoqHasil['eoq'],
            'frekuensi_per_tahun'   => (int) round($eoqHasil['D'] / $eoqHasil['eoq']),
            'interval_eoq_hari'     => $eoqHasil['T'],
            'ss_result'             => $ssHasil['ss'],
            'rop_result'            => $ropHasil['rop'],
            'shelf_life_days'       => $layer2Hasil['shelf_life_days'],
            'batas_aman_hari'       => $layer2Hasil['batas_aman_hari'],
            'shelf_life_flag'       => $layer2Hasil['shelf_life_flag'],
            'interval_kirim_hari'   => $layer2Hasil['interval_kirim_hari'],
            'avg_jual_harian'       => round($ssHasil['d'], 4),
            'q_kirim_result'        => $layer2Hasil['q_kirim'],
            'stok_aktual'           => $stokAktual,
            'is_below_rop'          => $isBelowRop,
            'total_kirim_historis'  => $eoqHasil['total_kirim'],
            'total_retur_historis'  => $eoqHasil['total_retur'],
            'penjualan_aktual'      => $eoqHasil['penjualan_aktual'],
            'calculated_at'         => now(),
        ];

        // ── Step 8: UPSERT — update jika sudah ada, insert jika belum ────────
        $this->simpan($hasil);

        return $hasil;
    }

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
     * Truncate seluruh tabel inventory_rekomendasi lalu generate ulang dari awal.
     *
     * Digunakan oleh auto-refresh tiap 5 menit agar:
     *   1. Tidak ada baris stale untuk kombinasi yang sudah tidak aktif
     *   2. Stok aktual, is_below_rop, shelf_life_flag selalu fresh
     *
     * CATATAN: Truncate + insert lebih bersih dari UPSERT untuk skenario ini
     * karena kombinasi aktif bisa berubah (toko baru, barang baru, toko nonaktif).
     * UPSERT tidak menghapus baris lama yang kombinasinya sudah tidak relevan.
     *
     * Proses ini berjalan di background — caller tidak perlu menunggu selesai
     * (dipanggil via endpoint auto-refresh, response dikirim setelah truncate+generate
     * selesai, lalu JS langsung fetch data terbaru).
     *
     * @return array{berhasil: int, gagal: int, errors: array, truncated: int}
     */
    public function truncateAndRegenerate(int $hariObservasi = self::DEFAULT_HARI_OBSERVASI): array
    {
        // Catat jumlah baris sebelum dihapus untuk logging
        $truncated = InventoryRekomendasi::count();

        Log::info('RekomendasiService::truncateAndRegenerate dimulai', [
            'baris_dihapus' => $truncated,
            'hari_observasi' => $hariObservasi,
        ]);

        // Hapus semua data lama sekaligus — lebih efisien dari DELETE row-per-row
        InventoryRekomendasi::truncate();

        // Generate ulang semua kombinasi aktif
        $hasil = $this->hitungSemua($hariObservasi);

        Log::info('RekomendasiService::truncateAndRegenerate selesai', [
            'truncated' => $truncated,
            'berhasil'  => $hasil['berhasil'],
            'gagal'     => $hasil['gagal'],
        ]);

        return array_merge($hasil, ['truncated' => $truncated]);
    }

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
     * Resolve barang_toko_id dari kombinasi toko_id + barang_id.
     *
     * Digunakan sebagai PK untuk upsert ke inventory_rekomendasi.
     * Jika kombinasi tidak ditemukan di barang_toko, lempar exception
     * karena data tidak valid untuk dikalkulasi.
     *
     * @throws RekomendasiCalculationException
     */
    private function resolveBarangTokoId(string $tokoId, string $barangId): string
    {
        $barangToko = BarangToko::where('toko_id', $tokoId)
            ->where('barang_id', $barangId)
            ->value('barang_toko_id');

        if (!$barangToko) {
            throw new RekomendasiCalculationException(
                "Kombinasi toko {$tokoId} dan barang {$barangId} tidak ditemukan di tabel barang_toko. "
                . 'Pastikan barang sudah terdaftar di toko ini.'
            );
        }

        return $barangToko;
    }

    /**
     * UPSERT ke inventory_rekomendasi.
     *
     * Karena PK = barang_toko_id (bukan autoincrement), kita bisa pakai
     * updateOrCreate() dengan barang_toko_id sebagai key pencarian.
     *
     * Ini menggantikan create() lama yang selalu INSERT baris baru
     * dan menyebabkan duplikat per kombinasi toko+barang.
     */
    private function simpan(array $hasil): void
    {
        InventoryRekomendasi::updateOrCreate(
            // ── Key: identifikasi baris yang sudah ada ───────────────────────
            [
                InventoryRekomendasi::FIELD_BARANG_TOKO_ID => $hasil['barang_toko_id'],
            ],
            // ── Values: semua kolom yang di-update / di-insert ───────────────
            $hasil
        );
    }

    private function hitungLayer2(
        string $barangId,
        string $tokoId,
        int    $eoq,
        float  $intervalEoqHari,
        float  $avgJualHarian,
    ): array {
        $barang        = Barang::findOrFail($barangId);
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
        } else {
            $shelfLifeFlag = 0;
            $intervalKirim = $intervalEoqHari;
        }

        $minInterval = $this->resolveMinIntervalKirim($tokoId);
        if ($minInterval > 0 && $intervalKirim < $minInterval) {
            $intervalKirim = (float) min($minInterval, $batasAman);
            Log::debug('RekomendasiService::hitungLayer2 min_interval applied', [
                'toko_id'        => $tokoId,
                'barang_id'      => $barangId,
                'min_interval'   => $minInterval,
                'interval_akhir' => $intervalKirim,
            ]);
        }

        $qKirimRaw = $avgJualHarian * $intervalKirim;
        $qKirim    = $this->normalisasiQKirim($qKirimRaw, $eoq);

        return [
            'shelf_life_days'     => $shelfLifeDays,
            'batas_aman_hari'     => $batasAman,
            'shelf_life_flag'     => $shelfLifeFlag,
            'interval_kirim_hari' => round($intervalKirim, 2),
            'q_kirim'             => $qKirim,
        ];
    }

    private function resolveMinIntervalKirim(string $tokoId): int
    {
        // Prioritas 1: override per-toko dari kolom min_interval_kirim_hari di tabel toko
        $tokoOverride = (int) (\App\Models\Toko::where(\App\Models\Toko::FIELD_TOKO_ID, $tokoId)
            ->value('min_interval_kirim_hari') ?? 0);

        if ($tokoOverride > 0) {
            return $tokoOverride;
        }

        // Prioritas 2: nilai global dari konfigurasi_sistem (dapat diubah via admin)
        return (int) KonfigurasiSistem::get(
            KonfigurasiSistem::KEY_MIN_INTERVAL_KIRIM_HARI,
            KonfigurasiSistem::DEFAULT_MIN_INTERVAL_KIRIM_HARI  // fallback 14
        );
    }

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
            return self::MIN_Q_KIRIM_OPERASIONAL;
        }
        return $qPack;
    }

    /**
     * Hitung stok aktual di toko mitra untuk sistem konsinyasi.
     *
     * PENDEKATAN KONSINYASI:
     * Kepemilikan barang tetap di UMKM sampai terjual. Stok aktual
     * adalah semua yang sudah dikirim (status terkirim) dikurangi
     * yang sudah terjual dan dikembalikan melalui proses retur.
     *
     * Pengiriman yang belum ada retumya (masih aktif di toko) =
     * seluruh jumlah kirim dianggap masih berada di toko.
     *
     * Tidak pakai window filter tanggal karena interval kirim
     * bisa 40-140 hari — window pendek akan memotong pengiriman
     * aktif yang masih berada di toko.
     *
     * Rumus: stok = total_kirim(terkirim) - total_terjual - total_retur
     */
    private function getStokAktual(string $tokoId, string $barangId): int
    {
        // Hanya hitung pengiriman yang sudah berstatus terkirim
        $totalKirim = (int) Pengiriman::where(Pengiriman::FIELD_TOKO_ID, $tokoId)
            ->where(Pengiriman::FIELD_BARANG_ID, $barangId)
            ->where(Pengiriman::FIELD_STATUS, 'terkirim')
            ->sum(Pengiriman::FIELD_JUMLAH_KIRIM);

        // Yang sudah terjual (dari retur yang sudah diisi)
        $totalTerjual = (int) Retur::where(Retur::FIELD_TOKO_ID, $tokoId)
            ->where(Retur::FIELD_BARANG_ID, $barangId)
            ->sum(Retur::FIELD_TOTAL_TERJUAL);

        // Yang dikembalikan ke UMKM
        $totalRetur = (int) Retur::where(Retur::FIELD_TOKO_ID, $tokoId)
            ->where(Retur::FIELD_BARANG_ID, $barangId)
            ->sum(Retur::FIELD_JUMLAH_RETUR);

        $stokAktual = max(0, $totalKirim - $totalTerjual - $totalRetur);

        Log::debug('RekomendasiService::getStokAktual', [
            'toko_id'       => $tokoId,
            'barang_id'     => $barangId,
            'total_kirim'   => $totalKirim,
            'total_terjual' => $totalTerjual,
            'total_retur'   => $totalRetur,
            'stok_aktual'   => $stokAktual,
        ]);

        return $stokAktual;
    }

    /**
     * Ambil kombinasi toko+barang aktif dari pengiriman 1 tahun terakhir.
     *
     * Hanya kombinasi yang terdaftar di barang_toko yang diproses,
     * karena barang_toko_id dibutuhkan sebagai PK upsert.
     */
    private function getKombinasiAktif(): array
    {
        $cutoff = Carbon::now()->subYears(1);

        // Ambil kombinasi dari pengiriman, lalu filter yang punya barang_toko_id
        return Pengiriman::where(Pengiriman::FIELD_TANGGAL_PENGIRIMAN, '>=', $cutoff)
            ->select([Pengiriman::FIELD_TOKO_ID, Pengiriman::FIELD_BARANG_ID])
            ->distinct()
            ->get()
            ->filter(function ($item) {
                // Pastikan kombinasi terdaftar di barang_toko
                return BarangToko::where('toko_id', $item->{Pengiriman::FIELD_TOKO_ID})
                    ->where('barang_id', $item->{Pengiriman::FIELD_BARANG_ID})
                    ->exists();
            })
            ->map(fn($item) => [
                'toko_id'   => $item->{Pengiriman::FIELD_TOKO_ID},
                'barang_id' => $item->{Pengiriman::FIELD_BARANG_ID},
            ])
            ->values()
            ->toArray();
    }
}