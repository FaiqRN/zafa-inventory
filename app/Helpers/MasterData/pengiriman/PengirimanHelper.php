<?php

namespace App\Helpers\MasterData\pengiriman;

use App\Models\Pengiriman;
use App\Models\BarangToko;
use App\Models\Barang;
use App\Models\BarangStok;
use App\Models\User;
use App\Services\PengirimanCacheService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class PengirimanHelper
{
    public static function generateNomerPengiriman()
    {
        $today = date('Ymd');
        $prefix = 'PGR';
        
        $lastPengiriman = Pengiriman::where(Pengiriman::FIELD_NOMER_PENGIRIMAN, 'like', $prefix . $today . '%')
            ->orderBy(Pengiriman::FIELD_NOMER_PENGIRIMAN, 'desc')
            ->first();
        
        if (!$lastPengiriman) {
            return $prefix . $today . '001';
        }
        
        $lastNumber = intval(substr($lastPengiriman->{Pengiriman::FIELD_NOMER_PENGIRIMAN}, -3));
        $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        
        return $prefix . $today . $newNumber;
    }

    public static function generatePengirimanId()
    {
        $lastPengiriman = Pengiriman::orderBy(Pengiriman::FIELD_PENGIRIMAN_ID, 'desc')->first();
        
        if (!$lastPengiriman) {
            return 'PG0001';
        }
        
        $lastId = $lastPengiriman->{Pengiriman::FIELD_PENGIRIMAN_ID};
        $prefix = 'PG';
        
        if (!preg_match('/^PG\d+$/', $lastId)) {
            return 'PG0001';
        }
        
        $numPart = substr($lastId, strlen($prefix));
        $nextNum = intval($numPart) + 1;
        
        return $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    }

    public static function createPengiriman($data)
    {
        DB::beginTransaction();
        
        try {
            $nomerPengiriman = self::generateNomerPengiriman();
            $userId = self::resolveCurrentUserId();
            
            foreach ($data['items'] as $item) {
                $pengirimanId = self::generatePengirimanId();
                
                $barangToko = BarangToko::where(BarangToko::FIELD_TOKO_ID, $data['toko_id'])
                    ->where(BarangToko::FIELD_BARANG_ID, $item['barang_id'])
                    ->first();
                
                if (!$barangToko) {
                    throw new \Exception("Barang tidak ditemukan di toko ini");
                }
                
                Pengiriman::create([
                    Pengiriman::FIELD_PENGIRIMAN_ID => $pengirimanId,
                    Pengiriman::FIELD_NOMER_PENGIRIMAN => $nomerPengiriman,
                    Pengiriman::FIELD_TOKO_ID => $data['toko_id'],
                    Pengiriman::FIELD_BARANG_ID => $item['barang_id'],
                    Pengiriman::FIELD_TANGGAL_PENGIRIMAN => $data['tanggal_pengiriman'],
                    Pengiriman::FIELD_JUMLAH_KIRIM => $item['jumlah'],
                    Pengiriman::FIELD_STATUS => 'proses',
                    Pengiriman::FIELD_USER_CREATE => $userId,
                    Pengiriman::FIELD_USER_UPDATE => $userId,
                ]);
            }
            
            DB::commit();
            
            PengirimanCacheService::clearAllCache();
            
            return ['success' => true, 'nomer_pengiriman' => $nomerPengiriman];
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating pengiriman: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public static function updateStatus($nomerPengiriman, $newStatus)
    {
        DB::beginTransaction();
        
        try {
            $pengirimanList = Pengiriman::where(Pengiriman::FIELD_NOMER_PENGIRIMAN, $nomerPengiriman)->get();
            
            if ($pengirimanList->isEmpty()) {
                throw new \Exception("Pengiriman tidak ditemukan");
            }
            
            $oldStatus = $pengirimanList->first()->{Pengiriman::FIELD_STATUS};
            
            Log::info("Update Status Pengiriman", [
                'nomer' => $nomerPengiriman,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'total_items' => $pengirimanList->count()
            ]);
            
            foreach ($pengirimanList as $pengiriman) {
                if ($newStatus === 'terkirim' && $oldStatus === 'proses') {
                    Log::info("Mengurangi stok untuk barang_id: " . $pengiriman->{Pengiriman::FIELD_BARANG_ID});
                    self::reduceStock($pengiriman);
                } elseif ($newStatus === 'batal' && $oldStatus === 'terkirim') {
                    Log::info("Mengembalikan stok untuk barang_id: " . $pengiriman->{Pengiriman::FIELD_BARANG_ID});
                    self::restoreStock($pengiriman);
                }

                $tanggalTerima = $pengiriman->{Pengiriman::FIELD_TANGGAL_TERIMA};
                if ($newStatus === 'terkirim' && empty($tanggalTerima)) {
                    $tanggalTerima = now()->toDateString();
                }
                
                $pengiriman->update([
                    Pengiriman::FIELD_STATUS => $newStatus,
                    Pengiriman::FIELD_TANGGAL_TERIMA => $tanggalTerima,
                    Pengiriman::FIELD_USER_UPDATE => self::resolveCurrentUserId(),
                ]);
            }
            
            DB::commit();
            
            PengirimanCacheService::clearPengirimanCache($nomerPengiriman);
            
            Log::info("Status berhasil diupdate");
            return ['success' => true];
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating status: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private static function reduceStock($pengiriman)
    {
        $barang = Barang::find($pengiriman->{Pengiriman::FIELD_BARANG_ID});
        
        if (!$barang) {
            throw new \Exception("Barang tidak ditemukan");
        }
        
        $currentStok = $barang->stok; 
        $jumlahKirim = $pengiriman->{Pengiriman::FIELD_JUMLAH_KIRIM};
        
        Log::info("Reduce Stock Detail", [
            'barang_id' => $barang->{Barang::FIELD_BARANG_ID},
            'nama_barang' => $barang->{Barang::FIELD_NAMA_BARANG},
            'current_stok' => $currentStok,
            'jumlah_kirim' => $jumlahKirim,
            'new_stok' => $currentStok - $jumlahKirim
        ]);
        
        if ($currentStok < $jumlahKirim) {
            throw new \Exception("Stok tidak mencukupi untuk barang: " . $barang->{Barang::FIELD_NAMA_BARANG});
        }

        self::reduceStockFIFO($pengiriman->{Pengiriman::FIELD_BARANG_ID}, $jumlahKirim);
        
        Log::info("Stok berhasil dikurangi", [
            'barang_id' => $barang->{Barang::FIELD_BARANG_ID},
            'new_stok' => $barang->fresh()->stok
        ]);
    }

    private static function restoreStock($pengiriman)
    {
        $barang = Barang::find($pengiriman->{Pengiriman::FIELD_BARANG_ID});
        
        if (!$barang) {
            throw new \Exception("Barang tidak ditemukan");
        }
        
        $currentStok = $barang->stok; 
        $jumlahKirim = $pengiriman->{Pengiriman::FIELD_JUMLAH_KIRIM};
        
        Log::info("Restore Stock Detail", [
            'barang_id' => $barang->{Barang::FIELD_BARANG_ID},
            'nama_barang' => $barang->{Barang::FIELD_NAMA_BARANG},
            'current_stok' => $currentStok,
            'jumlah_kirim' => $jumlahKirim,
            'new_stok' => $currentStok + $jumlahKirim
        ]);

        self::restoreStockFIFO($pengiriman->{Pengiriman::FIELD_BARANG_ID}, $jumlahKirim);
        
        Log::info("Stok berhasil dikembalikan", [
            'barang_id' => $barang->{Barang::FIELD_BARANG_ID},
            'new_stok' => $barang->fresh()->stok
        ]);
    }

    private static function reduceStockFIFO($barangId, $jumlah)
    {
        $remaining = $jumlah;
        
        $batches = BarangStok::where(BarangStok::FIELD_BARANG_ID, $barangId)
            ->where(BarangStok::FIELD_SISA_STOK, '>', 0)
            ->orderBy(BarangStok::FIELD_TANGGAL_STOCK_BARANG, 'asc')
            ->orderBy(BarangStok::FIELD_ID, 'asc')
            ->get();
        
        Log::info("FIFO Reduce Stock", [
            'barang_id' => $barangId,
            'jumlah_kirim' => $jumlah,
            'total_batches' => $batches->count()
        ]);
        
        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }
            
            $sisaStok = $batch->{BarangStok::FIELD_SISA_STOK} ?? 0;
            $deduct = min($remaining, $sisaStok);
            
            $batch->update([
                BarangStok::FIELD_SISA_STOK => $sisaStok - $deduct,
                BarangStok::FIELD_USER_UPDATE => self::resolveCurrentUserId(),
            ]);
            
            $remaining -= $deduct;
            
            Log::info("Batch dikurangi", [
                'batch_id' => $batch->{BarangStok::FIELD_ID},
                'deduct' => $deduct,
                'sisa_batch' => $sisaStok - $deduct,
                'remaining' => $remaining
            ]);
        }
        
        if ($remaining > 0) {
            Log::warning("FIFO: Tidak cukup batch untuk mengurangi stok", [
                'barang_id' => $barangId,
                'remaining' => $remaining
            ]);
        }
    }

    private static function restoreStockFIFO($barangId, $jumlah)
    {
        $batch = BarangStok::where(BarangStok::FIELD_BARANG_ID, $barangId)
            ->orderBy(BarangStok::FIELD_TANGGAL_STOCK_BARANG, 'desc')
            ->orderBy(BarangStok::FIELD_ID, 'desc')
            ->first();
        
        if ($batch) {
            $sisaStok = $batch->{BarangStok::FIELD_SISA_STOK} ?? 0;
            
            $batch->update([
                BarangStok::FIELD_SISA_STOK => $sisaStok + $jumlah,
                BarangStok::FIELD_USER_UPDATE => self::resolveCurrentUserId(),
            ]);
            
            Log::info("FIFO Restore - Batch dikembalikan", [
                'batch_id' => $batch->{BarangStok::FIELD_ID},
                'restore' => $jumlah,
                'new_sisa' => $sisaStok + $jumlah
            ]);
        }
    }

    private static function resolveCurrentUserId(): string
    {
        static $resolved = false;
        static $resolvedUserId = 'SYSTEM';

        if ($resolved) {
            return $resolvedUserId;
        }

        $resolved = true;
        $authIdentifier = Auth::id();

        if ($authIdentifier === null) {
            return $resolvedUserId;
        }

        $userId = User::query()
            ->where(User::FIELD_USERNAME, (string) $authIdentifier)
            ->value(User::FIELD_USER_ID);

        if ($userId !== null) {
            $resolvedUserId = (string) $userId;
        }

        return $resolvedUserId;
    }

    public static function getPengirimanByNomer($nomerPengiriman)
    {
        $pengirimanList = Pengiriman::where(Pengiriman::FIELD_NOMER_PENGIRIMAN, $nomerPengiriman)
            ->with(['toko', 'barang'])
            ->get();
        
        if ($pengirimanList->isEmpty()) {
            return null;
        }
        
        $first = $pengirimanList->first();
        $tokoId = $first->{Pengiriman::FIELD_TOKO_ID};
        
        $barangIds = $pengirimanList->pluck(Pengiriman::FIELD_BARANG_ID)->unique();
        $barangTokoMap = BarangToko::where(BarangToko::FIELD_TOKO_ID, $tokoId)
            ->whereIn(BarangToko::FIELD_BARANG_ID, $barangIds)
            ->get()
            ->keyBy(BarangToko::FIELD_BARANG_ID);
        
        return [
            'nomer_pengiriman' => $first->{Pengiriman::FIELD_NOMER_PENGIRIMAN},
            'tanggal_pengiriman' => $first->{Pengiriman::FIELD_TANGGAL_PENGIRIMAN},
            'toko' => $first->toko,
            'status' => $first->{Pengiriman::FIELD_STATUS},
            'items' => $pengirimanList->map(function($p) use ($barangTokoMap) {
                $barangId = $p->{Pengiriman::FIELD_BARANG_ID};
                $barangToko = $barangTokoMap->get($barangId);
                
                return [
                    'barang' => $p->barang,
                    'jumlah' => $p->{Pengiriman::FIELD_JUMLAH_KIRIM},
                    'satuan' => $p->barang->{Barang::FIELD_SATUAN},
                    'harga' => $barangToko ? $barangToko->{BarangToko::FIELD_HARGA_BARANG_TOKO} : 0,
                ];
            })
        ];
    }

    public static function getTotalJumlah($nomerPengiriman)
    {
        return Pengiriman::where(Pengiriman::FIELD_NOMER_PENGIRIMAN, $nomerPengiriman)
            ->sum(Pengiriman::FIELD_JUMLAH_KIRIM);
    }

    public static function getBarangByToko($tokoId)
    {
        $barangToko = BarangToko::where(BarangToko::FIELD_TOKO_ID, $tokoId)
            ->with('barang')
            ->get();
        
        return $barangToko->map(function($bt) {
            return [
                'barang_id' => $bt->{BarangToko::FIELD_BARANG_ID},
                'nama_barang' => $bt->barang->{Barang::FIELD_NAMA_BARANG},
                'satuan' => $bt->barang->{Barang::FIELD_SATUAN},
                'harga' => $bt->{BarangToko::FIELD_HARGA_BARANG_TOKO},
                'stok' => $bt->barang->stok, 
            ];
        });
    }
}
