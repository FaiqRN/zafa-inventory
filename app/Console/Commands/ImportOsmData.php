<?php

namespace App\Console\Commands;

use App\Models\Jalan;
use App\Models\JalanSegment;
use App\Models\Poi;
use App\Models\KelurahanCoordinate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportOsmData extends Command
{
    protected $signature = 'osm:import 
                            {file : Path to OSM SQL file}
                            {--type=all : Type to import: streets, pois, or all}
                            {--chunk=1000 : Chunk size for batch insert}
                            {--dry-run : Preview without inserting}';

    protected $description = 'Import OpenStreetMap data from PostGIS SQL dump to MySQL';

    private int $streetsImported = 0;
    private int $poisImported = 0;
    private int $segmentsImported = 0;
    private int $skipped = 0;
    private array $kelurahanCache = [];

    // Malang region bounding box
    private const MALANG_BOUNDS = [
        'min_lat' => -8.6,
        'max_lat' => -7.4,
        'min_lng' => 111.8,
        'max_lng' => 113.2,
    ];

    public function handle(): int
    {
        $filePath = $this->argument('file');
        $type = $this->option('type');
        $chunkSize = (int) $this->option('chunk');
        $dryRun = $this->option('dry-run');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        $this->info("Starting OSM data import from: {$filePath}");
        $this->info("Import type: {$type}");
        
        if ($dryRun) {
            $this->warn("DRY RUN MODE - No data will be inserted");
        }

        // Load kelurahan cache for matching
        $this->loadKelurahanCache();

        // Process file line by line
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            $this->error("Cannot open file: {$filePath}");
            return 1;
        }

        $streetsBatch = [];
        $poisBatch = [];
        $lineNumber = 0;
        $inCopySection = false;

        $progressBar = $this->output->createProgressBar();
        $progressBar->start();

        while (($line = fgets($handle)) !== false) {
            $lineNumber++;
            $line = trim($line);

            // Detect COPY section start
            if (str_starts_with($line, 'COPY "public"."data_jalan_malang"')) {
                $inCopySection = true;
                continue;
            }

            // Detect COPY section end
            if ($inCopySection && ($line === '\\.' || str_starts_with($line, 'COMMIT'))) {
                $inCopySection = false;
                continue;
            }

            // Process data lines
            if ($inCopySection && !empty($line)) {
                $parsed = $this->parseLine($line);
                
                if (!$parsed) {
                    continue;
                }

                // Categorize: street (ways_line) or POI (nodes)
                if ($parsed['osm_type'] === 'ways_line' && $parsed['highway'] && \in_array($type, ['all', 'streets'])) {
                    if ($parsed['name']) {
                        if ($dryRun) {
                            $this->streetsImported++;
                        } else {
                            $streetsBatch[] = $parsed;
                            
                            if (\count($streetsBatch) >= $chunkSize) {
                                $this->importStreetsBatch($streetsBatch);
                                $streetsBatch = [];
                                $progressBar->advance($chunkSize);
                            }
                        }
                    }
                } elseif ($parsed['osm_type'] === 'nodes' && \in_array($type, ['all', 'pois'])) {
                    // POI - any node with name and some identifying attribute
                    $hasName = !empty($parsed['name']);
                    $hasCategory = $parsed['amenity'] || $parsed['shop'] || $parsed['place'] || $parsed['tourism'] 
                        || $parsed['leisure'] || $parsed['office'] || $parsed['healthcare'] || $parsed['building'];
                    
                    // Import jika punya nama DAN kategori, ATAU punya nama saja (untuk POI tanpa kategori)
                    if ($hasName && $hasCategory) {
                        if ($dryRun) {
                            $this->poisImported++;
                        } else {
                            $poisBatch[] = $parsed;
                            
                            if (\count($poisBatch) >= $chunkSize) {
                                $this->importPoisBatch($poisBatch);
                                $poisBatch = [];
                                $progressBar->advance($chunkSize);
                            }
                        }
                    }
                }
            }
        }

        // Process remaining batches
        if (!$dryRun) {
            if (!empty($streetsBatch)) {
                $this->importStreetsBatch($streetsBatch);
            }
            if (!empty($poisBatch)) {
                $this->importPoisBatch($poisBatch);
            }
        }

        fclose($handle);
        $progressBar->finish();

        $this->newLine(2);
        $this->info("Import completed!");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Streets imported', $this->streetsImported],
                ['Segments imported', $this->segmentsImported],
                ['POIs imported', $this->poisImported],
                ['Skipped (out of bounds/invalid)', $this->skipped],
            ]
        );

        return 0;
    }

    /**
     * Parse a single line from the COPY section
     * 
     * Column order from SQL file (Data_Jalan_Malang.sql):
     * 0: geom, 1: osm_id, 2: osm_type, 3: health_facility_type, 4: power, 5: water,
     * 6: man_made, 7: roof_material, 8: admin_level, 9: addr_street, 10: width,
     * 11: amenity, 12: highway, 13: backup_generator, 14: beds, 15: historic,
     * 16: barrier, 17: office, 18: rooms, 19: fuel, 20: staff_count_doctors,
     * 21: medical_system_western, 22: isced_level, 23: smoothness, 24: religion,
     * 25: pump, 26: operator_type, 27: opening_hours, 28: population, 29: communication_radio,
     * 30: toilets_disposal, 31: surface, 32: health_facility_bed, 33: tower,
     * 34: public_transport, 35: network, 36: parking, 37: name_en, 38: capacity,
     * 39: covered, 40: access, 41: tourism, 42: blockage, 43: government, 44: layer,
     * 45: leisure, 46: addr_postcode, 47: emergency, 48: status, 49: boundary,
     * 50: health_facility_level, 51: bridge, 52: addr_housenumber, 53: diameter,
     * 54: communication_mobile, 55: access_roof, 56: building, 57: railway,
     * 58: denomination, 59: place, 60: toilets_handwashing, 61: military, 62: healthcare,
     * 63: name, 64: depth, 65: name_fr, 66: tunnel, 67: name_sw, 68: oneway,
     * 69: operator, 70: landuse, 71: waterway, 72: aeroway, 73: shop,
     * 74: building_material, 75: natural, 76: staff_count_nurses, 77: is_in
     */
    private function parseLine(string $line): ?array
    {
        // Split by tab
        $fields = explode("\t", $line);
        
        if (\count($fields) < 10) {
            return null;
        }

        // Parse WKB geometry to get coordinates
        $geom = $fields[0] ?? null;
        $coordinates = $this->parseWkbGeometry($geom);
        
        if (!$coordinates) {
            $this->skipped++;
            return null;
        }

        // Check if within Malang bounds
        if (!$this->isWithinMalangBounds($coordinates)) {
            $this->skipped++;
            return null;
        }

        return [
            'geometry' => $geom,
            'coordinates' => $coordinates,
            'osm_id' => $this->parseField($fields[1] ?? null),
            'osm_type' => $this->parseField($fields[2] ?? null),
            'name' => $this->parseField($fields[63] ?? null),
            'highway' => $this->parseField($fields[12] ?? null),
            'amenity' => $this->parseField($fields[11] ?? null),
            'shop' => $this->parseField($fields[73] ?? null),
            'addr_street' => $this->parseField($fields[9] ?? null),
            'addr_housenumber' => $this->parseField($fields[52] ?? null),
            'addr_postcode' => $this->parseField($fields[46] ?? null),
            'surface' => $this->parseField($fields[31] ?? null),
            'place' => $this->parseField($fields[59] ?? null),
            'tourism' => $this->parseField($fields[41] ?? null),
            // Additional fields
            'building' => $this->parseField($fields[56] ?? null),
            'leisure' => $this->parseField($fields[45] ?? null),
            'landuse' => $this->parseField($fields[70] ?? null),
            'healthcare' => $this->parseField($fields[62] ?? null),
            'office' => $this->parseField($fields[17] ?? null),
            'religion' => $this->parseField($fields[24] ?? null),
            'operator' => $this->parseField($fields[69] ?? null),
        ];
    }

    /**
     * Parse WKB geometry to coordinates
     * Supports POINT and LINESTRING (EWKB format with SRID)
     */
    private function parseWkbGeometry(?string $wkb): ?array
    {
        if (empty($wkb) || \strlen($wkb) < 42) {
            return null;
        }

        try {
            // EWKB format: 
            // Byte 0-1: byte order (01 = little endian)
            // Byte 2-9: type with SRID flag (01000020 = Point+SRID, 02000020 = LineString+SRID)
            // Byte 10-17: SRID (E6100000 = 4326)
            // Byte 18+: coordinates
            
            $byteOrder = substr($wkb, 0, 2);
            if ($byteOrder !== '01') {
                // Only support little-endian for now
                return null;
            }
            
            $typeHex = substr($wkb, 2, 8);
            
            if ($typeHex === '01000020') {
                // POINT with SRID
                return $this->parseWkbPoint($wkb);
            } elseif ($typeHex === '02000020') {
                // LINESTRING with SRID
                return $this->parseWkbLineString($wkb);
            }
        } catch (\Exception $e) {
            Log::warning("Failed to parse WKB: " . substr($wkb, 0, 50));
        }

        return null;
    }

    /**
     * Parse WKB POINT geometry (EWKB with SRID)
     * Format: 01 01000020 E6100000 [X:16hex] [Y:16hex]
     *         ^  ^        ^        ^         ^
     *         |  |        |        |         Y coordinate (lat)
     *         |  |        |        X coordinate (lng)
     *         |  |        SRID 4326
     *         |  Type: Point with SRID
     *         Little endian
     */
    private function parseWkbPoint(string $hex): ?array
    {
        // Total length: 2 + 8 + 8 + 16 + 16 = 50 chars
        if (\strlen($hex) < 50) {
            return null;
        }

        // X (longitude) starts at position 18
        $xHex = substr($hex, 18, 16);
        // Y (latitude) starts at position 34
        $yHex = substr($hex, 34, 16);

        $lng = $this->hexToDouble($xHex);
        $lat = $this->hexToDouble($yHex);

        if ($lng === null || $lat === null) {
            return null;
        }

        return [
            'type' => 'point',
            'lat' => $lat,
            'lng' => $lng,
            'points' => [['lat' => $lat, 'lng' => $lng]],
        ];
    }

    /**
     * Parse WKB LINESTRING geometry (EWKB with SRID)
     * Format: 01 02000020 E6100000 [numPoints:8hex] [points...]
     */
    private function parseWkbLineString(string $hex): ?array
    {
        // Minimum: 2 + 8 + 8 + 8 + 32 = 58 chars (header + 1 point)
        if (\strlen($hex) < 58) {
            return null;
        }

        // Number of points starts at position 18 (4 bytes = 8 hex chars)
        $numPointsHex = substr($hex, 18, 8);
        $numPoints = $this->hexToInt($numPointsHex);

        if ($numPoints <= 0 || $numPoints > 10000) {
            return null;
        }

        $points = [];
        $offset = 26; // Start after header (18) + numPoints (8)

        for ($i = 0; $i < $numPoints; $i++) {
            if ($offset + 32 > \strlen($hex)) {
                break;
            }

            $xHex = substr($hex, $offset, 16);
            $yHex = substr($hex, $offset + 16, 16);

            $lng = $this->hexToDouble($xHex);
            $lat = $this->hexToDouble($yHex);

            if ($lng !== null && $lat !== null) {
                $points[] = ['lat' => $lat, 'lng' => $lng];
            }

            $offset += 32;
        }

        if (empty($points)) {
            return null;
        }

        // Calculate center point
        $sumLat = array_sum(array_column($points, 'lat'));
        $sumLng = array_sum(array_column($points, 'lng'));
        $count = \count($points);

        return [
            'type' => 'linestring',
            'lat' => $sumLat / $count,
            'lng' => $sumLng / $count,
            'points' => $points,
        ];
    }

    /**
     * Convert hex string to double (IEEE 754 little-endian)
     */
    private function hexToDouble(string $hex): ?float
    {
        if (\strlen($hex) !== 16) {
            return null;
        }

        // Convert hex to binary and unpack as little-endian double
        $binary = @hex2bin($hex);
        if ($binary === false || \strlen($binary) !== 8) {
            return null;
        }
        
        // 'e' format is for little-endian double (PHP 7.0.15+)
        $unpacked = unpack('e', $binary);

        return $unpacked[1] ?? null;
    }

    /**
     * Convert hex string to integer (little endian)
     */
    private function hexToInt(string $hex): int
    {
        // Reverse byte order
        $reversed = '';
        for ($i = strlen($hex) - 2; $i >= 0; $i -= 2) {
            $reversed .= substr($hex, $i, 2);
        }
        return hexdec($reversed);
    }

    /**
     * Parse field value, converting \N to null
     */
    private function parseField(?string $value): ?string
    {
        if ($value === null || $value === '\\N' || $value === '') {
            return null;
        }
        return trim($value);
    }

    /**
     * Check if coordinates are within Malang region
     */
    private function isWithinMalangBounds(array $coords): bool
    {
        $lat = $coords['lat'];
        $lng = $coords['lng'];

        return $lat >= self::MALANG_BOUNDS['min_lat'] &&
               $lat <= self::MALANG_BOUNDS['max_lat'] &&
               $lng >= self::MALANG_BOUNDS['min_lng'] &&
               $lng <= self::MALANG_BOUNDS['max_lng'];
    }

    /**
     * Load kelurahan data for matching
     */
    private function loadKelurahanCache(): void
    {
        $this->info("Loading kelurahan cache...");
        
        $kelurahanList = KelurahanCoordinate::active()->get();
        
        foreach ($kelurahanList as $kel) {
            $key = strtolower($kel->nama);
            $this->kelurahanCache[$key] = $kel->id;
        }

        $this->info("Loaded " . count($this->kelurahanCache) . " kelurahan");
    }

    /**
     * Find kelurahan ID by coordinate (simple nearest match)
     */
    private function findKelurahanByCoordinate(float $lat, float $lng): ?int
    {
        // Simple implementation - find nearest kelurahan
        $nearest = KelurahanCoordinate::active()
            ->selectRaw('id, (POW(latitude - ?, 2) + POW(longitude - ?, 2)) as distance', [$lat, $lng])
            ->orderBy('distance')
            ->first();

        return $nearest?->id;
    }

    /**
     * Import batch of streets
     */
    private function importStreetsBatch(array $batch): void
    {
        DB::beginTransaction();
        
        try {
            foreach ($batch as $street) {
                $this->importStreet($street);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to import streets batch: " . $e->getMessage());
            $this->error("Batch import failed: " . $e->getMessage());
        }
    }

    /**
     * Import single street with segments
     */
    private function importStreet(array $data): void
    {
        $coords = $data['coordinates'];
        $points = $coords['points'] ?? [];

        if (empty($points)) {
            return;
        }

        // Check if already exists by osm_id
        $existing = Jalan::where(Jalan::FIELD_OSM_ID, $data['osm_id'])->first();
        if ($existing) {
            $this->skipped++;
            return;
        }

        // Calculate total length
        $totalLength = $this->calculatePolylineLength($points);

        // Find kelurahan
        $kelurahanId = $this->findKelurahanByCoordinate($coords['lat'], $coords['lng']);

        // Create jalan record
        $jalan = Jalan::create([
            Jalan::FIELD_OSM_ID => $data['osm_id'],
            Jalan::FIELD_NAMA_JALAN => $data['name'],
            Jalan::FIELD_NAMA_NORMALIZED => Jalan::normalizeNamaJalan($data['name']),
            Jalan::FIELD_HIGHWAY_TYPE => $data['highway'],
            Jalan::FIELD_SURFACE => $data['surface'],
            Jalan::FIELD_LATITUDE => $coords['lat'],
            Jalan::FIELD_LONGITUDE => $coords['lng'],
            Jalan::FIELD_CENTER_LAT => $coords['lat'],
            Jalan::FIELD_CENTER_LNG => $coords['lng'],
            Jalan::FIELD_TOTAL_LENGTH_METERS => $totalLength,
            Jalan::FIELD_KELURAHAN_ID => $kelurahanId,
            Jalan::FIELD_SOURCE => 'osm',
            Jalan::FIELD_ACCURACY => 'high',
            Jalan::FIELD_IS_ACTIVE => true,
        ]);

        // Create segments
        $distanceFromStart = 0;
        $prevPoint = null;

        foreach ($points as $index => $point) {
            if ($prevPoint) {
                $distanceFromStart += JalanSegment::haversineDistance(
                    $prevPoint['lat'], $prevPoint['lng'],
                    $point['lat'], $point['lng']
                );
            }

            JalanSegment::create([
                JalanSegment::FIELD_JALAN_ID => $jalan->id,
                JalanSegment::FIELD_SEQUENCE => $index + 1,
                JalanSegment::FIELD_LATITUDE => $point['lat'],
                JalanSegment::FIELD_LONGITUDE => $point['lng'],
                JalanSegment::FIELD_DISTANCE_FROM_START => $distanceFromStart,
            ]);

            $this->segmentsImported++;
            $prevPoint = $point;
        }

        $this->streetsImported++;
    }

    /**
     * Calculate total length of polyline in meters
     */
    private function calculatePolylineLength(array $points): float
    {
        $totalLength = 0;
        $prevPoint = null;

        foreach ($points as $point) {
            if ($prevPoint) {
                $totalLength += JalanSegment::haversineDistance(
                    $prevPoint['lat'], $prevPoint['lng'],
                    $point['lat'], $point['lng']
                );
            }
            $prevPoint = $point;
        }

        return $totalLength;
    }

    /**
     * Import batch of POIs
     */
    private function importPoisBatch(array $batch): void
    {
        foreach ($batch as $poi) {
            try {
                $this->importPoi($poi);
            } catch (\Exception $e) {
                Log::warning("Failed to import POI {$poi['osm_id']}: " . $e->getMessage());
                $this->error("POI Error: " . $e->getMessage());
                $this->skipped++;
            }
        }
    }

    /**
     * Import single POI
     */
    private function importPoi(array $data): void
    {
        $coords = $data['coordinates'];

        // Check if already exists
        $existing = Poi::where(Poi::FIELD_OSM_ID, $data['osm_id'])->first();
        if ($existing) {
            $this->skipped++;
            return;
        }

        // Find kelurahan
        $kelurahanId = $this->findKelurahanByCoordinate($coords['lat'], $coords['lng']);

        // Determine category - check all OSM type fields
        $kategori = Poi::mapOsmTypeToKategori(
            $data['amenity'],
            $data['shop'],
            $data['place'] ?? null,
            $data['tourism'] ?? null,
            $data['leisure'] ?? null,
            $data['office'] ?? null,
            $data['healthcare'] ?? null,
            $data['building'] ?? null
        );

        // Clean and truncate address fields - OSM data can be inconsistent
        $addrStreet = $data['addr_street'];
        $addrHousenumber = $data['addr_housenumber'];
        $addrPostcode = $data['addr_postcode'];
        
        // If housenumber looks like full address, extract just the number
        if ($addrHousenumber && strlen($addrHousenumber) > 20) {
            // Try to extract just the number part
            if (preg_match('/(?:no\.?\s*)?(\d+[A-Za-z]?(?:\s*[-\/]\s*\d+[A-Za-z]?)?)/i', $addrHousenumber, $matches)) {
                $addrHousenumber = $matches[1];
            } else {
                $addrHousenumber = null; // Skip if can't extract
            }
        }

        Poi::create([
            Poi::FIELD_OSM_ID => $data['osm_id'],
            Poi::FIELD_NAMA => substr($data['name'], 0, 255),
            Poi::FIELD_NAMA_NORMALIZED => Poi::normalizeNama($data['name']),
            Poi::FIELD_KATEGORI => substr($kategori, 0, 50),
            Poi::FIELD_LATITUDE => $coords['lat'],
            Poi::FIELD_LONGITUDE => $coords['lng'],
            Poi::FIELD_ALAMAT_JALAN => $addrStreet ? substr($addrStreet, 0, 255) : null,
            Poi::FIELD_NOMOR_RUMAH => $addrHousenumber ? substr($addrHousenumber, 0, 20) : null,
            Poi::FIELD_KODE_POS => $addrPostcode ? substr($addrPostcode, 0, 10) : null,
            Poi::FIELD_KELURAHAN_ID => $kelurahanId,
            Poi::FIELD_IS_ACTIVE => true,
        ]);

        $this->poisImported++;
    }
}
