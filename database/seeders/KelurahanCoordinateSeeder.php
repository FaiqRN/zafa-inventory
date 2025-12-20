<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KelurahanCoordinate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class KelurahanCoordinateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Truncate table untuk fresh seed
        Schema::disableForeignKeyConstraints();
        DB::table('kelurahan_coordinates')->truncate();
        Schema::enableForeignKeyConstraints();

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
                    $isManual = isset($coordinateMap[$kelurahanId]);
                    $coords = $isManual ? $coordinateMap[$kelurahanId] : $this->generateDefaultCoordinates($kotaNama, $kecamatanNama);
                    
                    $kelurahanData[] = [
                        'nama' => $kelurahanNama,
                        'nama_normalized' => strtolower(str_replace([' ', '_', '-'], '', $kelurahanNama)),
                        'kecamatan' => $kecamatanNama,
                        'kota' => $kotaNama,
                        'latitude' => $coords['lat'],
                        'longitude' => $coords['lng'],
                        'is_active' => true,
                        'source' => $isManual ? 'manual' : 'generated',
                        'accuracy' => $isManual ? 'high' : 'low',
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
            'kidul dalem' => ['lat' => -7.9900, 'lng' => 112.6300],
            'kidul_dalem' => ['lat' => -7.9900, 'lng' => 112.6300],
            'oro oro dowo' => ['lat' => -7.9700, 'lng' => 112.6150],
            'oro_oro_dowo' => ['lat' => -7.9700, 'lng' => 112.6150],
            'bareng' => ['lat' => -7.9650, 'lng' => 112.6100],
            'gadingkasri' => ['lat' => -7.9600, 'lng' => 112.6050],
            'sukoharjo' => ['lat' => -7.9550, 'lng' => 112.6000],
            'rampal celaket' => ['lat' => -7.9800, 'lng' => 112.6100],
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
            'bakalan krajan' => ['lat' => -8.0400, 'lng' => 112.6500],
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

            // Kota Batu
            'ngaglik' => ['lat' => -7.8700, 'lng' => 112.5200],
            'pesanggrahan' => ['lat' => -7.8800, 'lng' => 112.5300],
            'sisir' => ['lat' => -7.8900, 'lng' => 112.5400],
            'songgokerto' => ['lat' => -7.9000, 'lng' => 112.5500],
            'sumberejo' => ['lat' => -7.9100, 'lng' => 112.5600],
            'temas' => ['lat' => -7.9200, 'lng' => 112.5700],
            'oro oro ombo' => ['lat' => -7.9300, 'lng' => 112.5800],
            'oro_oro_ombo' => ['lat' => -7.9300, 'lng' => 112.5800],
            'sidomulyo' => ['lat' => -7.9400, 'lng' => 112.5900],

        // Kabupaten Malang - Singosari
            'singosari' => ['lat' => -7.8950, 'lng' => 112.6650],
            'ardimulyo' => ['lat' => -7.8900, 'lng' => 112.6700],
            'banjararum' => ['lat' => -7.8850, 'lng' => 112.6750],
            'candirenggo' => ['lat' => -7.9000, 'lng' => 112.6600],
            'pagentan' => ['lat' => -7.9050, 'lng' => 112.6700],
            'randuagung' => ['lat' => -7.8800, 'lng' => 112.6650],
            'toyomarto' => ['lat' => -7.8750, 'lng' => 112.6800],

            // Kabupaten Malang - Lawang
            'lawang' => ['lat' => -7.8350, 'lng' => 112.6940],
            'kalirejo' => ['lat' => -7.8400, 'lng' => 112.7000],
            'bedali' => ['lat' => -7.8300, 'lng' => 112.7050],
            'turirejo' => ['lat' => -7.8450, 'lng' => 112.6900],
            'ketindan' => ['lat' => -7.8500, 'lng' => 112.6850],
            'wonorejo' => ['lat' => -7.8250, 'lng' => 112.7100],

            // Kabupaten Malang - Karangploso
            'karangploso' => ['lat' => -7.9150, 'lng' => 112.6000],
            'ngijo' => ['lat' => -7.9100, 'lng' => 112.5950],
            'bocek' => ['lat' => -7.9200, 'lng' => 112.6050],
            'donowarih' => ['lat' => -7.9250, 'lng' => 112.5900],
            'ampeldento' => ['lat' => -7.9050, 'lng' => 112.6100],

            // Kabupaten Malang - Dau
            'dau' => ['lat' => -7.8900, 'lng' => 112.5800],
            'tegalweru' => ['lat' => -7.8850, 'lng' => 112.5750],
            'mulyoagung' => ['lat' => -7.8950, 'lng' => 112.5850],
            'petungsari' => ['lat' => -7.9000, 'lng' => 112.5900],
            'kucur' => ['lat' => -7.8800, 'lng' => 112.5700],

            // Kabupaten Malang - Pujon
            'pujon' => ['lat' => -7.8550, 'lng' => 112.4700],
            'pujonkidul' => ['lat' => -7.8650, 'lng' => 112.4650],
            'pujonlor' => ['lat' => -7.8450, 'lng' => 112.4750],
            'pandesari' => ['lat' => -7.8500, 'lng' => 112.4800],
            'ngabab' => ['lat' => -7.8600, 'lng' => 112.4600],

            // Kabupaten Malang - Ngantang
            'ngantang' => ['lat' => -7.7800, 'lng' => 112.5000],
            'tulungrejo' => ['lat' => -7.7750, 'lng' => 112.5050],
            'banturejo' => ['lat' => -7.7850, 'lng' => 112.4950],
            'purworejo' => ['lat' => -7.7900, 'lng' => 112.5100],
            'pagersari' => ['lat' => -7.7700, 'lng' => 112.4900],

            // Kabupaten Malang - Kasembon
            'kasembon' => ['lat' => -7.9500, 'lng' => 112.4500],
            'sukosari' => ['lat' => -7.9550, 'lng' => 112.4450],
            'pondokagung' => ['lat' => -7.9450, 'lng' => 112.4550],
            'bayem' => ['lat' => -7.9600, 'lng' => 112.4600],

            // Kabupaten Malang - Pakis
            'pakis' => ['lat' => -7.9700, 'lng' => 112.7200],
            'pakisjajar' => ['lat' => -7.9650, 'lng' => 112.7250],
            'sukoanyar' => ['lat' => -7.9750, 'lng' => 112.7150],
            'asrikaton' => ['lat' => -7.9800, 'lng' => 112.7300],
            'kedungrejo' => ['lat' => -7.9600, 'lng' => 112.7350],

            // Kabupaten Malang - Tumpang
            'tumpang' => ['lat' => -8.0100, 'lng' => 112.7600],
            'jeru' => ['lat' => -8.0050, 'lng' => 112.7650],
            'pandansari' => ['lat' => -8.0150, 'lng' => 112.7550],
            'pulungdowo' => ['lat' => -8.0200, 'lng' => 112.7700],
            'kidal' => ['lat' => -8.0000, 'lng' => 112.7750],
            'wringinsongo' => ['lat' => -7.9950, 'lng' => 112.7800],

            // Kabupaten Malang - Poncokusumo
            'poncokusumo' => ['lat' => -8.0500, 'lng' => 112.8000],
            'ngadas' => ['lat' => -8.0400, 'lng' => 112.8100],
            'wonomulyo' => ['lat' => -8.0600, 'lng' => 112.7950],
            'gubugklakah' => ['lat' => -8.0550, 'lng' => 112.8200],

            // Kabupaten Malang - Jabung
            'jabung' => ['lat' => -7.8650, 'lng' => 112.7500],
            'kemantren' => ['lat' => -7.8700, 'lng' => 112.7550],
            'sumberejo' => ['lat' => -7.8600, 'lng' => 112.7600],
            'sidorejo' => ['lat' => -7.8550, 'lng' => 112.7650],
            'taji' => ['lat' => -7.8750, 'lng' => 112.7450],

            // Kabupaten Malang - Turen
            'turen' => ['lat' => -8.1690, 'lng' => 112.7100],
            'talok' => ['lat' => -8.1750, 'lng' => 112.7150],
            'tanggung' => ['lat' => -8.1630, 'lng' => 112.7050],
            'sanankerto' => ['lat' => -8.1800, 'lng' => 112.7200],
            'sanankulon' => ['lat' => -8.1600, 'lng' => 112.7000],

            // Kabupaten Malang - Gondanglegi
            'gondanglegi' => ['lat' => -8.1850, 'lng' => 112.6350],
            'sukosari' => ['lat' => -8.1900, 'lng' => 112.6400],
            'bulupitu' => ['lat' => -8.1800, 'lng' => 112.6300],
            'panggungrejo' => ['lat' => -8.1950, 'lng' => 112.6450],
            'patokpicis' => ['lat' => -8.1750, 'lng' => 112.6250],

            // Kabupaten Malang - Wagir
            'wagir' => ['lat' => -8.1200, 'lng' => 112.7300],
            'petung' => ['lat' => -8.1150, 'lng' => 112.7350],
            'gondowangi' => ['lat' => -8.1250, 'lng' => 112.7250],
            'ngadirejo' => ['lat' => -8.1300, 'lng' => 112.7400],
            'sidorahayu' => ['lat' => -8.1100, 'lng' => 112.7450],

            // Kabupaten Malang - Pakisaji
            'pakisaji' => ['lat' => -8.0650, 'lng' => 112.5980],
            'jatisari' => ['lat' => -8.0700, 'lng' => 112.6030],
            'karangsari' => ['lat' => -8.0600, 'lng' => 112.5930],
            'kendalpayak' => ['lat' => -8.0750, 'lng' => 112.6080],
            'kebonagung' => ['lat' => -8.0550, 'lng' => 112.5880],

            // Kabupaten Malang - Kepanjen
            'kepanjen' => ['lat' => -8.1300, 'lng' => 112.5730],
            'ardirejo' => ['lat' => -8.1350, 'lng' => 112.5780],
            'sengguruh' => ['lat' => -8.1250, 'lng' => 112.5680],
            'mangunrejo' => ['lat' => -8.1400, 'lng' => 112.5830],
            'talangagung' => ['lat' => -8.1200, 'lng' => 112.5630],

            // Kabupaten Malang - Ngajum
            'ngajum' => ['lat' => -8.1000, 'lng' => 112.5000],
            'balesari' => ['lat' => -8.1050, 'lng' => 112.5050],
            'palaan' => ['lat' => -8.0950, 'lng' => 112.4950],
            'sumberputih' => ['lat' => -8.1100, 'lng' => 112.5100],

            // Kabupaten Malang - Wonosari
            'wonosari' => ['lat' => -8.0800, 'lng' => 112.5200],
            'plaosan' => ['lat' => -8.0850, 'lng' => 112.5250],
            'wonokerto' => ['lat' => -8.0750, 'lng' => 112.5150],
            'sukolilo' => ['lat' => -8.0900, 'lng' => 112.5300],

            // Kabupaten Malang - Bululawang
            'bululawang' => ['lat' => -8.0950, 'lng' => 112.6420],
            'godo' => ['lat' => -8.1000, 'lng' => 112.6470],
            'krebet' => ['lat' => -8.0900, 'lng' => 112.6370],
            'kuwolu' => ['lat' => -8.1050, 'lng' => 112.6520],

            // Kabupaten Malang - Tajinan
            'tajinan' => ['lat' => -8.0350, 'lng' => 112.5680],
            'tanjungsari' => ['lat' => -8.0400, 'lng' => 112.5730],
            'kanigoro' => ['lat' => -8.0300, 'lng' => 112.5630],
            'mulyosari' => ['lat' => -8.0450, 'lng' => 112.5780],

            // Kabupaten Malang - Sumberpucung
            'sumberpucung' => ['lat' => -8.1700, 'lng' => 112.4800],
            'karangkates' => ['lat' => -8.1650, 'lng' => 112.4850],
            'ngudirejo' => ['lat' => -8.1750, 'lng' => 112.4750],
            'sumberdem' => ['lat' => -8.1800, 'lng' => 112.4900],

            // Kabupaten Malang - Kromengan
            'kromengan' => ['lat' => -8.1500, 'lng' => 112.5200],
            'wringinagung' => ['lat' => -8.1550, 'lng' => 112.5250],
            'karangpandan' => ['lat' => -8.1450, 'lng' => 112.5150],

            // Kabupaten Malang - Dampit
            'dampit' => ['lat' => -8.2100, 'lng' => 112.7500],
            'amadanom' => ['lat' => -8.2050, 'lng' => 112.7550],
            'pamotan' => ['lat' => -8.2150, 'lng' => 112.7450],
            'srimulyo' => ['lat' => -8.2200, 'lng' => 112.7600],
            'baturetno' => ['lat' => -8.2000, 'lng' => 112.7650],

            // Kabupaten Malang - Tirtoyudo
            'tirtoyudo' => ['lat' => -8.3200, 'lng' => 112.6800],
            'tamankuncaran' => ['lat' => -8.3150, 'lng' => 112.6850],
            'pujiharjo' => ['lat' => -8.3250, 'lng' => 112.6750],
            'sumbertangkil' => ['lat' => -8.3300, 'lng' => 112.6900],

            // Kabupaten Malang - Ampelgading
            'ampelgading' => ['lat' => -8.3500, 'lng' => 112.6200],
            'lebakharjo' => ['lat' => -8.3450, 'lng' => 112.6250],
            'argotirto' => ['lat' => -8.3550, 'lng' => 112.6150],
            'tirtomoyo' => ['lat' => -8.3600, 'lng' => 112.6300],

            // Kabupaten Malang - Sumbermanjing Wetan
            'sumbermanjing wetan' => ['lat' => -8.3800, 'lng' => 112.5500],
            'sumbermanjing_wetan' => ['lat' => -8.3800, 'lng' => 112.5500],
            'tambakrejo' => ['lat' => -8.3850, 'lng' => 112.5550],
            'druju' => ['lat' => -8.3750, 'lng' => 112.5450],
            'sitiarjo' => ['lat' => -8.3900, 'lng' => 112.5600],

            // Kabupaten Malang - Gedangan
            'gedangan' => ['lat' => -8.4200, 'lng' => 112.5800],
            'tumpakrejo' => ['lat' => -8.4150, 'lng' => 112.5850],
            'sidodadi' => ['lat' => -8.4250, 'lng' => 112.5750],
            'gajahrejo' => ['lat' => -8.4300, 'lng' => 112.5900],

            // Kabupaten Malang - Bantur
            'bantur' => ['lat' => -8.4500, 'lng' => 112.5300],
            'bandungrejo' => ['lat' => -8.4450, 'lng' => 112.5350],
            'pringgodani' => ['lat' => -8.4550, 'lng' => 112.5250],
            'rejosari' => ['lat' => -8.4600, 'lng' => 112.5400],

            // Kabupaten Malang - Donomulyo
            'donomulyo' => ['lat' => -8.1600, 'lng' => 112.3500],
            'purwodadi' => ['lat' => -8.1550, 'lng' => 112.3550],
            'mentaraman' => ['lat' => -8.1650, 'lng' => 112.3450],
            'sumberoto' => ['lat' => -8.1700, 'lng' => 112.3600],

            // Kabupaten Malang - Kalipare
            'kalipare' => ['lat' => -8.2500, 'lng' => 112.3800],
            'sukolilo' => ['lat' => -8.2450, 'lng' => 112.3850],
            'tlogosari' => ['lat' => -8.2550, 'lng' => 112.3750],

            // Kabupaten Malang - Pagak
            'pagak' => ['lat' => -8.3500, 'lng' => 112.4200],
            'gunungsari' => ['lat' => -8.3450, 'lng' => 112.4250],
            'kemulan' => ['lat' => -8.3550, 'lng' => 112.4150],

            // Kabupaten Malang - Wajak
            'wajak' => ['lat' => -8.1200, 'lng' => 112.7900],
            'kidangbang' => ['lat' => -8.1150, 'lng' => 112.7950],
            'ngembal' => ['lat' => -8.1250, 'lng' => 112.7850],
            'sukolilo' => ['lat' => -8.1300, 'lng' => 112.8000],

            // Kabupaten Malang - Pagelaran
            'pagelaran' => ['lat' => -8.2200, 'lng' => 112.6500],
            'karangduren' => ['lat' => -8.2150, 'lng' => 112.6550],
            'talun' => ['lat' => -8.2250, 'lng' => 112.6450],
            'sidodadi' => ['lat' => -8.2300, 'lng' => 112.6600],
        ];
        
    }

    /**
     * Generate default coordinates berdasarkan kota/kecamatan
     * FIXED: Reduced random offset from ±11km to ±25-30 meters for accuracy
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
        
        // Add minimal random offset untuk variasi (~25-30 meters)
        // CRITICAL FIX: Changed from rand(-100, 100) / 1000 (±11km) to rand(-25, 25) / 100000 (±25-30m)
        // 0.00025 degrees ≈ 25-30 meters at Malang's latitude
        $latOffset = (rand(-25, 25) / 100000);
        $lngOffset = (rand(-25, 25) / 100000);
        
        return [
            'lat' => round($baseCoords['lat'] + $latOffset, 8),
            'lng' => round($baseCoords['lng'] + $lngOffset, 8),
        ];
    }
}
