<?php

namespace App\Helpers\MasterData\barang;

use App\Models\Barang;
use App\Models\BarangStok;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class BarangStokHelper
{

    public static function tambahStok($barangId, $jumlah, $tanggal, $catatan = null)
    {
        try {
            $barang = Barang::find($barangId);
            if (!$barang) {
                return [
                    'success' => false,
                    'message' => 'Barang tidak ditemukan',
                    'data' => null
                ];
            }

            if ($jumlah <= 0) {
                return [
                    'success' => false,
                    'message' => 'Jumlah stok harus lebih dari 0',
                    'data' => null
                ];
            }

            if (strtotime($tanggal) > strtotime(date('Y-m-d'))) {
                return [
                    'success' => false,
                    'message' => 'Tanggal stok tidak boleh melebihi tanggal hari ini',
                    'data' => null
                ];
            }

            DB::beginTransaction();

            $barangStok = BarangStok::create([
                BarangStok::FIELD_BARANG_ID => $barangId,
                BarangStok::FIELD_TANGGAL_STOCK_BARANG => $tanggal,
                BarangStok::FIELD_STOK => $jumlah,
                BarangStok::FIELD_SISA_STOK => $jumlah,
                BarangStok::FIELD_STOK_AWAL => $jumlah,
                BarangStok::FIELD_CATATAN => $catatan,
                BarangStok::FIELD_USER_CREATE => self::resolveCurrentUsername(),
            ]);

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

    public static function kurangiStok($barangId, $jumlah)
    {
        try {
            $validasi = self::validateStokCukup($barangId, $jumlah);
            if (!$validasi['is_available']) {
                return [
                    'success' => false,
                    'message' => "Stok tidak mencukupi. Tersedia: {$validasi['available_stock']} unit, Diminta: {$validasi['requested']} unit",
                    'data' => $validasi
                ];
            }

            DB::beginTransaction();

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
                $batch->{BarangStok::FIELD_USER_UPDATE} = self::resolveCurrentUsername();
                $batch->save();

                $sisaKurang -= $kurangDariBatch;
                
                $batchDikurangi[] = [
                    'batch_id' => $batch->{BarangStok::FIELD_ID},
                    'tanggal_batch' => $batch->{BarangStok::FIELD_TANGGAL_STOCK_BARANG},
                    'jumlah_dikurangi' => $kurangDariBatch,
                    'sisa_batch' => $batch->{BarangStok::FIELD_SISA_STOK},
                ];
            }

            DB::commit();

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

    public static function getStokTersedia($barangId)
    {
        return BarangStok::byBarang($barangId)
            ->sum(BarangStok::FIELD_SISA_STOK);
    }

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

    public static function getBatchTerlama($barangId, $limit = 5)
    {
        return BarangStok::byBarang($barangId)
            ->available()
            ->fifo()
            ->limit($limit)
            ->get();
    }

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

    private static function resolveCurrentUsername(): string
    {
        static $resolved = false;
        static $resolvedUsername = 'system';

        if ($resolved) {
            return $resolvedUsername;
        }

        $resolved = true;
        $authIdentifier = Auth::id();

        if ($authIdentifier === null) {
            return $resolvedUsername;
        }

        $username = User::query()
            ->where(User::FIELD_USERNAME, (string) $authIdentifier)
            ->value(User::FIELD_USERNAME);

        if ($username !== null) {
            $resolvedUsername = (string) $username;
        }

        return $resolvedUsername;
    }
}
