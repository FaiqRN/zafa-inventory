<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KelurahanCoordinate;
use Illuminate\Support\Facades\DB;

class KelurahanCoordinateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Truncate table untuk fresh seed
        DB::table('kelurahan_coordinates')->truncate();

        // Load data dari wilayah_malang.json
        $jsonPath = public_path('data/wilayah_malang.json');
        
        if (!file_exists($jsonPath)) {
            $this->command->error('❌ File wilayah_malang.json tidak ditemukan di ' . $jsonPath);
            return;
        }

        $jsonContent = file_get_contents($jsonPath);
        $wilayahData = json_decode($jsonContent, true);

        if (!isset($wilayahData['wilayah'])) {
            $this->command->error('❌ Format JSON tidak valid');
            return;
        }

        $kelurahanData = [];
        $coordinateMap = $this->getCoordinateMap();

        // Parse data dari JSON
        foreach ($wilayahData['wilayah'] as $kota) {
            $kotaNama = $kota['nama'];
            
            foreach ($kota['kecamatan'] as $kecamatan) {
                $kecamatanNama = $kecamatan['nama'];
                
                foreach ($kecamatan['kelurahan'] as $kelurahan) {
                    $kelurahanNama = $kelurahan['nama'];
                    $kelurahanId = $kelurahan['id'];
                    
                    // Get coordinates dari map atau generate default
                    $coords = $coordinateMap[$kelurahanId] ?? $this->generateDefaultCoordinates($kotaNama, $kecamatanNama);
                    
                    $kelurahanData[] = [
                        'nama' => $kelurahanNama,
                        'nama_normalized' => strtolower(str_replace([' ', '_', '-'], '', $kelurahanNama)),
                        'kecamatan' => $kecamatanNama,
                        'kota' => $kotaNama,
                        'latitude' => $coords['lat'],
                        'longitude' => $coords['lng'],
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        // Batch insert untuk performance
        foreach (array_chunk($kelurahanData, 100) as $chunk) {
            DB::table('kelurahan_coordinates')->insert($chunk);
        }

        $this->command->info('✅ Berhasil seed ' . count($kelurahanData) . ' kelurahan coordinates dari wilayah_malang.json');
    }

    /**
     * Map koordinat untuk kelurahan-kelurahan utama
     * Koordinat ini didapat dari data OSM atau Google Maps
     */
    private function getCoordinateMap(): array
    {
        return [
            // Kota Malang - Blimbing
            'polowijen' => ['lat' => -7.9200, 'lng' => 112.6400],
            'balearjosari' => ['lat' => -7.9100, 'lng' => 112.6450],
            'bunulrejo' => ['lat' => -7.9000, 'lng' => 112.6500],
            'pandanwangi' => ['lat' => -7.9300, 'lng' => 112.6600],
            'blimbing' => ['lat' => -7.9400, 'lng' => 112.6550],
            'polehan' => ['lat' => -7.9500, 'lng' => 112.6400],
            'jodipan' => ['lat' => -7.9600, 'lng' => 112.6350],
            'kesatrian' => ['lat' => -7.9700, 'lng' => 112.6300],
            'purwantoro' => ['lat' => -7.9800, 'lng' => 112.6250],
            'purwodadi' => ['lat' => -7.9150, 'lng' => 112.6350],
            'arjosari' => ['lat' => -7.9050, 'lng' => 112.6400],

            // Kota Malang - Lowokwaru
            'jatimulyo' => ['lat' => -7.9455, 'lng' => 112.6198],
            'tlogomas' => ['lat' => -7.9340, 'lng' => 112.6144],
            'lowokwaru' => ['lat' => -7.9451, 'lng' => 112.6097],
            'mojolangu' => ['lat' => -7.9290, 'lng' => 112.6180],
            'tulusrejo' => ['lat' => -7.9380, 'lng' => 112.6050],
            'dinoyo' => ['lat' => -7.9520, 'lng' => 112.6120],
            'sumbersari' => ['lat' => -7.9600, 'lng' => 112.6200],
            'ketawanggede' => ['lat' => -7.9350, 'lng' => 112.6080],
            'merjosari' => ['lat' => -7.9250, 'lng' => 112.6100],
            'tunggulwulung' => ['lat' => -7.9400, 'lng' => 112.6000],
            'tasikmadu' => ['lat' => -7.9500, 'lng' => 112.6000],
            'tunjungsekar' => ['lat' => -7.9300, 'lng' => 112.6050],

            // Kota Malang - Klojen
            'kasin' => ['lat' => -7.9750, 'lng' => 112.6300],
            'klojen' => ['lat' => -7.9800, 'lng' => 112.6200],
            'kauman' => ['lat' => -7.9850, 'lng' => 112.6250],
            'kidul_dalem' => ['lat' => -7.9900, 'lng' => 112.6300],
            'oro_oro_dowo' => ['lat' => -7.9700, 'lng' => 112.6150],
            'bareng' => ['lat' => -7.9650, 'lng' => 112.6100],
            'gadingkasri' => ['lat' => -7.9600, 'lng' => 112.6050],
            'sukoharjo' => ['lat' => -7.9550, 'lng' => 112.6000],
            'rampal_celaket' => ['lat' => -7.9800, 'lng' => 112.6100],
            'samaan' => ['lat' => -7.9750, 'lng' => 112.6050],
            'penanggungan' => ['lat' => -7.9700, 'lng' => 112.6000],

            // Kota Malang - Sukun
            'sukun' => ['lat' => -8.0000, 'lng' => 112.6200],
            'gadang' => ['lat' => -8.0100, 'lng' => 112.6150],
            'karangbesuki' => ['lat' => -8.0200, 'lng' => 112.6100],
            'tanjungrejo' => ['lat' => -8.0050, 'lng' => 112.6250],
            'bandulan' => ['lat' => -8.0150, 'lng' => 112.6300],
            'mulyorejo' => ['lat' => -8.0250, 'lng' => 112.6350],
            'pisangcandi' => ['lat' => -8.0300, 'lng' => 112.6400],
            'ciptomulyo' => ['lat' => -8.0350, 'lng' => 112.6450],
            'bakalan_krajan' => ['lat' => -8.0400, 'lng' => 112.6500],
            'kebonsari' => ['lat' => -8.0450, 'lng' => 112.6550],
            'bandungrejosari' => ['lat' => -8.0500, 'lng' => 112.6600],

            // Kota Malang - Kedungkandang
            'kedungkandang' => ['lat' => -8.0000, 'lng' => 112.6800],
            'sawojajar' => ['lat' => -7.9800, 'lng' => 112.7000],
            'arjowinangun' => ['lat' => -7.9700, 'lng' => 112.6900],
            'mergosono' => ['lat' => -7.9900, 'lng' => 112.6950],
            'buring' => ['lat' => -8.0100, 'lng' => 112.7100],
            'bumiayu' => ['lat' => -8.0200, 'lng' => 112.7200],
            'wonokoyo' => ['lat' => -8.0300, 'lng' => 112.7300],
            'tlogowaru' => ['lat' => -8.0400, 'lng' => 112.7400],
            'madyopuro' => ['lat' => -8.0500, 'lng' => 112.7500],
            'lesanpuro' => ['lat' => -8.0600, 'lng' => 112.7600],
            'cemorokandang' => ['lat' => -8.0700, 'lng' => 112.7700],
            'kotalama' => ['lat' => -8.0800, 'lng' => 112.7800],
        ];
    }

    /**
     * Generate default coordinates berdasarkan kota/kecamatan
     */
    private function generateDefaultCoordinates(string $kota, string $kecamatan): array
    {
        // Default center untuk setiap kota
        $kotaDefaults = [
            'Kota Malang' => ['lat' => -7.9666, 'lng' => 112.6326],
            'Kabupaten Malang' => ['lat' => -8.1000, 'lng' => 112.6500],
            'Kota Batu' => ['lat' => -7.8700, 'lng' => 112.5200],
        ];

        $baseCoords = $kotaDefaults[$kota] ?? ['lat' => -7.9666, 'lng' => 112.6326];
        
        // Add small random offset untuk variasi
        $latOffset = (rand(-100, 100) / 1000);
        $lngOffset = (rand(-100, 100) / 1000);
        
        return [
            'lat' => round($baseCoords['lat'] + $latOffset, 8),
            'lng' => round($baseCoords['lng'] + $lngOffset, 8),
        ];
    }
}
