<?php

namespace App\Helpers\MasterData\Toko;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class WilayahHelper
{

    private const WILAYAH_FILE = 'data/wilayah_malang.json';

    public static function getWilayahKota(): array
    {
        try {
            $jsonFile = public_path(self::WILAYAH_FILE);

            if (!File::exists($jsonFile)) {
                Log::warning('Wilayah JSON file not found: ' . self::WILAYAH_FILE);
                return [
                    'success' => false,
                    'status_code' => 404,
                    'message' => 'File data wilayah tidak ditemukan'
                ];
            }

            $wilayahData = json_decode(File::get($jsonFile), true);

            if (!isset($wilayahData['wilayah'])) {
                Log::error('Invalid wilayah JSON structure');
                return [
                    'success' => false,
                    'status_code' => 500,
                    'message' => 'Format data wilayah tidak valid'
                ];
            }

            return [
                'success' => true,
                'status_code' => 200,
                'data' => $wilayahData['wilayah']
            ];
        } catch (\Exception $e) {
            Log::error('Error loading wilayah data: ' . $e->getMessage());
            return [
                'success' => false,
                'status_code' => 500,
                'message' => 'Gagal memuat data wilayah'
            ];
        }
    }

    public static function getKecamatanByKota(?string $kotaId): array
    {
        if (!$kotaId) {
            return [
                'success' => false,
                'status_code' => 400,
                'message' => 'ID Kota/Kabupaten tidak valid'
            ];
        }

        try {
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

            foreach ($wilayahData['wilayah'] ?? [] as $kota) {
                if ($kota['id'] == $kotaId) {
                    $kecamatanData = $kota['kecamatan'] ?? [];
                    break;
                }
            }

            return [
                'success' => true,
                'status_code' => 200,
                'data' => $kecamatanData
            ];
        } catch (\Exception $e) {
            Log::error('Error loading kecamatan data: ' . $e->getMessage());
            return [
                'success' => false,
                'status_code' => 500,
                'message' => 'Gagal memuat data kecamatan'
            ];
        }
    }

    public static function getKelurahanByKecamatan(?string $kotaId, ?string $kecamatanId): array
    {
        if (!$kotaId || !$kecamatanId) {
            return [
                'success' => false,
                'status_code' => 400,
                'message' => 'ID Kota/Kabupaten atau Kecamatan tidak valid'
            ];
        }

        try {
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

            foreach ($wilayahData['wilayah'] ?? [] as $kota) {
                if ($kota['id'] == $kotaId) {
                    foreach ($kota['kecamatan'] ?? [] as $kecamatan) {
                        if ($kecamatan['id'] == $kecamatanId) {
                            $kelurahanData = $kecamatan['kelurahan'] ?? [];
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
        } catch (\Exception $e) {
            Log::error('Error loading kelurahan data: ' . $e->getMessage());
            return [
                'success' => false,
                'status_code' => 500,
                'message' => 'Gagal memuat data kelurahan'
            ];
        }
    }

    public static function getWilayahByIds(string $kotaId, string $kecamatanId, string $kelurahanId): ?array
    {
        try {
            $jsonFile = public_path(self::WILAYAH_FILE);

            if (!File::exists($jsonFile)) {
                return null;
            }

            $wilayahData = json_decode(File::get($jsonFile), true);

            foreach ($wilayahData['wilayah'] ?? [] as $kota) {
                if ($kota['id'] == $kotaId) {
                    foreach ($kota['kecamatan'] ?? [] as $kecamatan) {
                        if ($kecamatan['id'] == $kecamatanId) {
                            foreach ($kecamatan['kelurahan'] ?? [] as $kelurahan) {
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
        } catch (\Exception $e) {
            Log::error('Error getting wilayah by IDs: ' . $e->getMessage());
            return null;
        }
    }

    public static function validateWilayahCombination(string $kotaId, string $kecamatanId, string $kelurahanId): bool
    {
        return self::getWilayahByIds($kotaId, $kecamatanId, $kelurahanId) !== null;
    }
}
