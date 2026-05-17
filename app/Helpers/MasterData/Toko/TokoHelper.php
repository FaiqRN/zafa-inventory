<?php

namespace App\Helpers\MasterData\Toko;

use App\Models\Toko;

class TokoHelper
{

    public static function generateKode(): string
    {
        $lastToko = Toko::orderBy(Toko::FIELD_TOKO_ID, 'desc')->first();

        if (!$lastToko) {
            return 'TKO001';
        }

        $lastId = $lastToko->{Toko::FIELD_TOKO_ID};
        $prefix = 'TKO';

        if (preg_match('/^TKO(\d+)$/', $lastId, $matches)) {
            $number = intval($matches[1]);
            $nextNumber = $number + 1;
            return $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        }

        return 'TKO001';
    }

    public static function getQualityBadge(?string $quality): array
    {
        $badges = [
            'excellent' => ['text' => 'Sangat Akurat', 'class' => 'success'],
            'good' => ['text' => 'Akurat', 'class' => 'primary'],
            'fair' => ['text' => 'Cukup Akurat', 'class' => 'warning'],
            'poor' => ['text' => 'Kurang Akurat', 'class' => 'danger'],
            'very poor' => ['text' => 'Tidak Akurat', 'class' => 'danger'],
            'failed' => ['text' => 'Gagal', 'class' => 'secondary']
        ];

        return $badges[$quality] ?? $badges['failed'];
    }

    public static function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }

    public static function formatDistance(float $meters): string
    {
        if ($meters < 1000) {
            return round($meters) . ' meter';
        }
        return round($meters / 1000, 2) . ' km';
    }

    public static function buildFullAddress(array $data): string
    {
        $parts = array_filter([
            $data[Toko::FIELD_ALAMAT] ?? '',
            $data[Toko::FIELD_WILAYAH_KELURAHAN] ?? '',
            $data[Toko::FIELD_WILAYAH_KECAMATAN] ?? '',
            $data[Toko::FIELD_WILAYAH_KOTA_KABUPATEN] ?? '',
            'Jawa Timur',
            'Indonesia'
        ]);

        return implode(', ', $parts);
    }

    public static function getStoreValidationRules(): array
    {
        return [
            Toko::FIELD_TOKO_ID => 'required|string|max:10|unique:' . Toko::TABLE,
            Toko::FIELD_NAMA_TOKO => 'required|string|max:100',
            Toko::FIELD_PEMILIK => 'required|string|max:100',
            Toko::FIELD_ALAMAT => 'required|string',
            Toko::FIELD_WILAYAH_KOTA_KABUPATEN => 'required|string|max:100',
            Toko::FIELD_WILAYAH_KECAMATAN => 'required|string|max:100',
            Toko::FIELD_WILAYAH_KELURAHAN => 'required|string|max:100',
            Toko::FIELD_NOMER_TELPON => 'required|string|max:20',
            Toko::FIELD_LATITUDE => 'required|numeric|between:-90,90',
            Toko::FIELD_LONGITUDE => 'required|numeric|between:-180,180',
        ];
    }

    public static function getUpdateValidationRules(): array
    {
        return [
            Toko::FIELD_NAMA_TOKO => 'required|string|max:100',
            Toko::FIELD_PEMILIK => 'required|string|max:100',
            Toko::FIELD_ALAMAT => 'required|string',
            Toko::FIELD_WILAYAH_KOTA_KABUPATEN => 'required|string|max:100',
            Toko::FIELD_WILAYAH_KECAMATAN => 'required|string|max:100',
            Toko::FIELD_WILAYAH_KELURAHAN => 'required|string|max:100',
            Toko::FIELD_NOMER_TELPON => 'required|string|max:20',
            Toko::FIELD_LATITUDE => 'required|numeric|between:-90,90',
            Toko::FIELD_LONGITUDE => 'required|numeric|between:-180,180',
        ];
    }

    public static function getValidationMessages(): array
    {
        return [
            Toko::FIELD_LATITUDE . '.required' => 'Koordinat latitude wajib diisi. Silakan pilih lokasi pada peta.',
            Toko::FIELD_LONGITUDE . '.required' => 'Koordinat longitude wajib diisi. Silakan pilih lokasi pada peta.',
            Toko::FIELD_LATITUDE . '.between' => 'Koordinat latitude tidak valid.',
            Toko::FIELD_LONGITUDE . '.between' => 'Koordinat longitude tidak valid.',
        ];
    }

    public static function getCoordinateValidationRules(): array
    {
        return [
            Toko::FIELD_LATITUDE => 'required|numeric|between:-90,90',
            Toko::FIELD_LONGITUDE => 'required|numeric|between:-180,180',
        ];
    }

    public static function formatTokoForList(Toko $toko): array
    {
        return [
            Toko::FIELD_TOKO_ID => $toko->{Toko::FIELD_TOKO_ID},
            Toko::FIELD_NAMA_TOKO => $toko->{Toko::FIELD_NAMA_TOKO},
            Toko::FIELD_PEMILIK => $toko->{Toko::FIELD_PEMILIK},
            Toko::FIELD_ALAMAT => $toko->{Toko::FIELD_ALAMAT},
            Toko::FIELD_WILAYAH_KECAMATAN => $toko->{Toko::FIELD_WILAYAH_KECAMATAN},
            Toko::FIELD_WILAYAH_KELURAHAN => $toko->{Toko::FIELD_WILAYAH_KELURAHAN},
            Toko::FIELD_WILAYAH_KOTA_KABUPATEN => $toko->{Toko::FIELD_WILAYAH_KOTA_KABUPATEN},
            Toko::FIELD_NOMER_TELPON => $toko->{Toko::FIELD_NOMER_TELPON},
            Toko::FIELD_LATITUDE => $toko->{Toko::FIELD_LATITUDE},
            Toko::FIELD_LONGITUDE => $toko->{Toko::FIELD_LONGITUDE},
            Toko::FIELD_IS_ACTIVE => $toko->{Toko::FIELD_IS_ACTIVE},
            Toko::FIELD_GEOCODING_PROVIDER => $toko->{Toko::FIELD_GEOCODING_PROVIDER},
            Toko::FIELD_GEOCODING_QUALITY => $toko->{Toko::FIELD_GEOCODING_QUALITY},
            Toko::FIELD_GEOCODING_SCORE => $toko->{Toko::FIELD_GEOCODING_SCORE},
            Toko::FIELD_GEOCODING_CONFIDENCE => $toko->{Toko::FIELD_GEOCODING_CONFIDENCE},
        ];
    }
}
