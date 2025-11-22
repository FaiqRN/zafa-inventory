<?php

namespace App\Helpers\MasterData\barang;

use App\Models\Barang;
use App\Models\BarangStok;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BarangStokHelper
{
    /**
     * Tambah stok baru dengan sistem FIFO
     *
     * @param string $barangId
     * @param int $jumlah
     * @param string $tanggal
     * @param string|null $catatan
     * @return array
     */
    public static function tambahStok($barangId, $jumlah, $tanggal, $catatan = null)
    {
        try {
            // Validasi barang exists
            $barang = Barang::find($barangId);
            if (!$barang) {
                return [
                    'success' => false,
                    'message' => 'Barang tidak ditemukan',
                    'data' => null
                ];
            }

            // Validasi jumlah > 0
            if ($jumlah <= 0) {
                return [
                    'success' => false,
                    'message' => 'Jumlah stok harus lebih dari 0',
                    'data' => null
                ];
            }

            // Validasi tanggal tidak boleh future date
            if (strtotime($tanggal) > strtotime(date('Y-m-d'))) {
                return [
                    'success' => false,
                    'message' => 'Tanggal stok tidak boleh melebihi tanggal hari ini',
                    'data' => null
                ];
            }

            DB::beginTransaction();

            // Buat record baru di barang_stok
            $barangStok = BarangStok::create([
                BarangStok::FIELD_BARANG_ID => $barangId,
                BarangStok::FIELD_TANGGAL_STOCK_BARANG => $tanggal,
                BarangStok::FIELD_STOK => $jumlah,
                BarangStok::FIELD_SISA_STOK => $jumlah,
                BarangStok::FIELD_STOK_AWAL => $jumlah,
                BarangStok::FIELD_CATATAN => $catatan,
                BarangStok::FIELD_USER_CREATE => auth()->user()->username ?? 'system',
            ]);

            // Update total stok di tabel barang (increment)
            $barang->increment(Barang::FIELD_STOK, $jumlah);

            DB::commit();

            return [
                'success' => true,
                'message' => "Berhasil menambah stok sebanyak {$jumlah} unit",
                'data' => $barangStok
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error tambah stok: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat menambah stok: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Kurangi stok dengan sistem FIFO
     *
     * @param string $barangId
     * @param int $jumlah
     * @return array
     */
    public static function kurangiStok($barangId, $jumlah)
    {
        try {
            // Validasi stok tersedia cukup
            $validasi = self::validateStokCukup($barangId, $jumlah);
            if (!$validasi['is_available']) {
                return [
                    'success' => false,
                    'message' => "Stok tidak mencukupi. Tersedia: {$validasi['available_stock']} unit, Diminta: {$validasi['requested']} unit",
                    'data' => $validasi
                ];
            }

            DB::beginTransaction();

            // Ambil batch available dengan FIFO order
            $batches = BarangStok::byBarang($barangId)
                ->available()
                ->fifo()
                ->get();

            $sisaKurang = $jumlah;
            $batchDikurangi = [];

            foreach ($batches as $batch) {
                if ($sisaKurang <= 0) break;

                $kurangDariBatch = min($batch->{BarangStok::FIELD_SISA_STOK}, $sisaKurang);
                $batch->{BarangStok::FIELD_SISA_STOK} -= $kurangDariBatch;
                $batch->{BarangStok::FIELD_USER_UPDATE} = auth()->user()->username ?? 'system';
                $batch->save();

                $sisaKurang -= $kurangDariBatch;
                
                $batchDikurangi[] = [
                    'batch_id' => $batch->{BarangStok::FIELD_ID},
                    'tanggal_batch' => $batch->{BarangStok::FIELD_TANGGAL_STOCK_BARANG},
                    'jumlah_dikurangi' => $kurangDariBatch,
                    'sisa_batch' => $batch->{BarangStok::FIELD_SISA_STOK},
                ];
            }

            // Update total stok di tabel barang (decrement)
            $barang = Barang::find($barangId);
            $barang->decrement(Barang::FIELD_STOK, $jumlah);

            DB::commit();

            // Log detail pengurangan
            Log::info("Stok dikurangi untuk barang {$barangId}", [
                'jumlah_total' => $jumlah,
                'batch_dikurangi' => $batchDikurangi
            ]);

            return [
                'success' => true,
                'message' => "Berhasil mengurangi stok sebanyak {$jumlah} unit",
                'data' => [
                    'jumlah_dikurangi' => $jumlah,
                    'batch_details' => $batchDikurangi
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error kurangi stok: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengurangi stok: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Get total stok tersedia (sum sisa_stok)
     *
     * @param string $barangId
     * @return int
     */
    public static function getStokTersedia($barangId)
    {
        return BarangStok::byBarang($barangId)
            ->sum(BarangStok::FIELD_SISA_STOK);
    }

    /**
     * Get detail semua batch untuk barang
     *
     * @param string $barangId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getDetailBatch($barangId)
    {
        return BarangStok::byBarang($barangId)
            ->fifo()
            ->get()
            ->map(function ($batch) {
                return [
                    'id' => $batch->{BarangStok::FIELD_ID},
                    'tanggal' => $batch->{BarangStok::FIELD_TANGGAL_STOCK_BARANG}->format('d/m/Y'),
                    'tanggal_raw' => $batch->{BarangStok::FIELD_TANGGAL_STOCK_BARANG},
                    'stok_awal' => $batch->{BarangStok::FIELD_STOK_AWAL},
                    'sisa_stok' => $batch->{BarangStok::FIELD_SISA_STOK},
                    'terpakai' => $batch->{BarangStok::FIELD_STOK_AWAL} - $batch->{BarangStok::FIELD_SISA_STOK},
                    'catatan' => $batch->{BarangStok::FIELD_CATATAN},
                    'status' => $batch->{BarangStok::FIELD_SISA_STOK} > 0 ? 'Tersedia' : 'Habis',
                    'status_class' => $batch->{BarangStok::FIELD_SISA_STOK} > 0 ? 'success' : 'secondary',
                ];
            });
    }

    /**
     * Validasi apakah stok cukup
     *
     * @param string $barangId
     * @param int $jumlah
     * @return array
     */
    public static function validateStokCukup($barangId, $jumlah)
    {
        $availableStock = self::getStokTersedia($barangId);
        
        return [
            'is_available' => $availableStock >= $jumlah,
            'available_stock' => $availableStock,
            'requested' => $jumlah,
            'shortage' => max(0, $jumlah - $availableStock)
        ];
    }

    /**
     * Get batch terlama yang masih ada sisa
     *
     * @param string $barangId
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getBatchTerlama($barangId, $limit = 5)
    {
        return BarangStok::byBarang($barangId)
            ->available()
            ->fifo()
            ->limit($limit)
            ->get();
    }

    /**
     * Get summary stok untuk barang
     *
     * @param string $barangId
     * @return array
     */
    public static function getStokSummary($barangId)
    {
        $batches = BarangStok::byBarang($barangId)->get();
        
        $totalBatch = $batches->count();
        $totalStokAwal = $batches->sum(BarangStok::FIELD_STOK_AWAL);
        $totalSisaStok = $batches->sum(BarangStok::FIELD_SISA_STOK);
        $totalTerpakai = $totalStokAwal - $totalSisaStok;
        
        $batchTertua = BarangStok::byBarang($barangId)
            ->available()
            ->fifo()
            ->first();
            
        $batchTerbaru = BarangStok::byBarang($barangId)
            ->orderBy(BarangStok::FIELD_TANGGAL_STOCK_BARANG, 'desc')
            ->orderBy(BarangStok::FIELD_ID, 'desc')
            ->first();

        return [
            'total_batch' => $totalBatch,
            'total_stok_awal' => $totalStokAwal,
            'total_sisa_stok' => $totalSisaStok,
            'total_terpakai' => $totalTerpakai,
            'batch_tertua' => $batchTertua ? $batchTertua->{BarangStok::FIELD_TANGGAL_STOCK_BARANG}->format('d/m/Y') : '-',
            'batch_terbaru' => $batchTerbaru ? $batchTerbaru->{BarangStok::FIELD_TANGGAL_STOCK_BARANG}->format('d/m/Y') : '-',
        ];
    }
}
