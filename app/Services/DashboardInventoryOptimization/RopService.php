<?php

namespace App\Services\DashboardInventoryOptimization;

class RopService
{
    /**
     * Hitung Reorder Point.
     *
     * RopService hanya menerima nilai yang sudah dihitung oleh SsService
     * dan menerapkan rumus ROP. Tidak ada query database di sini.
     *
     * Rumus: ROP = (d × L) + SS
     *
     * @param  float $d        Rata-rata demand harian (unit/hari) — dari SsService
     * @param  float $leadTime Rata-rata lead time (hari) — dari SsService
     * @param  int   $ss       Safety Stock (unit) — dari SsService
     * @return array{
     *     rop: int,
     *     titik_stok_kirim_ulang: int,
     * }
     */
    public function hitung(float $d, float $leadTime, int $ss): array
    {
        $d        = max(0.0, $d);
        $leadTime = max(0.0, $leadTime);
        $ss       = max(0, $ss);

        // Rumus: ROP = (d × L) + SS
        $ropRaw = ($d * $leadTime) + $ss;
        $rop = (int) ceil($ropRaw);

        if ($d > 0 && $rop <= 0) {
            $rop = 1;
        }

        $rop = max(0, $rop);

        return [
            'rop'                    => $rop,
            'titik_stok_kirim_ulang' => $rop,
        ];
    }
}
