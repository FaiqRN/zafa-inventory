<?php

namespace App\Helpers\MasterData\barangToko;

use App\Models\BarangToko;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class BarangTokoOperationHelper
{
    public static function storeBarangToko(array $data): BarangToko
    {
        // Validasi input
        $validator = Validator::make(
            $data,
            BarangTokoHelper::validateBarangTokoData($data)
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        if (BarangTokoHelper::isBarangExistsForToko($data['toko_id'], $data['barang_id'])) {
            $validator->errors()->add('barang_id', 'Barang ini sudah terdaftar untuk toko yang dipilih');
            throw new ValidationException($validator);
        }

        return BarangTokoHelper::createBarangToko([
            'toko_id' => $data['toko_id'],
            'barang_id' => $data['barang_id'],
            'harga_barang_toko' => $data['harga_barang_toko'],
            'user_create' => $data['user_create'] ?? null,
        ]);
    }

    public static function updateBarangTokoData(BarangToko $barangToko, array $data): BarangToko
    {
        // Validasi input
        $validator = Validator::make(
            $data,
            BarangTokoHelper::validateBarangTokoData($data, true)
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Update data barang-toko
        return BarangTokoHelper::updateBarangToko($barangToko, [
            'harga_barang_toko' => $data['harga_barang_toko'],
            'user_update' => $data['user_update'] ?? null,
        ]);
    }

    public static function deleteBarangTokoData(BarangToko $barangToko): bool
    {
        return BarangTokoHelper::deleteBarangToko($barangToko);
    }
}
