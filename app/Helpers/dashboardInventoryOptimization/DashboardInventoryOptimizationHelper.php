<?php

namespace App\Helpers\dashboardInventoryOptimization;

use App\Models\Barang;
use App\Models\InventoryRekomendasi;
use App\Models\Toko;
use App\Services\RekomendasiService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DashboardInventoryOptimizationHelper
{
    public const NOMINATIM_BASE_URL = 'https://nominatim.openstreetmap.org';

    public const DEFAULT_HARI_OBSERVASI = 365;

    /**
     * Data lengkap untuk render halaman pertama kali (page load).
     * Jika DB kosong, trigger generate sekali.
     */
    public static function getDashboardData(): array
    {
        return [
            'rekomendasiData'  => self::getRekomendasiData(),
            'tokosGeo'         => self::getTokosGeo(),
            'nominatimBaseUrl' => self::NOMINATIM_BASE_URL,
        ];
    }

    /**
     * Hanya READ data terbaru — TANPA kalkulasi ulang.
     * Digunakan oleh endpoint auto-refresh (dipanggil tiap 5 menit dari JS).
     */
    public static function getLatestDataOnly(): array
    {
        return [
            'rekomendasiData'  => self::loadLatestRekomendasiData(),
            'tokosGeo'         => self::getTokosGeo(),
            'nominatimBaseUrl' => self::NOMINATIM_BASE_URL,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────

    private static function getRekomendasiData(): array
    {
        if (!Schema::hasTable(InventoryRekomendasi::TABLE)) {
            Log::warning('Tabel inventory_rekomendasi belum tersedia.');
            return [];
        }

        $rekomendasiData = self::loadLatestRekomendasiData();

        if (!empty($rekomendasiData)) {
            return $rekomendasiData;
        }

        Log::info('DashboardInventoryOptimizationHelper: Tidak ada data rekomendasi, memulai auto-generate.');
        self::generateRekomendasiData();

        return self::loadLatestRekomendasiData();
    }

    private static function generateRekomendasiData(): void
    {
        try {
            /** @var RekomendasiService $rekomendasiService */
            $rekomendasiService = app(RekomendasiService::class);

            $hasil = $rekomendasiService->hitungSemua(self::DEFAULT_HARI_OBSERVASI);

            Log::info('DashboardInventoryOptimizationHelper: auto-generate selesai.', [
                'berhasil' => $hasil['berhasil'] ?? 0,
                'gagal'    => $hasil['gagal'] ?? 0,
            ]);

            if (($hasil['gagal'] ?? 0) > 0) {
                Log::warning('Sebagian kalkulasi rekomendasi gagal saat auto-generate dashboard.', [
                    'berhasil' => $hasil['berhasil'] ?? 0,
                    'gagal'    => $hasil['gagal'] ?? 0,
                    'errors'   => $hasil['errors'] ?? [],
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Gagal melakukan auto-generate rekomendasi dashboard.', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Load data rekomendasi terbaru dari DB.
     *
     * SIMPLIFIKASI dari versi lama:
     *   Sebelum : subquery MAX(id) GROUP BY toko_id+barang_id
     *             → diperlukan karena ada banyak baris duplikat per kombinasi
     *   Sekarang: langsung SELECT semua baris
     *             → tidak ada duplikat karena PK = barang_toko_id (1 baris per kombinasi)
     *
     * Query jauh lebih ringan dan mudah dibaca.
     */
    private static function loadLatestRekomendasiData(): array
    {
        if (!Schema::hasTable(InventoryRekomendasi::TABLE)) {
            return [];
        }

        return InventoryRekomendasi::query()
            ->from(InventoryRekomendasi::TABLE . ' as ir')
            ->join(Barang::TABLE . ' as b', 'ir.' . InventoryRekomendasi::FIELD_BARANG_ID, '=', 'b.' . Barang::FIELD_BARANG_ID)
            ->join(Toko::TABLE . ' as t', 'ir.' . InventoryRekomendasi::FIELD_TOKO_ID, '=', 't.' . Toko::FIELD_TOKO_ID)
            ->where('t.' . Toko::FIELD_IS_ACTIVE, true)
            ->select([
                'ir.' . InventoryRekomendasi::FIELD_BARANG_TOKO_ID . ' as barang_toko_id',
                'ir.' . InventoryRekomendasi::FIELD_TOKO_ID . ' as toko_id',
                'ir.' . InventoryRekomendasi::FIELD_BARANG_ID . ' as barang_id',
                'b.' . Barang::FIELD_NAMA_BARANG . ' as barang_nama',
                'ir.' . InventoryRekomendasi::FIELD_EOQ_RESULT . ' as eoq_result',
                'ir.' . InventoryRekomendasi::FIELD_SS_RESULT . ' as ss_result',
                'ir.' . InventoryRekomendasi::FIELD_ROP_RESULT . ' as rop_result',
                'ir.' . InventoryRekomendasi::FIELD_Q_KIRIM_RESULT . ' as q_kirim_result',
                'ir.' . InventoryRekomendasi::FIELD_INTERVAL_KIRIM_HARI . ' as interval_kirim_hari',
                'ir.' . InventoryRekomendasi::FIELD_STOK_AKTUAL . ' as stok_aktual',
                'ir.' . InventoryRekomendasi::FIELD_IS_BELOW_ROP . ' as is_below_rop',
                'ir.' . InventoryRekomendasi::FIELD_SHELF_LIFE_FLAG . ' as shelf_life_flag',
            ])
            ->orderBy('ir.' . InventoryRekomendasi::FIELD_TOKO_ID)
            ->orderBy('b.' . Barang::FIELD_NAMA_BARANG)
            ->get()
            ->map(function ($item): array {
                $intervalKirim = round((float) $item->interval_kirim_hari, 2);

                return [
                    'barang_toko_id'      => (string) $item->barang_toko_id,
                    'toko_id'             => (string) $item->toko_id,
                    'barang_id'           => (string) $item->barang_id,
                    'barang_nama'         => (string) $item->barang_nama,
                    'eoq_result'          => (int) $item->eoq_result,
                    'ss_result'           => (int) $item->ss_result,
                    'rop_result'          => (int) $item->rop_result,
                    'q_kirim_result'      => (int) $item->q_kirim_result,
                    'interval_kirim'      => $intervalKirim,
                    'interval_kirim_hari' => $intervalKirim,
                    'stok_aktual'         => (int) $item->stok_aktual,
                    'is_below_rop'        => (bool) $item->is_below_rop,
                    'shelf_life_flag'     => (bool) $item->shelf_life_flag,
                ];
            })
            ->values()
            ->toArray();
    }

    private static function getTokosGeo(): array
    {
        $tokos = Toko::query()
            ->select([
                Toko::FIELD_TOKO_ID,
                Toko::FIELD_NAMA_TOKO,
                Toko::FIELD_ALAMAT,
                Toko::FIELD_WILAYAH_KECAMATAN,
                Toko::FIELD_WILAYAH_KOTA_KABUPATEN,
                Toko::FIELD_LATITUDE,
                Toko::FIELD_LONGITUDE,
            ])
            ->where(Toko::FIELD_IS_ACTIVE, true)
            ->whereNotNull(Toko::FIELD_LATITUDE)
            ->whereNotNull(Toko::FIELD_LONGITUDE)
            ->orderBy(Toko::FIELD_NAMA_TOKO)
            ->get();

        return $tokos->map(function (Toko $toko): array {
            return [
                'toko_id' => $toko->{Toko::FIELD_TOKO_ID},
                'nama'    => $toko->{Toko::FIELD_NAMA_TOKO},
                'lokasi'  => self::buildLokasiLabel($toko),
                'lat'     => (float) $toko->{Toko::FIELD_LATITUDE},
                'lng'     => (float) $toko->{Toko::FIELD_LONGITUDE},
            ];
        })->values()->toArray();
    }

    private static function buildLokasiLabel(Toko $toko): string
    {
        $parts = array_filter([
            $toko->{Toko::FIELD_ALAMAT},

            
        ]);

        return !empty($parts) ? implode(', ', $parts) : '-';
    }
}