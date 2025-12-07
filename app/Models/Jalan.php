<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jalan extends Model
{
    use HasFactory;

    public const TABLE = 'jalans';
    public const FIELD_ID = 'id';
    public const FIELD_NAMA_JALAN = 'nama_jalan';
    public const FIELD_NAMA_NORMALIZED = 'nama_normalized';
    public const FIELD_KELURAHAN_ID = 'kelurahan_id';
    public const FIELD_LATITUDE = 'latitude';
    public const FIELD_LONGITUDE = 'longitude';
    public const FIELD_IS_ACTIVE = 'is_active';
    public const FIELD_SOURCE = 'source';
    public const FIELD_ACCURACY = 'accuracy';
    public const FIELD_CREATED_AT = 'created_at';
    public const FIELD_UPDATED_AT = 'updated_at';

    // Common Indonesian street name variations untuk fuzzy matching
    private static $streetNameVariations = [
        'ahmad' => ['achmad', 'ahmat', 'achmat'],
        'muhammad' => ['mohammad', 'mohamad', 'muh', 'moh', 'muhamad'],
        'soekarno' => ['sukarno'],
        'soepomo' => ['supomo'],
        'soetomo' => ['sutomo'],
        'diponegoro' => ['dipanegara', 'dipanegoro'],
        'sudirman' => ['soedirman'],
        'thamrin' => ['tamrin'],
        'hatta' => ['hata'],
        'veteran' => ['feteran'],
        'pahlawan' => ['phalawan'],
        'merdeka' => ['mardeka'],
        'gatot' => ['gatut'],
        'subroto' => ['subroto'],
        'basuki' => ['basuki'],
        'rahmat' => ['rachmat', 'rahmad', 'rachmat'],
    ];

    protected $table = self::TABLE;

    protected $fillable = [
        self::FIELD_NAMA_JALAN,
        self::FIELD_NAMA_NORMALIZED,
        self::FIELD_KELURAHAN_ID,
        self::FIELD_LATITUDE,
        self::FIELD_LONGITUDE,
        self::FIELD_IS_ACTIVE,
        self::FIELD_SOURCE,
        self::FIELD_ACCURACY,
    ];

    protected $casts = [
        self::FIELD_LATITUDE => 'decimal:8',
        self::FIELD_LONGITUDE => 'decimal:8',
        self::FIELD_IS_ACTIVE => 'boolean',
    ];

    /**
     * Relasi ke KelurahanCoordinate
     */
    public function kelurahan()
    {
        return $this->belongsTo(KelurahanCoordinate::class, self::FIELD_KELURAHAN_ID);
    }

    /**
     * Relasi ke Toko
     */
    public function tokos()
    {
        return $this->hasMany(Toko::class, Toko::FIELD_JALAN_ID, self::FIELD_ID);
    }

    /**
     * Scope untuk jalan aktif
     */
    public function scopeActive($query)
    {
        return $query->where(self::FIELD_IS_ACTIVE, true);
    }

    /**
     * Scope untuk filter by kelurahan
     */
    public function scopeByKelurahan($query, $kelurahanId)
    {
        return $query->where(self::FIELD_KELURAHAN_ID, $kelurahanId);
    }

    /**
     * Scope untuk search by nama dengan multiple pattern matching
     */
    public function scopeSearchByName($query, $keyword)
    {
        $normalized = self::normalizeNamaJalan($keyword);
        $simpleNormalized = strtolower(str_replace([' ', '_', '-', '.'], '', $keyword));
        $tokens = self::extractStreetTokens($keyword);

        return $query->where(function($q) use ($normalized, $simpleNormalized, $keyword, $tokens) {
            $q->where(self::FIELD_NAMA_NORMALIZED, 'like', "%{$normalized}%")
              ->orWhere(self::FIELD_NAMA_NORMALIZED, 'like', "%{$simpleNormalized}%")
              ->orWhere(self::FIELD_NAMA_JALAN, 'like', "%{$keyword}%");

            // Add token-based search for better matching
            foreach ($tokens as $token) {
                if (strlen($token) >= 3) {
                    $q->orWhere(self::FIELD_NAMA_NORMALIZED, 'like', "%{$token}%");
                }
            }
        });
    }

    /**
     * Scope untuk search by address (reverse like) dengan NLP parsing
     * Mencari jalan yang namanya ada di dalam alamat input
     */
    public function scopeSearchByAddress($query, $address)
    {
        $normalizedAddress = strtolower(str_replace([' ', '_', '-', '.'], '', $address));
        $streetName = self::extractStreetNameFromAddress($address);
        $normalizedStreet = self::normalizeNamaJalan($streetName);

        return $query->where(function($q) use ($normalizedAddress, $normalizedStreet, $streetName) {
            // Primary: match normalized address contains street name
            $q->whereRaw("? LIKE CONCAT('%', " . self::FIELD_NAMA_NORMALIZED . ", '%')", [$normalizedAddress]);

            // Secondary: match extracted street name
            if (!empty($normalizedStreet) && strlen($normalizedStreet) >= 3) {
                $q->orWhere(self::FIELD_NAMA_NORMALIZED, 'like', "%{$normalizedStreet}%");
            }

            // Tertiary: direct match on original name
            if (!empty($streetName)) {
                $q->orWhere(self::FIELD_NAMA_JALAN, 'like', "%{$streetName}%");
            }
        });
    }

    /**
     * Extract street name from full Indonesian address
     */
    public static function extractStreetNameFromAddress($address)
    {
        // Pattern untuk extract nama jalan dari alamat lengkap
        $patterns = [
            // "Jl. Ahmad Yani No. 20" -> "Ahmad Yani"
            '/(?:jalan|jl\.?)\s+([^,\d]+?)(?:\s+(?:no\.?|nomor)\s*\d+)?(?:,|$)/i',
            // "Gang Mawar No. 5" -> "Mawar"
            '/(?:gang|gg\.?)\s+([^,\d]+?)(?:\s+(?:no\.?|nomor)\s*\d+)?(?:,|$)/i',
            // "Jl. Veteran III" -> "Veteran III"
            '/(?:jalan|jl\.?)\s+([^,]+?)(?:,|$)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $address, $matches)) {
                return trim($matches[1]);
            }
        }

        // Fallback: ambil bagian pertama sebelum koma
        $parts = explode(',', $address);
        if (!empty($parts[0])) {
            $firstPart = trim($parts[0]);
            // Remove prefix jalan/gang
            return preg_replace('/^(?:jalan|jl\.?|gang|gg\.?)\s+/i', '', $firstPart);
        }

        return '';
    }

    /**
     * Extract meaningful tokens from address for fuzzy matching
     */
    public static function extractStreetTokens($input)
    {
        // Remove common prefixes dan noise words
        $cleaned = preg_replace('/^(jalan|jl\.?|gang|gg\.?)\s+/i', '', $input);
        $cleaned = preg_replace('/\b(no\.?|nomor|rt\.?|rw\.?|kec\.?|kecamatan|kel\.?|kelurahan|kota|kabupaten|kab\.?)\b/i', '', $cleaned);
        $cleaned = preg_replace('/\b(terusan|trs\.?|raya)\b/i', '', $cleaned);

        // Split dan filter tokens
        $tokens = preg_split('/[\s,.\-_\/]+/', strtolower($cleaned));
        $tokens = array_filter($tokens, function($token) {
            return strlen($token) >= 2 && !is_numeric($token);
        });

        return array_values(array_unique($tokens));
    }

    /**
     * Get coordinates as array
     */
    public function getCoordinatesAttribute()
    {
        return [
            'lat' => (float) $this->{self::FIELD_LATITUDE},
            'lng' => (float) $this->{self::FIELD_LONGITUDE},
        ];
    }

    /**
     * Get full location info
     */
    public function getFullLocationAttribute()
    {
        if ($this->kelurahan) {
            return "{$this->{self::FIELD_NAMA_JALAN}}, {$this->kelurahan->nama}, {$this->kelurahan->kecamatan}, {$this->kelurahan->kota}";
        }
        return $this->{self::FIELD_NAMA_JALAN};
    }

    /**
     * Normalize nama jalan untuk fuzzy matching (enhanced)
     */
    public static function normalizeNamaJalan($nama)
    {
        $normalized = $nama;

        // Remove prefixes
        $normalized = preg_replace('/^(jalan|jl\.?|gang|gg\.?)\s+/i', '', $normalized);

        // Remove common suffix words
        $normalized = preg_replace('/\b(terusan|trs\.?|raya|barat|timur|selatan|utara)\b/i', '', $normalized);

        // Remove roman numerals at the end (I, II, III, IV, V, etc.)
        $normalized = preg_replace('/\s+(I{1,3}|IV|V|VI{0,3}|IX|X)$/i', '', $normalized);

        // Lowercase and remove all spaces, dashes, underscores, dots
        $normalized = strtolower($normalized);
        $normalized = str_replace([' ', '_', '-', '.', "'", '"'], '', $normalized);

        return trim($normalized);
    }

    /**
     * Normalize dengan variasi nama Indonesia
     */
    public static function normalizeWithVariations($nama)
    {
        $normalized = self::normalizeNamaJalan($nama);

        // Apply common name variations
        foreach (self::$streetNameVariations as $standard => $variations) {
            if (str_contains($normalized, $standard)) {
                return $normalized;
            }
            foreach ($variations as $variant) {
                if (str_contains($normalized, $variant)) {
                    $normalized = str_replace($variant, $standard, $normalized);
                }
            }
        }

        return $normalized;
    }

    /**
     * Calculate Jaro-Winkler similarity - optimal untuk nama pendek/prefix
     */
    public static function jaroWinklerSimilarity($str1, $str2)
    {
        $str1 = strtolower($str1);
        $str2 = strtolower($str2);

        if ($str1 === $str2) return 100;

        $len1 = strlen($str1);
        $len2 = strlen($str2);

        if ($len1 === 0 || $len2 === 0) return 0;

        $matchDistance = (int) floor(max($len1, $len2) / 2) - 1;
        if ($matchDistance < 0) $matchDistance = 0;

        $str1Matches = array_fill(0, $len1, false);
        $str2Matches = array_fill(0, $len2, false);

        $matches = 0;
        $transpositions = 0;

        // Find matches
        for ($i = 0; $i < $len1; $i++) {
            $start = max(0, $i - $matchDistance);
            $end = min($i + $matchDistance + 1, $len2);

            for ($j = $start; $j < $end; $j++) {
                if ($str2Matches[$j] || $str1[$i] !== $str2[$j]) continue;
                $str1Matches[$i] = $str2Matches[$j] = true;
                $matches++;
                break;
            }
        }

        if ($matches === 0) return 0;

        // Count transpositions
        $k = 0;
        for ($i = 0; $i < $len1; $i++) {
            if (!$str1Matches[$i]) continue;
            while (!$str2Matches[$k]) $k++;
            if ($str1[$i] !== $str2[$k]) $transpositions++;
            $k++;
        }

        // Jaro similarity
        $jaro = ($matches / $len1 + $matches / $len2 + ($matches - $transpositions / 2) / $matches) / 3;

        // Prefix bonus (Winkler adjustment)
        $commonPrefix = 0;
        for ($i = 0; $i < min(4, min($len1, $len2)); $i++) {
            if ($str1[$i] === $str2[$i]) $commonPrefix++;
            else break;
        }

        $jaroWinkler = $jaro + ($commonPrefix * 0.1 * (1 - $jaro));
        return round($jaroWinkler * 100);
    }

    /**
     * Calculate token-based similarity untuk alamat panjang
     */
    public static function tokenBasedSimilarity($input, $target)
    {
        $inputTokens = self::extractStreetTokens($input);
        $targetTokens = self::extractStreetTokens($target);

        if (empty($inputTokens) || empty($targetTokens)) return 0;

        $matchedScore = 0;
        $totalWeight = 0;

        foreach ($inputTokens as $inputToken) {
            $bestMatch = 0;
            $tokenWeight = strlen($inputToken); // Longer tokens have more weight

            foreach ($targetTokens as $targetToken) {
                // Exact token match
                if ($inputToken === $targetToken) {
                    $bestMatch = 100;
                    break;
                }

                // Substring match
                if (str_contains($inputToken, $targetToken) || str_contains($targetToken, $inputToken)) {
                    $minLen = min(strlen($inputToken), strlen($targetToken));
                    $maxLen = max(strlen($inputToken), strlen($targetToken));
                    $substringScore = ($minLen / $maxLen) * 95;
                    $bestMatch = max($bestMatch, $substringScore);
                    continue;
                }

                // Jaro-Winkler for fuzzy match
                $jwScore = self::jaroWinklerSimilarity($inputToken, $targetToken);
                $bestMatch = max($bestMatch, $jwScore);
            }

            $matchedScore += $bestMatch * $tokenWeight;
            $totalWeight += $tokenWeight;
        }

        return $totalWeight > 0 ? round($matchedScore / $totalWeight) : 0;
    }

    /**
     * ADVANCED fuzzy match score - kombinasi multiple algorithms
     * Returns similarity score 0-100 dengan bonus untuk kelurahan match
     */
    public function advancedFuzzyMatchScore($input, $inputKelurahanId = null)
    {
        $inputNormalized = self::normalizeNamaJalan($input);
        $inputWithVariations = self::normalizeWithVariations($input);
        $thisNormalized = $this->{self::FIELD_NAMA_NORMALIZED};
        $thisWithVariations = self::normalizeWithVariations($this->{self::FIELD_NAMA_JALAN});

        $scores = [];

        // 1. Exact match (normalized) - 100 points
        if ($inputNormalized === $thisNormalized || $inputWithVariations === $thisWithVariations) {
            return 100;
        }

        // 2. Contains match - street name found in address
        if (strlen($thisNormalized) >= 3) {
            if (str_contains($inputNormalized, $thisNormalized)) {
                $ratio = strlen($thisNormalized) / max(strlen($inputNormalized), 1);
                // Bonus for longer street name matches
                $containsScore = 85 + ($ratio * 10);
                $scores[] = min(95, $containsScore);
            }
        }

        // 3. Extract street name from address and compare
        $extractedStreet = self::extractStreetNameFromAddress($input);
        if (!empty($extractedStreet)) {
            $extractedNormalized = self::normalizeNamaJalan($extractedStreet);

            // Exact match with extracted street
            if ($extractedNormalized === $thisNormalized) {
                $scores[] = 98;
            } else {
                // Jaro-Winkler on extracted street name
                $jwScore = self::jaroWinklerSimilarity($extractedNormalized, $thisNormalized);
                if ($jwScore >= 70) {
                    $scores[] = $jwScore;
                }
            }
        }

        // 4. Token-based matching
        $tokenScore = self::tokenBasedSimilarity($input, $this->{self::FIELD_NAMA_JALAN});
        if ($tokenScore >= 60) {
            $scores[] = $tokenScore;
        }

        // 5. Jaro-Winkler similarity (original)
        $jwOriginal = self::jaroWinklerSimilarity($inputNormalized, $thisNormalized);
        if ($jwOriginal >= 60) {
            $scores[] = $jwOriginal;
        }

        // 6. Levenshtein similarity as fallback
        $maxLen = max(strlen($inputNormalized), strlen($thisNormalized));
        if ($maxLen > 0) {
            $distance = levenshtein($inputNormalized, $thisNormalized);
            $levenshteinScore = round((($maxLen - $distance) / $maxLen) * 100);
            if ($levenshteinScore >= 50) {
                $scores[] = $levenshteinScore;
            }
        }

        // Calculate final score - weighted combination
        if (empty($scores)) {
            return 0;
        }

        // Use highest score as base, add bonus for multiple high scores
        rsort($scores);
        $baseScore = $scores[0];

        // Kelurahan context bonus (if kelurahan matches)
        $kelurahanBonus = 0;
        if ($inputKelurahanId !== null && $this->{self::FIELD_KELURAHAN_ID} == $inputKelurahanId) {
            $kelurahanBonus = 5; // Bonus for matching kelurahan context
        }

        return min(100, $baseScore + $kelurahanBonus);
    }

    /**
     * Legacy fuzzyMatchScore untuk backward compatibility
     */
    public function fuzzyMatchScore($input)
    {
        return $this->advancedFuzzyMatchScore($input);
    }

    /**
     * POWERFUL fuzzy search dengan NLP dan multi-algorithm matching
     */
    public static function fuzzySearch($keyword, $kelurahanId = null, $limit = 10, $minScore = 60)
    {
        $query = self::active()->with('kelurahan');

        // Extract street name dari alamat untuk pencarian lebih fokus
        $streetName = self::extractStreetNameFromAddress($keyword);
        $searchKeyword = !empty($streetName) ? $streetName : $keyword;

        // Jika ada kelurahanId, prioritaskan tapi jangan exclude yang lain
        if ($kelurahanId) {
            // Ambil kandidat dari kelurahan yang sama terlebih dahulu
            $candidatesFromKelurahan = self::active()
                ->with('kelurahan')
                ->where(self::FIELD_KELURAHAN_ID, $kelurahanId)
                ->limit(50)
                ->get();

            // Juga ambil kandidat global berdasarkan nama
            $candidatesGlobal = self::active()
                ->with('kelurahan')
                ->where(self::FIELD_KELURAHAN_ID, '!=', $kelurahanId)
                ->where(function($q) use ($searchKeyword, $keyword) {
                    $normalized = self::normalizeNamaJalan($searchKeyword);
                    $q->where(self::FIELD_NAMA_NORMALIZED, 'like', "%{$normalized}%")
                      ->orWhere(self::FIELD_NAMA_JALAN, 'like', "%{$searchKeyword}%");

                    // Also search by full keyword if different
                    if ($keyword !== $searchKeyword) {
                        $fullNormalized = self::normalizeNamaJalan($keyword);
                        $q->orWhere(self::FIELD_NAMA_NORMALIZED, 'like', "%{$fullNormalized}%");
                    }
                })
                ->limit(50)
                ->get();

            $candidates = $candidatesFromKelurahan->merge($candidatesGlobal)->unique('id');
        } else {
            // Jika keyword panjang (alamat), gunakan searchByAddress
            if (strlen($keyword) > 25) {
                $query->searchByAddress($keyword);
            } else {
                $query->searchByName($searchKeyword);
            }

            $candidates = $query->limit(150)->get();
        }

        // Score dan filter semua kandidat
        $results = $candidates->map(function($jalan) use ($keyword, $kelurahanId) {
            $score = $jalan->advancedFuzzyMatchScore($keyword, $kelurahanId);
            $jalan->match_score = $score;

            // Add match details untuk debugging
            $jalan->match_details = [
                'input_normalized' => self::normalizeNamaJalan($keyword),
                'street_normalized' => $jalan->{self::FIELD_NAMA_NORMALIZED},
                'kelurahan_match' => $kelurahanId && $jalan->{self::FIELD_KELURAHAN_ID} == $kelurahanId,
            ];

            return $jalan;
        })->filter(function($jalan) use ($minScore) {
            return $jalan->match_score >= $minScore;
        })->sortByDesc(function($jalan) use ($kelurahanId) {
            // Sort by score, with kelurahan match as tiebreaker
            $kelurahanBonus = ($kelurahanId && $jalan->{self::FIELD_KELURAHAN_ID} == $kelurahanId) ? 0.5 : 0;
            return $jalan->match_score + $kelurahanBonus;
        })->take($limit);

        return $results->values();
    }

    /**
     * Smart search - mencari jalan yang paling cocok untuk alamat
     * Returns single best match or null
     */
    public static function findBestMatch($address, $kelurahanId = null, $minScore = 75)
    {
        $results = self::fuzzySearch($address, $kelurahanId, 1, $minScore);
        return $results->first();
    }
}
