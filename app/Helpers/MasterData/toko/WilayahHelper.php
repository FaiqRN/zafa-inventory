<?php

namespace App\Helpers\MasterData\Toko;

use Illuminate\Support\Facades\File;

class WilayahHelper
{
    /**
     * Path to wilayah JSON file
     */
    private const WILAYAH_FILE = 'data/wilayah_malang.json';

    /**
     * Get all wilayah data (Kota/Kabupaten)
     *
     * @return array
     */
    public static function getWilayahKota(): array
    {
        $jsonFile = public_path(self::WILAYAH_FILE);

        if (!File::exists($jsonFile)) {
            return [
                'success' => false,
                'status_code' => 404,
                'message' => 'File data wilayah tidak ditemukan'
            ];
        }

        $wilayahData = json_decode(File::get($jsonFile), true);

        return [
            'success' => true,
            'status_code' => 200,
            'data' => $wilayahData['wilayah']
        ];
    }

    /**
     * Get kecamatan by kota ID
     *
     * @param string|null $kotaId
     * @return array
     */
    public static function getKecamatanByKota(?string $kotaId): array
    {
        if (!$kotaId) {
            return [
                'success' => false,
                'status_code' => 400,
                'message' => 'ID Kota/Kabupaten tidak valid'
            ];
        }

        $jsonFile = public_path(self::WILAYAH_FILE);

        if (!File::exists($jsonFile)) {
            return [
                'success' => false,
                'status_code' => 404,
                'message' => 'File data wilayah tidak ditemukan'
            ];
        }

        $wilayahData = json_decode(File::get($jsonFile), true);
        $kecamatanData = [];

        foreach ($wilayahData['wilayah'] as $kota) {
            if ($kota['id'] == $kotaId) {
                $kecamatanData = $kota['kecamatan'];
                break;
            }
        }

        return [
            'success' => true,
            'status_code' => 200,
            'data' => $kecamatanData
        ];
    }

    /**
     * Get kelurahan by kecamatan ID
     *
     * @param string|null $kotaId
     * @param string|null $kecamatanId
     * @return array
     */
    public static function getKelurahanByKecamatan(?string $kotaId, ?string $kecamatanId): array
    {
        if (!$kotaId || !$kecamatanId) {
            return [
                'success' => false,
                'status_code' => 400,
                'message' => 'ID Kota/Kabupaten atau Kecamatan tidak valid'
            ];
        }

        $jsonFile = public_path(self::WILAYAH_FILE);

        if (!File::exists($jsonFile)) {
            return [
                'success' => false,
                'status_code' => 404,
                'message' => 'File data wilayah tidak ditemukan'
            ];
        }

        $wilayahData = json_decode(File::get($jsonFile), true);
        $kelurahanData = [];

        foreach ($wilayahData['wilayah'] as $kota) {
            if ($kota['id'] == $kotaId) {
                foreach ($kota['kecamatan'] as $kecamatan) {
                    if ($kecamatan['id'] == $kecamatanId) {
                        $kelurahanData = $kecamatan['kelurahan'];
                        break 2;
                    }
                }
            }
        }

        return [
            'success' => true,
            'status_code' => 200,
            'data' => $kelurahanData
        ];
    }

    /**
     * Get full wilayah hierarchy by IDs
     *
     * @param string $kotaId
     * @param string $kecamatanId
     * @param string $kelurahanId
     * @return array|null
     */
    public static function getWilayahByIds(string $kotaId, string $kecamatanId, string $kelurahanId): ?array
    {
        $jsonFile = public_path(self::WILAYAH_FILE);

        if (!File::exists($jsonFile)) {
            return null;
        }

        $wilayahData = json_decode(File::get($jsonFile), true);

        foreach ($wilayahData['wilayah'] as $kota) {
            if ($kota['id'] == $kotaId) {
                foreach ($kota['kecamatan'] as $kecamatan) {
                    if ($kecamatan['id'] == $kecamatanId) {
                        foreach ($kecamatan['kelurahan'] as $kelurahan) {
                            if ($kelurahan['id'] == $kelurahanId) {
                                return [
                                    'kota' => [
                                        'id' => $kota['id'],
                                        'name' => $kota['name']
                                    ],
                                    'kecamatan' => [
                                        'id' => $kecamatan['id'],
                                        'name' => $kecamatan['name']
                                    ],
                                    'kelurahan' => [
                                        'id' => $kelurahan['id'],
                                        'name' => $kelurahan['name']
                                    ]
                                ];
                            }
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * Validate wilayah combination exists
     *
     * @param string $kotaId
     * @param string $kecamatanId
     * @param string $kelurahanId
     * @return bool
     */
    public static function validateWilayahCombination(string $kotaId, string $kecamatanId, string $kelurahanId): bool
    {
        return self::getWilayahByIds($kotaId, $kecamatanId, $kelurahanId) !== null;
    }

    /**
     * Search wilayah by name
     *
     * @param string $searchTerm
     * @return array
     */
    public static function searchWilayah(string $searchTerm): array
    {
        $jsonFile = public_path(self::WILAYAH_FILE);

        if (!File::exists($jsonFile)) {
            return [];
        }

        $wilayahData = json_decode(File::get($jsonFile), true);
        $results = [];
        $searchLower = strtolower($searchTerm);

        foreach ($wilayahData['wilayah'] as $kota) {
            foreach ($kota['kecamatan'] as $kecamatan) {
                foreach ($kecamatan['kelurahan'] as $kelurahan) {
                    if (
                        str_contains(strtolower($kota['name']), $searchLower) ||
                        str_contains(strtolower($kecamatan['name']), $searchLower) ||
                        str_contains(strtolower($kelurahan['name']), $searchLower)
                    ) {
                        $results[] = [
                            'kota' => $kota['name'],
                            'kota_id' => $kota['id'],
                            'kecamatan' => $kecamatan['name'],
                            'kecamatan_id' => $kecamatan['id'],
                            'kelurahan' => $kelurahan['name'],
                            'kelurahan_id' => $kelurahan['id'],
                            'full_address' => $kelurahan['name'] . ', ' . $kecamatan['name'] . ', ' . $kota['name']
                        ];
                    }
                }
            }
        }

        return $results;
    }
}
