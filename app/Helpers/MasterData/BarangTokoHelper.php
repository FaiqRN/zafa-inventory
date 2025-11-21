<?php

namespace App\Helpers\MasterData;

use App\Models\Barang;
use App\Models\BarangToko;
use App\Models\Toko;
use Illuminate\Support\Collection;

class BarangTokoHelper
{
    /**
     * Generate unique barang_toko_id
     * Format: BT001, BT002, etc.
     *
     * @return string
     */
    public static function generateBarangTokoId(): string
    {
        $lastBarangToko = BarangToko::orderBy(BarangToko::FIELD_BARANG_TOKO_ID, 'desc')->first();
        
        if (!$lastBarangToko) {
            return 'BT001';
        }
        
        $lastId = $lastBarangToko->{BarangToko::FIELD_BARANG_TOKO_ID};
        $prefix = 'BT';
        
        // Extract numeric part
        if (preg_match('/^BT(\d+)$/', $lastId, $matches)) {
            $number = intval($matches[1]);
            $nextNumber = $number + 1;
            return $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        }
        
        return 'BT001';
    }

    /**
     * Get all barang that are available for a specific toko
     * (barang that are not yet assigned to the toko)
     *
     * @param string $tokoId
     * @return Collection
     */
    public static function getAvailableBarangForToko(string $tokoId): Collection
    {
        return Barang::whereNotIn(Barang::FIELD_BARANG_ID, function($query) use ($tokoId) {
                $query->select(BarangToko::FIELD_BARANG_ID)
                      ->from(BarangToko::TABLE)
                      ->where(BarangToko::FIELD_TOKO_ID, $tokoId);
            })
            ->where(Barang::FIELD_IS_DELETED, 0)
            ->orderBy(Barang::FIELD_NAMA_BARANG, 'asc')
            ->get();
    }

    /**
     * Get all barang-toko for a specific toko with barang details
     *
     * @param string $tokoId
     * @return Collection
     */
    public static function getBarangTokoByToko(string $tokoId): Collection
    {
        return BarangToko::with('barang')
            ->where(BarangToko::FIELD_TOKO_ID, $tokoId)
            ->get();
    }

    /**
     * Get barang-toko by ID with relations
     *
     * @param string $barangTokoId
     * @return BarangToko|null
     */
    public static function getBarangTokoById(string $barangTokoId): ?BarangToko
    {
        return BarangToko::with(['barang', 'toko'])
            ->find($barangTokoId);
    }

    /**
     * Check if barang already exists for a specific toko
     *
     * @param string $tokoId
     * @param string $barangId
     * @return bool
     */
    public static function isBarangExistsForToko(string $tokoId, string $barangId): bool
    {
        return BarangToko::where(BarangToko::FIELD_TOKO_ID, $tokoId)
            ->where(BarangToko::FIELD_BARANG_ID, $barangId)
            ->exists();
    }

    /**
     * Create new barang-toko relation
     *
     * @param array $data
     * @return BarangToko
     */
    public static function createBarangToko(array $data): BarangToko
    {
        $barangTokoId = self::generateBarangTokoId();
        
        $barangToko = new BarangToko();
        $barangToko->{BarangToko::FIELD_BARANG_TOKO_ID} = $barangTokoId;
        $barangToko->{BarangToko::FIELD_TOKO_ID} = $data['toko_id'];
        $barangToko->{BarangToko::FIELD_BARANG_ID} = $data['barang_id'];
        $barangToko->{BarangToko::FIELD_HARGA_BARANG_TOKO} = $data['harga_barang_toko'];
        
        if (isset($data['user_create'])) {
            $barangToko->{BarangToko::FIELD_USER_CREATE} = $data['user_create'];
        }
        
        $barangToko->save();
        
        return $barangToko->fresh(['barang', 'toko']);
    }

    /**
     * Update barang-toko data
     *
     * @param BarangToko $barangToko
     * @param array $data
     * @return BarangToko
     */
    public static function updateBarangToko(BarangToko $barangToko, array $data): BarangToko
    {
        $barangToko->{BarangToko::FIELD_HARGA_BARANG_TOKO} = $data['harga_barang_toko'];
        
        if (isset($data['user_update'])) {
            $barangToko->{BarangToko::FIELD_USER_UPDATE} = $data['user_update'];
        }
        
        $barangToko->save();
        
        return $barangToko->fresh(['barang', 'toko']);
    }

    /**
     * Delete barang-toko relation
     *
     * @param BarangToko $barangToko
     * @return bool
     */
    public static function deleteBarangToko(BarangToko $barangToko): bool
    {
        return $barangToko->delete();
    }

    /**
     * Get all toko ordered by name
     *
     * @return Collection
     */
    public static function getAllTokoOrdered(): Collection
    {
        return Toko::orderBy(Toko::FIELD_NAMA_TOKO, 'asc')->get();
    }

    /**
     * Get barang-toko statistics for a toko
     *
     * @param string $tokoId
     * @return array
     */
    public static function getBarangTokoStatistics(string $tokoId): array
    {
        $totalBarang = BarangToko::where(BarangToko::FIELD_TOKO_ID, $tokoId)->count();
        $averagePrice = BarangToko::where(BarangToko::FIELD_TOKO_ID, $tokoId)
            ->avg(BarangToko::FIELD_HARGA_BARANG_TOKO);
        $minPrice = BarangToko::where(BarangToko::FIELD_TOKO_ID, $tokoId)
            ->min(BarangToko::FIELD_HARGA_BARANG_TOKO);
        $maxPrice = BarangToko::where(BarangToko::FIELD_TOKO_ID, $tokoId)
            ->max(BarangToko::FIELD_HARGA_BARANG_TOKO);

        return [
            'total_barang' => $totalBarang,
            'average_price' => $averagePrice ?? 0,
            'min_price' => $minPrice ?? 0,
            'max_price' => $maxPrice ?? 0,
        ];
    }

    /**
     * Validate barang-toko data
     *
     * @param array $data
     * @param bool $isUpdate
     * @return array
     */
    public static function validateBarangTokoData(array $data, bool $isUpdate = false): array
    {
        $rules = [
            'harga_barang_toko' => 'required|numeric|min:0',
        ];

        if (!$isUpdate) {
            $rules['toko_id'] = 'required|string|exists:' . Toko::TABLE . ',' . Toko::FIELD_TOKO_ID;
            $rules['barang_id'] = 'required|string|exists:' . Barang::TABLE . ',' . Barang::FIELD_BARANG_ID;
        }

        return $rules;
    }
}
