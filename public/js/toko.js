$(document).ready(function () {
    // ========================================
    // INITIALIZATION
    // ========================================
    console.log('Loading Smart Address Parsing with Kelurahan Detection...');

    // Global variables for map
    let interactiveMap = null;
    let currentMarker = null;
    let previewMarker = null;
    let isMapInitialized = false;
    let searchTimeout = null;

    // Tolerance tracking for 50m validation
    let initialGeocodedPosition = null;
    const MAX_TOLERANCE_METERS = 50;

    // Malang Region Bounds
    const MALANG_BOUNDS = {
        north: -7.4,
        south: -8.6,
        east: 113.2,
        west: 111.8
    };

    // Default center (Malang city center)
    const MALANG_CENTER = [-7.9666, 112.6326];

    // Kelurahan database - loaded from API
    let KELURAHAN_DATABASE = {};
    let isKelurahanDataLoaded = false;

    // Load kelurahan coordinates from API
    loadKelurahanCoordinates();
    loadTokoData();

    // ========================================
    // ADVANCED FUZZY MATCHING ALGORITHMS
    // ========================================

    /**
     * Calculate Levenshtein distance between two strings (Simple version)
     * Returns the minimum number of edits needed to transform one string into another
     * Used for basic fuzzy matching in advanced algorithms
     */
    function levenshteinDistanceSimple(str1, str2) {
        const len1 = str1.length;
        const len2 = str2.length;
        const matrix = [];

        for (let i = 0; i <= len1; i++) matrix[i] = [i];
        for (let j = 0; j <= len2; j++) matrix[0][j] = j;

        for (let i = 1; i <= len1; i++) {
            for (let j = 1; j <= len2; j++) {
                if (str1[i - 1] === str2[j - 1]) {
                    matrix[i][j] = matrix[i - 1][j - 1];
                } else {
                    matrix[i][j] = Math.min(
                        matrix[i - 1][j - 1] + 1, // substitution
                        matrix[i][j - 1] + 1,     // insertion
                        matrix[i - 1][j] + 1      // deletion
                    );
                }
            }
        }
        return matrix[len1][len2];
    }

    /**
     * Calculate similarity percentage based on Levenshtein distance
     */
    function levenshteinSimilarity(str1, str2) {
        const maxLen = Math.max(str1.length, str2.length);
        if (maxLen === 0) return 100;
        const distance = levenshteinDistanceSimple(str1.toLowerCase(), str2.toLowerCase());
        return Math.round(((maxLen - distance) / maxLen) * 100);
    }

    /**
     * Calculate Jaro-Winkler similarity (better for short strings and prefix matching)
     */
    function jaroWinklerSimilarity(str1, str2) {
        str1 = str1.toLowerCase();
        str2 = str2.toLowerCase();
        if (str1 === str2) return 100;

        const len1 = str1.length;
        const len2 = str2.length;
        const matchDistance = Math.floor(Math.max(len1, len2) / 2) - 1;

        const str1Matches = new Array(len1).fill(false);
        const str2Matches = new Array(len2).fill(false);

        let matches = 0;
        let transpositions = 0;

        // Find matches
        for (let i = 0; i < len1; i++) {
            const start = Math.max(0, i - matchDistance);
            const end = Math.min(i + matchDistance + 1, len2);
            for (let j = start; j < end; j++) {
                if (str2Matches[j] || str1[i] !== str2[j]) continue;
                str1Matches[i] = str2Matches[j] = true;
                matches++;
                break;
            }
        }

        if (matches === 0) return 0;

        // Count transpositions
        let k = 0;
        for (let i = 0; i < len1; i++) {
            if (!str1Matches[i]) continue;
            while (!str2Matches[k]) k++;
            if (str1[i] !== str2[k]) transpositions++;
            k++;
        }

        // Jaro similarity
        const jaro = (matches / len1 + matches / len2 + (matches - transpositions / 2) / matches) / 3;

        // Prefix bonus
        let commonPrefix = 0;
        for (let i = 0; i < Math.min(4, Math.min(str1.length, str2.length)); i++) {
            if (str1[i] === str2[i]) commonPrefix++;
            else break;
        }

        const jaroWinkler = jaro + (commonPrefix * 0.1 * (1 - jaro));
        return Math.round(jaroWinkler * 100);
    }

    /**
     * Indonesian street/place name variations mapping
     * Handles common spelling variations in Indonesian names
     */
    const INDONESIAN_NAME_VARIATIONS = {
        'ahmad': ['achmad', 'ahmat', 'achmat'],
        'muhammad': ['mohammad', 'mohamad', 'muh', 'moh', 'muhamad'],
        'soekarno': ['sukarno'],
        'soepomo': ['supomo'],
        'soetomo': ['sutomo'],
        'diponegoro': ['dipanegara', 'dipanegoro'],
        'sudirman': ['soedirman'],
        'thamrin': ['tamrin'],
        'hatta': ['hata'],
        'veteran': ['feteran'],
        'pahlawan': ['phalawan'],
        'merdeka': ['mardeka'],
        'gatot': ['gatut'],
        'subroto': ['subroto'],
        'basuki': ['basuki'],
        'rahmat': ['rachmat', 'rahmad', 'rachmat'],
    };

    /**
     * Normalize Indonesian name with variations
     */
    function normalizeIndonesianName(name) {
        let normalized = name.toLowerCase().trim();
        normalized = normalized.replace(/[^a-z0-9\s]/g, '');
        normalized = normalized.replace(/\s+/g, '');

        // Apply variations
        for (const [standard, variants] of Object.entries(INDONESIAN_NAME_VARIATIONS)) {
            for (const variant of variants) {
                if (normalized.includes(variant)) {
                    normalized = normalized.replace(variant, standard);
                }
            }
        }

        return normalized;
    }

    /**
     * Extract street name from full address
     */
    function extractStreetName(address) {
        if (!address) return '';

        const patterns = [
            /(?:jalan|jl\.?)\s+([^,\d]+?)(?:\s+(?:no\.?|nomor)\s*\d+)?(?:,|$)/i,
            /(?:gang|gg\.?)\s+([^,\d]+?)(?:\s+(?:no\.?|nomor)\s*\d+)?(?:,|$)/i,
        ];

        for (const pattern of patterns) {
            const match = address.match(pattern);
            if (match && match[1]) {
                return match[1].trim();
            }
        }

        // Fallback: get first part before comma
        const parts = address.split(',');
        if (parts[0]) {
            return parts[0].replace(/^(?:jalan|jl\.?|gang|gg\.?)\s+/i, '').trim();
        }

        return '';
    }

    /**
     * Extract meaningful tokens from address
     */
    function extractAddressTokens(input) {
        // Remove common prefixes and noise words
        let cleaned = input.toLowerCase();
        cleaned = cleaned.replace(/^(jalan|jl\.?|gang|gg\.?)\s+/i, '');
        cleaned = cleaned.replace(/\b(no\.?|nomor|rt\.?|rw\.?|kec\.?|kecamatan|kel\.?|kelurahan|kota|kabupaten|kab\.?)\b/gi, '');
        cleaned = cleaned.replace(/\b(terusan|trs\.?|raya|barat|timur|selatan|utara)\b/gi, '');

        // Split and filter tokens
        const tokens = cleaned.split(/[\s,.\-_\/]+/).filter(token => {
            return token.length >= 2 && !/^\d+$/.test(token);
        });

        return [...new Set(tokens)];
    }

    /**
     * Token-based similarity for long addresses
     */
    function tokenBasedSimilarity(input, target) {
        const inputTokens = extractAddressTokens(input);
        const targetTokens = extractAddressTokens(target);

        if (inputTokens.length === 0 || targetTokens.length === 0) return 0;

        let matchedScore = 0;
        let totalWeight = 0;

        for (const inputToken of inputTokens) {
            let bestMatch = 0;
            const tokenWeight = inputToken.length; // Longer tokens have more weight

            for (const targetToken of targetTokens) {
                // Exact token match
                if (inputToken === targetToken) {
                    bestMatch = 100;
                    break;
                }

                // Substring match
                if (inputToken.includes(targetToken) || targetToken.includes(inputToken)) {
                    const minLen = Math.min(inputToken.length, targetToken.length);
                    const maxLen = Math.max(inputToken.length, targetToken.length);
                    const substringScore = (minLen / maxLen) * 95;
                    bestMatch = Math.max(bestMatch, substringScore);
                    continue;
                }

                // Jaro-Winkler for fuzzy match
                const jwScore = jaroWinklerSimilarity(inputToken, targetToken);
                bestMatch = Math.max(bestMatch, jwScore);
            }

            matchedScore += bestMatch * tokenWeight;
            totalWeight += tokenWeight;
        }

        return totalWeight > 0 ? Math.round(matchedScore / totalWeight) : 0;
    }

    /**
     * POWERFUL Advanced fuzzy matching combining multiple algorithms
     * Uses: Token-based + Jaro-Winkler + Levenshtein + Indonesian name variations
     */
    function advancedFuzzyMatch(str1, str2) {
        const normalized1 = str1.toLowerCase().trim();
        const normalized2 = str2.toLowerCase().trim();
        if (normalized1 === normalized2) return 100;

        const scores = [];

        // 1. Basic Levenshtein + Jaro-Winkler (original algorithm)
        const levenshtein = levenshteinSimilarity(normalized1, normalized2);
        const jaroWinkler = jaroWinklerSimilarity(normalized1, normalized2);
        const basicScore = Math.round((jaroWinkler * 0.6) + (levenshtein * 0.4));
        if (basicScore >= 50) scores.push(basicScore);

        // 2. Normalized with Indonesian variations
        const normalizedVar1 = normalizeIndonesianName(str1);
        const normalizedVar2 = normalizeIndonesianName(str2);
        if (normalizedVar1 === normalizedVar2) return 100;

        const varLevenshtein = levenshteinSimilarity(normalizedVar1, normalizedVar2);
        const varJaroWinkler = jaroWinklerSimilarity(normalizedVar1, normalizedVar2);
        const varScore = Math.round((varJaroWinkler * 0.6) + (varLevenshtein * 0.4));
        if (varScore >= 50) scores.push(varScore);

        // 3. Token-based matching for longer strings
        if (str1.length > 15 || str2.length > 15) {
            const tokenScore = tokenBasedSimilarity(str1, str2);
            if (tokenScore >= 50) scores.push(tokenScore);
        }

        // 4. Contains check
        const cleanStr1 = normalized1.replace(/[^a-z0-9]/g, '');
        const cleanStr2 = normalized2.replace(/[^a-z0-9]/g, '');
        if (cleanStr1.includes(cleanStr2) || cleanStr2.includes(cleanStr1)) {
            const minLen = Math.min(cleanStr1.length, cleanStr2.length);
            const maxLen = Math.max(cleanStr1.length, cleanStr2.length);
            if (minLen >= 3) {
                const containsScore = 70 + (minLen / maxLen * 25);
                scores.push(Math.round(containsScore));
            }
        }

        // 5. Extract street name and compare
        const street1 = extractStreetName(str1);
        const street2 = extractStreetName(str2);
        if (street1 && street2) {
            const streetNorm1 = normalizeIndonesianName(street1);
            const streetNorm2 = normalizeIndonesianName(street2);
            if (streetNorm1 === streetNorm2) {
                scores.push(95);
            } else {
                const streetJW = jaroWinklerSimilarity(streetNorm1, streetNorm2);
                if (streetJW >= 70) scores.push(streetJW);
            }
        }

        // Return highest score
        if (scores.length === 0) return 0;
        return Math.max(...scores);
    }

    // ========================================
    // KELURAHAN DATA LOADER (FROM API)
    // ========================================

    function loadKelurahanCoordinates() {
        console.log('📡 [KELURAHAN DB] Loading kelurahan coordinates from API...');
        const startTime = performance.now();

        $.ajax({
            url: '/toko/kelurahan-coordinates',
            type: 'GET',
            cache: true, // Cache untuk performa
            success: function (response) {
                const loadTime = (performance.now() - startTime).toFixed(2);

                if (response.kelurahan_database) {
                    KELURAHAN_DATABASE = response.kelurahan_database;
                    isKelurahanDataLoaded = true;

                    console.log(`✅ [KELURAHAN DB] Successfully loaded ${response.total} kelurahan coordinates`);
                    console.log(`⏱️  [KELURAHAN DB] Load time: ${loadTime}ms`);
                    console.log(`📊 [KELURAHAN DB] Database size: ${Object.keys(KELURAHAN_DATABASE).length} entries`);
                    console.log(`💾 [KELURAHAN DB] Cache status: Enabled`);
                } else {
                    console.warn('⚠️  [KELURAHAN DB] Unexpected data format, using empty database');
                    console.warn('📋 [KELURAHAN DB] Response structure:', response);
                    KELURAHAN_DATABASE = {};
                    isKelurahanDataLoaded = true;
                }
            },
            error: function (xhr, status, error) {
                const loadTime = (performance.now() - startTime).toFixed(2);

                console.error('❌ [KELURAHAN DB] Failed to load kelurahan coordinates');
                console.error(`⏱️  [KELURAHAN DB] Failed after: ${loadTime}ms`);
                console.error(`🔍 [KELURAHAN DB] Status: ${status}`);
                console.error(`📝 [KELURAHAN DB] Error: ${error}`);
                console.error(`📄 [KELURAHAN DB] Response: ${xhr.responseText}`);
                console.warn('⚠️  [KELURAHAN DB] Smart address parsing will be limited without kelurahan data');

                KELURAHAN_DATABASE = {};
                isKelurahanDataLoaded = true;
            }
        });
    }

    // ========================================
    // SMART ADDRESS PARSING & KELURAHAN DETECTION
    // ========================================

    /**
     * Parse Indonesian standard address format with enhanced NLP
     * Format: Jl. [nama jalan] No. [nomor], RT/RW, [Kelurahan], Kec. [Kecamatan], Kota [Kota], [Provinsi] [Kode Pos]
     * 
     * @param {string} alamat - Full address string
     * @returns {object} Parsed address components
     */
    function parseIndonesianAddress(alamat) {
        if (!alamat || typeof alamat !== 'string') {
            return {
                street: '',
                streetNumber: '',
                rtRw: '',
                building: '',
                kelurahan: '',
                kecamatan: '',
                kota: '',
                provinsi: '',
                postalCode: ''
            };
        }

        const result = {
            street: '',
            streetNumber: '',
            rtRw: '',
            building: '',
            kelurahan: '',
            kecamatan: '',
            kota: '',
            provinsi: '',
            postalCode: ''
        };

        // Pre-processing: normalize common variations
        let processedAddress = alamat
            .replace(/\bJln\b/gi, 'Jalan')
            .replace(/\bJl\b(?!\.)/gi, 'Jl.')
            .replace(/\bGg\b(?!\.)/gi, 'Gg.')
            .replace(/\bNo\b(?!\.)/gi, 'No.')
            .replace(/\bKec\b(?!\.)/gi, 'Kec.')
            .replace(/\bKel\b(?!\.)/gi, 'Kel.')
            .replace(/\bKab\b(?!\.)/gi, 'Kab.');

        // Extract RT/RW first (before splitting by comma)
        const rtRwMatch = processedAddress.match(/\b(RT\.?\s*\/?\s*RW\.?\s*\d+\s*\/?\s*\d+|RT\.?\s*\d+\s*\/?\s*RW\.?\s*\d+|RT\.?\s*\d+|RW\.?\s*\d+)\b/i);
        if (rtRwMatch) {
            result.rtRw = rtRwMatch[0].trim();
            // Remove RT/RW from address for cleaner parsing
            processedAddress = processedAddress.replace(rtRwMatch[0], '').replace(/\s+/g, ' ').trim();
        }

        // Extract building/complex/apartment names
        const buildingPatterns = [
            /\b(Perumahan|Perum|Komplek|Kompleks|Komp\.|Apartemen|Apt\.|Ruko|Gedung|Gd\.)\s+([^,]+)/gi,
            /\b(Cluster|Blok)\s+([A-Z0-9]+)/gi
        ];

        buildingPatterns.forEach(pattern => {
            const match = processedAddress.match(pattern);
            if (match && !result.building) {
                result.building = match[0].trim();
            }
        });

        // Split by comma separator
        const parts = processedAddress.split(',').map(part => part.trim()).filter(part => part.length > 0);

        if (parts.length === 0) {
            return result;
        }

        // Enhanced pattern matching for each part
        parts.forEach((part, index) => {
            const partLower = part.toLowerCase();

            // 1. STREET DETECTION (Enhanced)
            if (!result.street) {
                // Match street patterns with number
                const streetWithNumber = part.match(/^(Jl\.|Jalan|Gg\.|Gang)\s+(.+?)(?:\s+No\.?\s*(\d+[A-Za-z]?))?$/i);
                if (streetWithNumber) {
                    result.street = streetWithNumber[1] + ' ' + streetWithNumber[2].trim();
                    if (streetWithNumber[3]) {
                        result.streetNumber = streetWithNumber[3];
                    }
                    return;
                }

                // First part is usually street if it contains street indicators
                if (index === 0 || partLower.match(/\b(jl\.|jalan|gang|gg\.)\b/i)) {
                    result.street = part;

                    // Extract street number if present
                    const numberMatch = part.match(/\bNo\.?\s*(\d+[A-Za-z]?)\b/i);
                    if (numberMatch) {
                        result.streetNumber = numberMatch[1];
                    }
                    return;
                }
            }

            // 2. KECAMATAN DETECTION (Enhanced)
            if (partLower.match(/\b(kec\.|kecamatan)\b/i)) {
                result.kecamatan = part
                    .replace(/\bkec\.\s*/i, '')
                    .replace(/\bkecamatan\s*/i, '')
                    .trim();
                return;
            }

            // 3. KOTA/KABUPATEN DETECTION (Enhanced)
            if (partLower.match(/\b(kota|kabupaten|kab\.)\b/i)) {
                result.kota = part.trim();
                return;
            }

            // 4. PROVINSI DETECTION
            if (partLower.match(/\b(jawa timur|jawa tengah|jawa barat|east java|jatim)\b/i)) {
                result.provinsi = part.trim();
                return;
            }

            // 5. POSTAL CODE DETECTION
            if (partLower.match(/\b\d{5}\b/)) {
                result.postalCode = part.match(/\d{5}/)[0];
                return;
            }

            // 6. KELURAHAN DETECTION (Enhanced with multiple strategies)
            if (!result.kelurahan) {
                // Strategy 1: Explicit kelurahan prefix
                if (partLower.match(/\b(kel\.|kelurahan|desa)\b/i)) {
                    result.kelurahan = part
                        .replace(/\bkel\.\s*/i, '')
                        .replace(/\bkelurahan\s*/i, '')
                        .replace(/\bdesa\s*/i, '')
                        .trim();
                    return;
                }

                // Strategy 2: Position-based detection (after street, before kecamatan)
                // Kelurahan is typically in position 1 or 2 (after street)
                if (index >= 1 && index <= 2 && !partLower.match(/\b(kec\.|kecamatan|kota|kabupaten|rt|rw|jl\.|jalan|gang|no\.|perumahan|komplek)\b/i)) {
                    // Additional validation: check if it's not a building/complex name
                    if (!result.building || !part.includes(result.building)) {
                        result.kelurahan = part;
                        return;
                    }
                }

                // Strategy 3: Fallback - any unmatched part that doesn't contain keywords
                if (index > 0 &&
                    !partLower.match(/\b(jl\.|jalan|gang|no\.|kec\.|kecamatan|kota|kabupaten|kab\.|rt|rw|perumahan|komplek|cluster|blok)\b/i)) {
                    result.kelurahan = part;
                }
            }
        });

        // Post-processing: Clean up and normalize
        Object.keys(result).forEach(key => {
            if (result[key]) {
                result[key] = normalizeAddressComponent(result[key]);
            }
        });

        // Enhanced logging with more details
        console.log('📍 [ADDRESS PARSER] Enhanced parsing result:');
        console.log('   🏠 Street:', result.street || '(not detected)');
        console.log('   🔢 Street Number:', result.streetNumber || '(not detected)');
        console.log('   📍 RT/RW:', result.rtRw || '(not detected)');
        console.log('   🏢 Building/Complex:', result.building || '(not detected)');
        console.log('   🏘️  Kelurahan:', result.kelurahan || '(not detected)');
        console.log('   🏙️  Kecamatan:', result.kecamatan || '(not detected)');
        console.log('   🌆 Kota:', result.kota || '(not detected)');
        console.log('   🗺️  Provinsi:', result.provinsi || '(not detected)');
        console.log('   📮 Postal Code:', result.postalCode || '(not detected)');

        return result;
    }

    /**
     * Normalize address component by cleaning text with enhanced rules
     * 
     * @param {string} component - Address component to normalize
     * @returns {string} Normalized component
     */
    function normalizeAddressComponent(component) {
        if (!component || typeof component !== 'string') {
            return '';
        }

        let normalized = component;

        // Remove extra spaces
        normalized = normalized.replace(/\s+/g, ' ').trim();

        // Remove special characters at start/end
        normalized = normalized.replace(/^[,.\-_\s]+|[,.\-_\s]+$/g, '');

        // Normalize common abbreviations (preserve dots for proper nouns)
        normalized = normalized
            .replace(/\bJln\.?\s*/gi, 'Jalan ')
            .replace(/\bJl\.?\s*/gi, 'Jalan ')
            .replace(/\bGg\.?\s*/gi, 'Gang ')
            .replace(/\bNo\.?\s*/gi, 'Nomor ')
            .replace(/\bKec\.?\s*/gi, 'Kecamatan ')
            .replace(/\bKel\.?\s*/gi, 'Kelurahan ')
            .replace(/\bKab\.?\s*/gi, 'Kabupaten ')
            .replace(/\bPerum\.?\s*/gi, 'Perumahan ')
            .replace(/\bKomp\.?\s*/gi, 'Komplek ')
            .replace(/\bApt\.?\s*/gi, 'Apartemen ')
            .replace(/\bGd\.?\s*/gi, 'Gedung ');

        // Remove duplicate spaces again after replacements
        normalized = normalized.replace(/\s+/g, ' ').trim();

        // Capitalize first letter of each word for proper nouns
        normalized = normalized.replace(/\b\w/g, char => char.toUpperCase());

        return normalized;
    }

    /**
     * Validate address format completeness
     * 
     * @param {object} parsedAddress - Parsed address object from parseIndonesianAddress()
     * @returns {object} Validation result with status and missing fields
     */
    function validateAddressFormat(parsedAddress) {
        const validation = {
            isValid: true,
            isComplete: true,
            missingFields: [],
            warnings: []
        };

        // Check required fields
        if (!parsedAddress.street || parsedAddress.street.length < 3) {
            validation.isValid = false;
            validation.missingFields.push('street');
        }

        // Check recommended fields for better geocoding
        if (!parsedAddress.kelurahan) {
            validation.isComplete = false;
            validation.warnings.push('Kelurahan tidak terdeteksi - geocoding mungkin kurang akurat');
        }

        if (!parsedAddress.kecamatan) {
            validation.isComplete = false;
            validation.warnings.push('Kecamatan tidak terdeteksi');
        }

        if (!parsedAddress.kota) {
            validation.isComplete = false;
            validation.warnings.push('Kota/Kabupaten tidak terdeteksi');
        }

        // Check for common format issues
        if (parsedAddress.street && !parsedAddress.street.match(/\b(jalan|jl|gang|gg|no|nomor)\b/i)) {
            validation.warnings.push('Format jalan mungkin tidak standar');
        }

        return validation;
    }

    // Event listener untuk alamat field - Smart parsing dengan kelurahan detection
    $(document).on('input', '#alamat', function () {
        const alamat = $(this).val().trim();

        // Clear timeout sebelumnya
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }

        // Jika alamat kurang dari 8 karakter, jangan proses
        if (alamat.length < 8) {
            clearPreviewMarker();
            $('#addressSearchStatus').hide();
            return;
        }

        // Debounce parsing - tunggu 1 detik setelah user berhenti mengetik
        searchTimeout = setTimeout(function () {
            performSmartAddressParsing(alamat);
        }, 1000);
    });

    function performSmartAddressParsing(alamat) {
        console.log('Smart parsing Indonesian standard address format:', alamat);
        console.log('Expected format: Jl. [nama jalan] No. [nomor], [Kelurahan], Kec. [Kecamatan], Kota [Kota], [Provinsi] [Kode Pos]');

        // Check if kelurahan data is loaded
        if (!isKelurahanDataLoaded) {
            console.warn('⏳ Kelurahan data still loading, retrying in 500ms...');
            setTimeout(() => performSmartAddressParsing(alamat), 500);
            return;
        }

        if (Object.keys(KELURAHAN_DATABASE).length === 0) {
            console.warn('⚠️ Kelurahan database is empty, performing basic geocoding only');
            performEnhancedGeocoding(alamat, null);
            return;
        }

        // Step 0: Parse Indonesian address format
        const parsedAddress = parseIndonesianAddress(alamat);
        const validation = validateAddressFormat(parsedAddress);

        // Log validation results
        if (!validation.isComplete) {
            console.warn('⚠️ Address format incomplete:', validation.warnings);
        }

        // Step 1: Deteksi kelurahan dari alamat dengan format standar Indonesia
        const detectedKelurahan = detectKelurahanFromAddress(alamat);

        if (detectedKelurahan) {
            // Step 2: Auto zoom ke kelurahan yang terdeteksi
            let message = `Kelurahan "${detectedKelurahan.name}" terdeteksi dari alamat!`;
            if (detectedKelurahan.method) {
                message += ` (${detectedKelurahan.method})`;
            }
            message += ' Peta zoom ke area tersebut.';

            showSearchStatus('success', message);
            zoomToDetectedKelurahan(detectedKelurahan);

            // Step 3: Lakukan deteksi jalan (Async)
            detectJalanFromAddress(alamat, detectedKelurahan.data.id, function (detectedJalan) {
                if (detectedJalan) {
                    let streetMessage = `Jalan "${detectedJalan.nama_jalan}" terdeteksi!`;
                    showSearchStatus('success', streetMessage);

                    // Use street coordinates
                    const streetLocation = {
                        lat: parseFloat(detectedJalan.latitude),
                        lng: parseFloat(detectedJalan.longitude)
                    };

                    updateMarkerPosition(streetLocation.lat, streetLocation.lng);
                    map.setView([streetLocation.lat, streetLocation.lng], 17);

                    // Fill form data if needed
                    // ...

                    // Perform enhanced geocoding as validation/backup
                    setTimeout(() => {
                        performEnhancedGeocoding(alamat, detectedKelurahan);
                    }, 1000);
                } else {
                    // Fallback to standard geocoding if street not found
                    setTimeout(() => {
                        performEnhancedGeocoding(alamat, detectedKelurahan);
                    }, 1500);
                }
            });
        } else {
            // Fallback: Try to use parsed address components (Kota & Kecamatan)
            if (parsedAddress.kota && parsedAddress.kecamatan) {
                console.log('⚠️ Kelurahan not detected, but Kota and Kecamatan found in address.');
                let message = `Kecamatan "${parsedAddress.kecamatan}" terdeteksi.`;
                showSearchStatus('info', message);

                // Auto-fill Kota and Kecamatan
                setWilayahFromDetection(parsedAddress.kota, parsedAddress.kecamatan, parsedAddress.kelurahan);
            } else {
                showSearchStatus('info', 'Kelurahan tidak terdeteksi. Silakan ikuti format: Jl. [nama], [Kelurahan], Kec. [Kecamatan], Kota [Kota]. Mencari lokasi secara umum...');
            }

            // Fallback: Geocoding biasa tanpa kelurahan detection
            performEnhancedGeocoding(alamat, null);
        }
    }

    /**
     * Detect kelurahan from address using multiple matching strategies
     * Implements 5 detection methods with confidence scoring
     * 
     * @param {string} alamat - Full address string
     * @returns {object|null} Best match with confidence score >= 65, or null
     */
    function detectKelurahanFromAddress(alamat) {
        if (!alamat || typeof alamat !== 'string') {
            console.warn('⚠️ Invalid address input for kelurahan detection');
            return null;
        }

        const alamatLower = alamat.toLowerCase();
        console.log('🔍 Detecting kelurahan from address:', alamatLower);

        // Check if kelurahan database is loaded
        if (!KELURAHAN_DATABASE || Object.keys(KELURAHAN_DATABASE).length === 0) {
            console.warn('⚠️ Kelurahan database not loaded');
            return null;
        }

        let bestMatch = null;
        let bestScore = 0;

        // Parse alamat dengan format standar Indonesia
        const parsedAddress = parseIndonesianAddress(alamat);
        console.log('📋 Parsed address components:', parsedAddress);

        // Split address by comma for standard format detection
        const addressParts = alamat.split(',').map(part => part.trim());

        // Iterate through all kelurahan in database
        Object.keys(KELURAHAN_DATABASE).forEach(kelurahanKey => {
            const kelurahanData = KELURAHAN_DATABASE[kelurahanKey];
            const kelurahanName = kelurahanKey.replace(/_/g, ' ');
            const kelurahanNameLower = kelurahanName.toLowerCase();

            // ========================================
            // METHOD 1: STANDARD FORMAT EXACT MATCH
            // Score: 100 (Perfect match)
            // ========================================
            if (addressParts.length >= 2) {
                const kelurahanPart = addressParts[1].toLowerCase().trim();

                // Perfect exact match in standard position
                if (kelurahanPart === kelurahanNameLower) {
                    bestMatch = {
                        key: kelurahanKey,
                        name: kelurahanName,
                        data: kelurahanData,
                        score: 100,
                        method: 'standard_format_exact'
                    };
                    console.log('✅ EXACT MATCH found:', bestMatch);
                    return false; // Break forEach - perfect match found
                }

                // ========================================
                // METHOD 2: STANDARD FORMAT PARTIAL MATCH
                // Score: 85-98 (High confidence with Levenshtein)
                // ========================================
                if (kelurahanPart.includes(kelurahanNameLower) ||
                    kelurahanNameLower.includes(kelurahanPart)) {
                    const score = calculateStandardFormatScore(kelurahanPart, kelurahanNameLower);
                    if (score > bestScore) {
                        bestScore = score;
                        bestMatch = {
                            key: kelurahanKey,
                            name: kelurahanName,
                            data: kelurahanData,
                            score: score,
                            method: 'standard_format_partial'
                        };
                    }
                }
            }

            // ========================================
            // METHOD 3: PREFIX MATCH
            // Score: 95 (Explicit kelurahan prefix)
            // ========================================
            const prefixPatterns = [
                'kelurahan ' + kelurahanNameLower,
                'kel. ' + kelurahanNameLower,
                'kel ' + kelurahanNameLower
            ];

            for (const pattern of prefixPatterns) {
                if (alamatLower.includes(pattern)) {
                    const score = 95;
                    if (score > bestScore) {
                        bestScore = score;
                        bestMatch = {
                            key: kelurahanKey,
                            name: kelurahanName,
                            data: kelurahanData,
                            score: score,
                            method: 'prefix_match'
                        };
                    }
                    break;
                }
            }

            // ========================================
            // METHOD 4: CONTEXTUAL MATCH
            // Score: 55-100 (Position and context analysis)
            // ========================================
            if (alamatLower.includes(kelurahanNameLower)) {
                const score = calculateContextualScore(alamatLower, kelurahanNameLower);
                if (score > bestScore && score >= 55) {
                    bestScore = score;
                    bestMatch = {
                        key: kelurahanKey,
                        name: kelurahanName,
                        data: kelurahanData,
                        score: score,
                        method: 'contextual_match'
                    };
                }
            }

            // ========================================
            // METHOD 5: VARIATION MATCH
            // Score: 60-90 (Handle spaces, underscores, dashes)
            // ========================================
            const variations = [
                kelurahanName.replace(/\s+/g, ''),           // Remove all spaces
                kelurahanKey,                                 // Original key (with underscores)
                kelurahanName.replace(/\s+/g, '_'),          // Spaces to underscores
                kelurahanName.replace(/\s+/g, '-'),          // Spaces to dashes
                kelurahanName.replace(/[\s\-_]/g, '')        // Remove all separators
            ];

            variations.forEach(variation => {
                const variationLower = variation.toLowerCase();
                if (alamatLower.includes(variationLower)) {
                    const score = calculateVariationScore(alamatLower, variationLower, kelurahanNameLower);
                    if (score > bestScore && score >= 60) {
                        bestScore = score;
                        bestMatch = {
                            key: kelurahanKey,
                            name: kelurahanName,
                            data: kelurahanData,
                            score: score,
                            method: 'variation_match'
                        };
                    }
                }
            });

            // ========================================
            // METHOD 6: ADVANCED FUZZY MATCHING
            // Score: 70-92 (String similarity algorithms)
            // Uses Levenshtein + Jaro-Winkler for typo tolerance
            // ========================================

            // Try fuzzy matching if no high-confidence match yet
            if (bestScore < 90) {
                const fuzzyScore = advancedFuzzyMatch(kelurahanNameLower, alamatLower);

                if (fuzzyScore >= 70) {
                    const finalScore = Math.min(92, fuzzyScore);

                    if (finalScore > bestScore) {
                        bestScore = finalScore;
                        bestMatch = {
                            key: kelurahanKey,
                            name: kelurahanName,
                            data: kelurahanData,
                            score: finalScore,
                            method: 'advanced_fuzzy'
                        };
                        console.log(`   🔍 Advanced fuzzy: ${kelurahanName} (${fuzzyScore}%)`);
                    }
                }
            }
        });

        // ========================================
        // CONFIDENCE THRESHOLD HANDLING
        // ========================================

        // High confidence (>= 75): auto-proceed
        if (bestMatch && bestMatch.score >= 75) {
            console.log('✅ [KELURAHAN DETECTION] HIGH CONFIDENCE detection');
            console.log(`   📊 Score: ${bestMatch.score}/100`);
            console.log(`   🎯 Method: ${bestMatch.method}`);
            console.log(`   📍 Location: ${bestMatch.name}, ${bestMatch.data.kecamatan}, ${bestMatch.data.kota}`);
            console.log(`   🗺️  Coordinates: [${bestMatch.data.coords[0]}, ${bestMatch.data.coords[1]}]`);
            console.log(`   ✔️  Action: Auto-proceeding with detection`);
            return bestMatch;
        }

        // Medium confidence (>= 65): proceed with warning
        if (bestMatch && bestMatch.score >= 65) {
            console.warn('⚠️  [KELURAHAN DETECTION] MEDIUM CONFIDENCE detection');
            console.warn(`   📊 Score: ${bestMatch.score}/100`);
            console.warn(`   🎯 Method: ${bestMatch.method}`);
            console.warn(`   📍 Location: ${bestMatch.name}, ${bestMatch.data.kecamatan}, ${bestMatch.data.kota}`);
            console.warn(`   🗺️  Coordinates: [${bestMatch.data.coords[0]}, ${bestMatch.data.coords[1]}]`);
            console.warn(`   ⚠️  Action: Proceeding with manual verification recommended`);
            return bestMatch;
        }

        // Low confidence (< 65): skip auto-detection
        console.log('❌ [KELURAHAN DETECTION] LOW CONFIDENCE - Skipping auto-detection');
        console.log(`   ℹ️  Minimum confidence required: 65/100`);
        if (bestMatch) {
            console.log(`   📊 Best match found: ${bestMatch.name}`);
            console.log(`   📊 Score: ${bestMatch.score}/100`);
            console.log(`   🎯 Method: ${bestMatch.method}`);
            console.log(`   📍 Location: ${bestMatch.name}, ${bestMatch.data.kecamatan}, ${bestMatch.data.kota}`);
            console.log(`   ❌ Action: Skipped due to low confidence`);
        } else {
            console.log(`   ℹ️  No matches found in kelurahan database`);
        }

        return null;
    }

    /**
     * Detect jalan from address using backend API
     * 
     * @param {string} alamat - Full address string
     * @param {number} kelurahanId - ID of detected kelurahan
     * @param {function} callback - Callback function with result
     */
    function detectJalanFromAddress(alamat, kelurahanId, callback) {
        if (!kelurahanId) {
            callback(null);
            return;
        }

        console.log('🔍 Detecting jalan from address:', alamat, 'Kelurahan ID:', kelurahanId);

        $.ajax({
            url: '/toko/search-jalan',
            type: 'GET',
            data: {
                keyword: alamat,
                kelurahan_id: kelurahanId,
                limit: 1
            },
            success: function (response) {
                if (response.status === 'success' && response.results.length > 0) {
                    // Check match score
                    const jalan = response.results[0];
                    if (jalan.match_score >= 70) {
                        console.log('✅ Jalan detected:', jalan);
                        callback(jalan);
                    } else {
                        console.log('⚠️ Jalan found but low score:', jalan.match_score);
                        callback(null);
                    }
                } else {
                    console.log('❌ No jalan detected');
                    callback(null);
                }
            },
            error: function () {
                console.error('❌ Error searching jalan');
                callback(null);
            }
        });
    }

    /**
     * Calculate score for standard format matches with normalization and Levenshtein distance
     * Used when kelurahan is found in the expected position (2nd part after street)
     * 
     * @param {string} kelurahanPart - The extracted kelurahan part from address
     * @param {string} kelurahanName - The kelurahan name from database
     * @returns {number} Score between 85-98
     */
    function calculateStandardFormatScore(kelurahanPart, kelurahanName) {
        let score = 85; // Base score for standard format detection

        // Perfect match - should not happen here (handled in exact match)
        if (kelurahanPart === kelurahanName) {
            return 100;
        }

        // Normalize both strings (remove spaces, dashes, underscores)
        const normalizedPart = kelurahanPart.replace(/[\s\-_]/g, '').toLowerCase();
        const normalizedName = kelurahanName.replace(/[\s\-_]/g, '').toLowerCase();

        // Exact match after normalization
        if (normalizedPart === normalizedName) {
            return 98;
        }

        // Length similarity bonus (longer matches are more specific)
        const lengthRatio = Math.min(kelurahanPart.length, kelurahanName.length) /
            Math.max(kelurahanPart.length, kelurahanName.length);
        score += lengthRatio * 10; // Up to +10 points

        // Contains check with position bonus
        if (kelurahanPart.includes(kelurahanName)) {
            score += 6;
            // Bonus if kelurahan name is at start of the part
            if (kelurahanPart.startsWith(kelurahanName)) {
                score += 4;
            }
        } else if (kelurahanName.includes(kelurahanPart)) {
            score += 5;
        }

        // Levenshtein distance for typo tolerance
        const distance = levenshteinDistance(normalizedPart, normalizedName);
        const maxLen = Math.max(normalizedPart.length, normalizedName.length);

        if (maxLen > 0) {
            const similarity = 1 - (distance / maxLen);

            // High similarity bonus
            if (similarity >= 0.9) {
                score += 8; // Very close match (1-2 char difference)
            } else if (similarity >= 0.8) {
                score += 6; // Close match
            } else if (similarity >= 0.7) {
                score += 4; // Moderate match
            } else if (similarity >= 0.6) {
                score += 2; // Weak match
            }
        }

        // Cap at 98 (reserve 100 for perfect exact match)
        return Math.min(98, Math.round(score));
    }

    /**
     * Calculate Levenshtein distance between two strings
     * Used for typo tolerance in kelurahan name matching
     * Lower distance = more similar strings
     * 
     * @param {string} str1 - First string
     * @param {string} str2 - Second string
     * @returns {number} Edit distance between strings
     */
    function levenshteinDistance(str1, str2) {
        // Handle edge cases
        if (!str1 || !str2) {
            return Math.max(str1?.length || 0, str2?.length || 0);
        }

        if (str1 === str2) {
            return 0;
        }

        // Create matrix for dynamic programming
        const matrix = [];

        // Initialize first column (deletions from str2)
        for (let i = 0; i <= str2.length; i++) {
            matrix[i] = [i];
        }

        // Initialize first row (insertions to str1)
        for (let j = 0; j <= str1.length; j++) {
            matrix[0][j] = j;
        }

        // Fill matrix using dynamic programming
        for (let i = 1; i <= str2.length; i++) {
            for (let j = 1; j <= str1.length; j++) {
                if (str2.charAt(i - 1) === str1.charAt(j - 1)) {
                    // Characters match - no operation needed
                    matrix[i][j] = matrix[i - 1][j - 1];
                } else {
                    // Characters don't match - take minimum of:
                    // 1. Substitution (diagonal)
                    // 2. Insertion (left)
                    // 3. Deletion (top)
                    matrix[i][j] = Math.min(
                        matrix[i - 1][j - 1] + 1, // Substitution
                        matrix[i][j - 1] + 1,     // Insertion
                        matrix[i - 1][j] + 1      // Deletion
                    );
                }
            }
        }

        return matrix[str2.length][str1.length];
    }

    /**
     * Calculate contextual score based on position and surrounding text analysis
     * Analyzes where kelurahan appears in address and what text surrounds it
     * 
     * @param {string} alamat - Full address (lowercase)
     * @param {string} kelurahanName - Kelurahan name to find (lowercase)
     * @returns {number} Score between 55-100 based on context
     */
    function calculateContextualScore(alamat, kelurahanName) {
        let score = 55; // Base score for contextual match

        const matchIndex = alamat.indexOf(kelurahanName);
        if (matchIndex === -1) {
            return 0; // No match found
        }

        // ========================================
        // LENGTH BONUS
        // Longer names are more specific and less likely to be false positives
        // ========================================
        const lengthBonus = Math.min(kelurahanName.length * 2, 20);
        score += lengthBonus;

        // ========================================
        // POSITION ANALYSIS
        // Kelurahan typically appears in middle of address (after street, before kecamatan)
        // ========================================
        const relativePosition = matchIndex / alamat.length;

        if (relativePosition > 0.2 && relativePosition < 0.6) {
            // Sweet spot: middle of address (typical kelurahan position)
            score += 15;
        } else if (relativePosition <= 0.2) {
            // Too early: might be part of street name
            score += 5;
        } else if (relativePosition >= 0.6 && relativePosition < 0.8) {
            // Late but acceptable
            score += 8;
        } else {
            // Very late: unusual position
            score += 3;
        }

        // ========================================
        // CONTEXT ANALYSIS - BEFORE & AFTER TEXT
        // ========================================
        const contextWindow = 25; // Characters to analyze before/after
        const before = alamat.substring(Math.max(0, matchIndex - contextWindow), matchIndex);
        const after = alamat.substring(
            matchIndex + kelurahanName.length,
            matchIndex + kelurahanName.length + contextWindow
        );

        // Structured address indicator (commas before AND after)
        if (before.includes(',') && after.includes(',')) {
            score += 20; // Strong indicator of proper address structure
        } else if (before.includes(',') || after.includes(',')) {
            score += 10; // Partial structure
        }

        // Street indicators before kelurahan (expected pattern)
        if (before.match(/\b(jl\.|jalan|gang|gg\.|no\.|nomor)\b/i)) {
            score += 15;
        }

        // Kecamatan after kelurahan (very strong indicator)
        if (after.match(/\b(kec\.|kecamatan)\b/i)) {
            score += 18;
        }

        // Kota/Kabupaten after kelurahan (strong indicator)
        if (after.match(/\b(kota|kabupaten)\b/i)) {
            score += 10;
        }

        // RT/RW before kelurahan (common Indonesian address pattern)
        if (before.match(/\b(rt|rw)\s*[\d\/]+/i)) {
            score += 6;
        }

        // Postal code after kelurahan
        if (after.match(/\b\d{5}\b/)) {
            score += 5;
        }

        // ========================================
        // PENALTIES FOR FALSE POSITIVES
        // ========================================

        // Penalty: Kelurahan name appears right after "Jalan" (likely street name)
        if (before.match(/\b(jl\.|jalan)\s+[^\,]*$/i) && matchIndex < alamat.length * 0.3) {
            score -= 20; // Strong penalty - likely part of street name
        }

        // Penalty: No comma separators nearby (unstructured text)
        if (!before.includes(',') && !after.includes(',') && alamat.includes(',')) {
            score -= 10; // Address has commas but not near this match
        }

        // Penalty: Match is at very end of address (unusual)
        if (matchIndex + kelurahanName.length >= alamat.length - 3) {
            score -= 8;
        }

        // Cap at 100
        return Math.min(100, Math.round(score));
    }

    /**
     * Calculate score for variation matches (handles spaces, underscores, dashes)
     * Used when kelurahan name appears with different separators
     * 
     * @param {string} alamat - Full address (lowercase)
     * @param {string} variation - Variation of kelurahan name (lowercase)
     * @param {string} originalName - Original kelurahan name for comparison
     * @returns {number} Score between 60-90
     */
    function calculateVariationScore(alamat, variation, originalName) {
        let score = 60; // Base score for variation matches

        const matchIndex = alamat.indexOf(variation);
        if (matchIndex === -1) {
            return 0; // No match found
        }

        // Length bonus (longer variations are more specific)
        const lengthBonus = Math.min(variation.length * 1.2, 15);
        score += lengthBonus;

        // Position bonus (earlier matches score higher)
        const positionBonus = Math.max(0, 10 - (matchIndex / 30));
        score += positionBonus;

        // Variation type bonus
        // Check what type of variation this is
        const noSpaces = originalName.replace(/\s+/g, '');
        const withUnderscores = originalName.replace(/\s+/g, '_');
        const withDashes = originalName.replace(/\s+/g, '-');

        if (variation === noSpaces) {
            // No spaces variation (e.g., "polowijen" for "polo wijen")
            score += 8;
        } else if (variation === withUnderscores || variation === withDashes) {
            // Underscore or dash variation
            score += 6;
        }

        // Context check (look for separators around match)
        const before = alamat.substring(Math.max(0, matchIndex - 15), matchIndex);
        const after = alamat.substring(matchIndex + variation.length, matchIndex + variation.length + 15);

        // Bonus if surrounded by commas or spaces (proper separation)
        if ((before.endsWith(',') || before.endsWith(' ')) &&
            (after.startsWith(',') || after.startsWith(' '))) {
            score += 10;
        } else if (before.includes(',') || after.includes(',')) {
            score += 5;
        }

        // Cap at 90 (variations should not score as high as exact matches)
        return Math.min(90, Math.round(score));
    }

    function calculateMatchScore(alamat, kelurahanName) {
        // Hitung score berdasarkan:
        // 1. Panjang match
        // 2. Posisi dalam string (awal lebih tinggi)
        // 3. Context (ada kata kunci seperti "kelurahan", "kel")

        let score = 50; // Base score

        const matchIndex = alamat.indexOf(kelurahanName.toLowerCase());
        if (matchIndex !== -1) {
            // Length bonus
            score += kelurahanName.length * 2;

            // Position bonus (match di awal lebih tinggi)
            score += Math.max(0, 20 - matchIndex);

            // Context bonus
            const before = alamat.substring(Math.max(0, matchIndex - 10), matchIndex);
            const after = alamat.substring(matchIndex + kelurahanName.length, matchIndex + kelurahanName.length + 10);

            if (before.includes('kelurahan') || before.includes('kel.') || before.includes('kel ')) {
                score += 30;
            }

            if (after.includes('kecamatan') || after.includes('kec.') || after.includes('kec ')) {
                score += 10;
            }
        }

        return Math.min(100, score);
    }

    // Note: zoomToDetectedKelurahan, updateDetectedKelurahanInfo, and setWilayahFromDetection
    // are now defined in the ENHANCED MAP CONTROLLER FUNCTIONS section for better organization and features

    function performEnhancedGeocoding(alamat, detectedKelurahan) {
        const fullAddress = buildSmartAddress(alamat, detectedKelurahan);
        const startTime = performance.now();

        console.log('🌐 [GEOCODING] Starting enhanced geocoding request');
        console.log(`   📝 Original address: ${alamat}`);
        console.log(`   📝 Smart address: ${fullAddress}`);
        console.log(`   🏘️  Detected kelurahan: ${detectedKelurahan ? detectedKelurahan.name : 'None'}`);
        console.log(`   ⏱️  Timeout: 15000ms`);

        showSearchStatus('searching', 'Mencari koordinat presisi...');

        $.ajax({
            url: '/toko/preview-geocode',
            type: 'POST',
            data: {
                alamat: fullAddress,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            timeout: 15000,
            success: function (response) {
                const responseTime = (performance.now() - startTime).toFixed(2);

                console.log(`✅ [GEOCODING] Request successful (${responseTime}ms)`);
                console.log('   📦 Response:', response);

                if (response.status === 'success' && response.geocode_info) {
                    const geocodeInfo = response.geocode_info;

                    console.log('   📍 Coordinates found:');
                    console.log(`      Latitude: ${geocodeInfo.latitude}`);
                    console.log(`      Longitude: ${geocodeInfo.longitude}`);
                    console.log(`   🏢 Provider: ${geocodeInfo.provider || 'Unknown'}`);
                    console.log(`   🎯 Accuracy: ${geocodeInfo.accuracy || 'Unknown'}`);
                    console.log(`   📊 Quality Score: ${geocodeInfo.quality_score || 'N/A'}/100`);
                    console.log(`   📊 Quality Level: ${geocodeInfo.quality_level || 'Unknown'}`);
                    console.log(`   🔒 Confidence: ${geocodeInfo.confidence || 'N/A'}`);
                    console.log(`   🗺️  In Malang Region: ${geocodeInfo.in_malang_region ? 'Yes' : 'No'}`);
                    console.log(`   📄 Formatted Address: ${geocodeInfo.formatted_address || 'N/A'}`);

                    if (geocodeInfo.in_malang_region) {
                        console.log('   ✔️  Validation: PASSED - Coordinates in Malang region');

                        // If quality is high enough, auto-select the location
                        // Threshold: 75 (High/Excellent)
                        if (geocodeInfo.quality_score >= 75) {
                            console.log('   ✨ High quality match - Auto-selecting coordinates');

                            // Store initial geocoded position for tolerance tracking
                            storeInitialGeocodedPosition(
                                geocodeInfo.latitude,
                                geocodeInfo.longitude,
                                geocodeInfo.formatted_address
                            );

                            updateCoordinateFields(geocodeInfo.latitude, geocodeInfo.longitude);
                            showFinalMarker(geocodeInfo.latitude, geocodeInfo.longitude, geocodeInfo.formatted_address);
                            showLocationStatus(geocodeInfo.latitude, geocodeInfo.longitude, true);

                            let message = 'Lokasi ditemukan dengan akurasi tinggi dan telah dipilih otomatis! ';
                            if (detectedKelurahan) {
                                message += `Area: ${detectedKelurahan.name}.`;
                            }
                            showSearchStatus('success', message);
                        } else {
                            // For lower quality, show preview and ask for confirmation
                            showPreviewLocation(geocodeInfo.latitude, geocodeInfo.longitude, alamat, detectedKelurahan);

                            let message = 'Lokasi ditemukan (akurasi sedang). ';
                            if (detectedKelurahan) {
                                message += `Area: ${detectedKelurahan.name}. `;
                            }
                            message += 'Klik marker kuning untuk konfirmasi atau klik peta untuk koreksi.';

                            showSearchStatus('warning', message);
                        }
                    } else {
                        console.warn('   ⚠️  Validation: WARNING - Coordinates outside Malang region');
                        showSearchStatus('warning', 'Lokasi ditemukan tapi di luar wilayah Malang Raya.');
                    }
                } else {
                    console.warn('   ⚠️  No geocode info in response');
                    console.warn('   📋 Response status:', response.status);
                    console.warn('   📋 Response message:', response.message);

                    let message = 'Koordinat presisi belum ditemukan. ';
                    if (detectedKelurahan) {
                        message += `Peta sudah dipusatkan ke ${detectedKelurahan.name}. Klik pada area yang sesuai.`;
                    } else {
                        message += 'Klik pada peta untuk menentukan lokasi manual.';
                    }
                    showSearchStatus('info', message);
                }
            },
            error: function (xhr, status, error) {
                const responseTime = (performance.now() - startTime).toFixed(2);

                console.error(`❌ [GEOCODING] Request failed (${responseTime}ms)`);
                console.error(`   🔍 Status: ${status}`);
                console.error(`   📝 Error: ${error}`);
                console.error(`   📄 Response: ${xhr.responseText}`);
                console.error(`   🔢 HTTP Status: ${xhr.status}`);

                if (status === 'timeout') {
                    console.error('   ⏱️  Request timed out after 15000ms');
                }

                let message = 'Pencarian otomatis gagal. ';
                if (detectedKelurahan) {
                    message += `Peta sudah dipusatkan ke ${detectedKelurahan.name}. Klik untuk menentukan lokasi.`;
                } else {
                    message += 'Klik pada peta untuk menentukan lokasi manual.';
                }
                showSearchStatus('info', message);
            }
        });
    }

    /**
     * Build smart, complete address for geocoding with enhanced NLP
     * Uses parsed address components and fuzzy matching for accuracy
     * 
     * @param {string} alamat - Original address string
     * @param {object} kelurahan - Detected kelurahan object
     * @returns {string} Enhanced address optimized for geocoding
     */
    function buildSmartAddress(alamat, kelurahan) {
        let fullAddress = alamat.trim();

        // Parse alamat to understand existing components
        const parsedAddress = parseIndonesianAddress(alamat);
        const addressParts = fullAddress.split(',').map(part => part.trim());
        const addressLower = fullAddress.toLowerCase();

        console.log('🏗️  Building smart address from:', fullAddress);
        console.log('📋 Parsed components:', parsedAddress);
        console.log('📍 Detected kelurahan:', kelurahan);

        // Jika kelurahan terdeteksi, pastikan alamat sudah lengkap dengan format standar
        if (kelurahan) {
            const kelurahanName = kelurahan.name;
            const kecamatanName = kelurahan.data.kecamatan;
            const kotaName = kelurahan.data.kota;

            // Cek apakah alamat sudah mengikuti format standar Indonesia
            let hasKelurahan = false;
            let hasKecamatan = false;
            let hasKota = false;
            let hasProvinsi = false;

            // Check existing components with fuzzy matching enhancement
            addressParts.forEach(part => {
                const partLower = part.toLowerCase();

                // Enhanced kelurahan detection with fuzzy matching
                if (partLower.includes(kelurahanName.toLowerCase()) ||
                    advancedFuzzyMatch(part, kelurahanName) >= 80) {
                    hasKelurahan = true;
                }

                // Enhanced kecamatan detection
                if (partLower.includes('kec.') || partLower.includes('kecamatan') ||
                    partLower.includes(kecamatanName.toLowerCase()) ||
                    advancedFuzzyMatch(part, kecamatanName) >= 80) {
                    hasKecamatan = true;
                }

                // Enhanced kota detection  
                const kotaSimplified = kotaName.toLowerCase().replace('kota ', '').replace('kabupaten ', '');
                if (partLower.includes('kota') || partLower.includes('kabupaten') ||
                    partLower.includes(kotaSimplified) ||
                    advancedFuzzyMatch(part, kotaName) >= 80) {
                    hasKota = true;
                }

                if (partLower.includes('jawa timur') || partLower.includes('east java')) {
                    hasProvinsi = true;
                }
            });

            // Build complete address in standard Indonesian format
            let addressComponents = [];

            // 1. Keep the street part (first component, usually)
            if (addressParts.length > 0) {
                addressComponents.push(addressParts[0]);
            }

            // 2. Add kelurahan if not present
            if (!hasKelurahan) {
                addressComponents.push(kelurahanName);
            } else {
                // Keep existing kelurahan part
                for (let i = 1; i < addressParts.length; i++) {
                    if (addressParts[i].toLowerCase().includes(kelurahanName.toLowerCase())) {
                        addressComponents.push(addressParts[i]);
                        break;
                    }
                }
            }

            // 3. Add kecamatan if not present
            if (!hasKecamatan) {
                addressComponents.push(`Kec. ${kecamatanName}`);
            } else {
                // Keep existing kecamatan part
                for (let part of addressParts) {
                    const partLower = part.toLowerCase();
                    if (partLower.includes('kec.') || partLower.includes('kecamatan')) {
                        addressComponents.push(part);
                        break;
                    }
                }
            }

            // 4. Add kota if not present
            if (!hasKota) {
                addressComponents.push(kotaName);
            } else {
                // Keep existing kota part
                for (let part of addressParts) {
                    const partLower = part.toLowerCase();
                    if (partLower.includes('kota') || partLower.includes('kabupaten')) {
                        addressComponents.push(part);
                        break;
                    }
                }
            }

            // 5. Add provinsi if not present
            if (!hasProvinsi) {
                addressComponents.push('Jawa Timur');
            }

            // 6. Add Indonesia if not present
            if (!addressLower.includes('indonesia')) {
                addressComponents.push('Indonesia');
            }

            fullAddress = addressComponents.join(', ');

            console.log('Smart address built:', fullAddress);
        } else {
            // Fallback: basic enhancement for non-detected addresses
            if (!addressLower.includes('malang')) {
                fullAddress += ', Malang';
            }

            if (!addressLower.includes('jawa timur')) {
                fullAddress += ', Jawa Timur';
            }

            if (!addressLower.includes('indonesia')) {
                fullAddress += ', Indonesia';
            }
        }

        return fullAddress;
    }

    function showPreviewLocation(lat, lng, address, kelurahan) {
        if (!interactiveMap) return;

        clearPreviewMarker();

        // Validate coordinates precision
        const precision = {
            lat: lat.toString().split('.')[1]?.length || 0,
            lng: lng.toString().split('.')[1]?.length || 0
        };

        const avgPrecision = (precision.lat + precision.lng) / 2;
        let accuracyLevel = 'medium';
        let accuracyColor = '#ffc107'; // yellow

        if (avgPrecision >= 6) {
            accuracyLevel = 'high';
            accuracyColor = '#28a745'; // green
        } else if (avgPrecision < 4) {
            accuracyLevel = 'low';
            accuracyColor = '#dc3545'; // red
        }

        console.log(`📍 Preview location accuracy: ${accuracyLevel} (precision: ${avgPrecision} decimals)`);

        // Buat preview marker dengan enhanced styling based on accuracy
        const previewIcon = L.divIcon({
            className: 'smart-preview-marker',
            html: `<div style="background-color: ${accuracyColor}; width: 20px; height: 20px; border-radius: 50%; border: 3px solid white; box-shadow: 0 4px 10px rgba(0,0,0,0.5); animation: pulse 2s infinite;"></div>`,
            iconSize: [26, 26],
            iconAnchor: [13, 13]
        });

        previewMarker = L.marker([lat, lng], {
            icon: previewIcon
        }).addTo(interactiveMap);

        // Enhanced popup dengan informasi kelurahan dan accuracy
        const accuracyBadge = accuracyLevel === 'high' ?
            '<span class="badge badge-success">Akurasi Tinggi</span>' :
            accuracyLevel === 'medium' ?
                '<span class="badge badge-warning">Akurasi Sedang</span>' :
                '<span class="badge badge-danger">Akurasi Rendah</span>';

        let popupContent = `
            <div style="text-align: center; max-width: 240px;">
                <strong><i class="fas fa-search-location" style="color: ${accuracyColor};"></i> Lokasi Terdeteksi</strong><br>
                ${accuracyBadge}<br>
                <small style="color: #666; margin-top: 4px; display: block;">${address.substring(0, 50)}${address.length > 50 ? '...' : ''}</small><br>
        `;

        if (kelurahan) {
            popupContent += `
                <div style="margin: 8px 0; padding: 6px; background: #e3f2fd; border-radius: 4px; border-left: 3px solid #2196f3;">
                    <small style="color: #1976d2; font-weight: bold;">
                        <i class="fas fa-map-marker-alt"></i> ${kelurahan.name}
                    </small><br>
                    <small style="color: #666;">${kelurahan.data.kecamatan}, ${kelurahan.data.kota}</small>
                    ${kelurahan.score ? `<br><small style="color: #999;">Confidence: ${kelurahan.score}%</small>` : ''}
                </div>
            `;
        }

        popupContent += `
                <div style="margin: 8px 0; padding: 6px; background: #f8f9fa; border-radius: 4px;">
                    <small style="color: #28a745; font-weight: bold;">
                        <i class="fas fa-mouse-pointer"></i> ${accuracyLevel === 'high' ? 'Klik untuk konfirmasi' : 'Klik area sekitar untuk presisi lebih tinggi'}
                    </small>
                </div>
                <small style="color: #999; font-size: 10px;">Presisi: ${avgPrecision.toFixed(1)} desimal</small>
            </div>
        `;

        previewMarker.bindPopup(popupContent).openPopup();

        // Add click listener to confirm location
        previewMarker.on('click', function () {
            console.log('✅ Preview marker clicked - Confirming location');
            updateCoordinateFields(lat, lng);
            showFinalMarker(lat, lng, address);
            showLocationStatus(lat, lng, true);

            // Show success message
            showSearchStatus('success', 'Lokasi dikonfirmasi! Silakan simpan data.');
        });

        // Smooth pan ke lokasi (tidak zoom ulang jika sudah zoom ke kelurahan)
        if (!kelurahan || interactiveMap.getZoom() < 15) {
            interactiveMap.setView([lat, lng], 17, {
                animate: true,
                duration: 1
            });
        } else {
            interactiveMap.panTo([lat, lng], {
                animate: true,
                duration: 0.8
            });
        }
    }

    function clearPreviewMarker() {
        if (previewMarker) {
            interactiveMap.removeLayer(previewMarker);
            previewMarker = null;
        }
    }

    /**
     * Show search status with appropriate styling and auto-hide behavior
     * Requirements: 3.1, 3.2, 3.3
     * 
     * @param {string} type - Status type: searching, success, warning, info, error
     * @param {string} message - Message to display
     */
    function showSearchStatus(type, message) {
        // Disabled - tidak menampilkan status apapun
        console.log(`📢 Search status (hidden): ${type} - ${message}`);
        return;
    }

    // ========================================
    // ENHANCED MAP CONTROLLER FUNCTIONS
    // ========================================

    /**
     * Zoom to detected kelurahan with smooth animation and area indicator
     * Requirements: 1.2, 3.4
     * 
     * @param {object} detectedKelurahan - Kelurahan object with coords, name, kecamatan, kota
     */
    function zoomToDetectedKelurahan(detectedKelurahan) {
        if (!interactiveMap || !detectedKelurahan || !detectedKelurahan.data || !detectedKelurahan.data.coords) {
            console.warn('⚠️ Cannot zoom: map not initialized or invalid kelurahan data');
            return;
        }

        const coords = detectedKelurahan.data.coords;
        const lat = coords[0];
        const lng = coords[1];

        console.log(`🎯 Zooming to kelurahan: ${detectedKelurahan.name} at [${lat}, ${lng}]`);

        // Perform smooth zoom animation (duration: 1.5s, zoom: 16)
        interactiveMap.flyTo([lat, lng], 16, {
            animate: true,
            duration: 1.5,
            easeLinearity: 0.25
        });

        // Show temporary area indicator (blue circle, radius: 500m)
        const areaIndicator = L.circle([lat, lng], {
            color: '#2196f3',
            fillColor: '#2196f3',
            fillOpacity: 0.15,
            radius: 500,
            weight: 2,
            dashArray: '5, 5'
        }).addTo(interactiveMap);

        // Add popup to area indicator
        areaIndicator.bindPopup(`
            <div style="text-align: center;">
                <strong><i class="fas fa-map-marked-alt" style="color: #2196f3;"></i> Area ${detectedKelurahan.name}</strong><br>
                <small style="color: #666;">${detectedKelurahan.data.kecamatan}, ${detectedKelurahan.data.kota}</small><br>
                <small style="color: #999;">Radius indikasi: 500m</small>
            </div>
        `).openPopup();

        // Auto-remove indicator after 3 seconds
        setTimeout(() => {
            interactiveMap.removeLayer(areaIndicator);
            console.log('✅ Area indicator removed after 3 seconds');
        }, 3000);

        // Update detected kelurahan info display
        updateDetectedKelurahanInfo(detectedKelurahan);

        console.log('✅ Zoom to kelurahan completed');
    }

    /**
     * Show preview marker (yellow) with popup
     * Requirements: 1.5, 2.1, 2.2
     * 
     * @param {number} lat - Latitude
     * @param {number} lng - Longitude
     * @param {string} popupText - Text to display in popup
     */
    function showPreviewMarker(lat, lng, popupText) {
        if (!interactiveMap) {
            console.warn('⚠️ Cannot show preview marker: map not initialized');
            return;
        }

        // Clear existing preview marker first
        clearPreviewMarker();

        console.log(`📍 Showing preview marker at [${lat}, ${lng}]`);

        // Create yellow preview marker with enhanced styling
        const previewIcon = L.divIcon({
            className: 'preview-marker',
            html: `<div style="background-color: #ffc107; width: 20px; height: 20px; border-radius: 50%; border: 3px solid white; box-shadow: 0 4px 10px rgba(0,0,0,0.5); animation: pulse 2s infinite;"></div>`,
            iconSize: [26, 26],
            iconAnchor: [13, 13]
        });

        previewMarker = L.marker([lat, lng], {
            icon: previewIcon
        }).addTo(interactiveMap);

        // Bind popup with provided text
        const popupContent = `
            <div style="text-align: center; max-width: 240px;">
                <strong><i class="fas fa-search-location" style="color: #ffc107;"></i> Lokasi Preview</strong><br>
                <small style="color: #666; margin-top: 4px; display: block;">${popupText}</small><br>
                <div style="margin: 8px 0; padding: 6px; background: #fff3cd; border-radius: 4px; border-left: 3px solid #ffc107;">
                    <small style="color: #856404; font-weight: bold;">
                        <i class="fas fa-mouse-pointer"></i> Klik untuk konfirmasi atau pilih lokasi lain
                    </small>
                </div>
                <small style="color: #999; font-size: 10px;">Lat: ${lat.toFixed(6)}, Lng: ${lng.toFixed(6)}</small>
            </div>
        `;

        previewMarker.bindPopup(popupContent).openPopup();

        // Add click listener to preview marker - convert to final marker
        previewMarker.on('click', function () {
            console.log('🖱️ Preview marker clicked, converting to final marker');
            showFinalMarker(lat, lng, 'Lokasi toko dikonfirmasi dari preview');
            updateCoordinateFields(lat, lng);
        });

        console.log('✅ Preview marker displayed');
    }

    /**
     * Update detected kelurahan info display
     * Requirements: 3.4
     * 
     * @param {object} kelurahan - Kelurahan object with name, kecamatan, kota, score
     */
    function updateDetectedKelurahanInfo(kelurahan) {
        // DISABLED: Hide info box display - only keep auto-fill functionality
        $('#detectedKelurahanInfo').hide();
        
        if (!kelurahan) {
            return;
        }

        console.log('📋 Processing detected kelurahan (display disabled)');

        // Parse alamat untuk mengekstrak informasi wilayah menggunakan parseIndonesianAddress()
        const alamat = $('#alamat').val();
        const parsedAddress = parseIndonesianAddress(alamat);

        console.log('Parsed address:', parsedAddress);

        // Use parsed address components with fallback to database
        const kota = parsedAddress.kota || kelurahan.data.kota;
        const kecamatan = parsedAddress.kecamatan || kelurahan.data.kecamatan;
        const kelurahanName = parsedAddress.kelurahan || kelurahan.name;

        console.log('Using - Kota:', kota, 'Kecamatan:', kecamatan, 'Kelurahan:', kelurahanName);

        // Auto-set dropdown wilayah dengan improved matching (keep this functionality)
        setWilayahFromDetection(kota, kecamatan, kelurahanName);

        console.log('✅ Detected kelurahan processed (display disabled)');
    }

    // ========================================
    // AUTO-FILL DROPDOWN WILAYAH FUNCTIONS
    // Requirements: 5.2, 5.3, 5.4, 5.5
    // ========================================

    /**
     * Set wilayah dropdowns from detected kelurahan data
     * Implements fuzzy matching and cascade loading
     * Requirements: 5.2, 5.3
     * 
     * @param {string} kota - Kota name from detection
     * @param {string} kecamatan - Kecamatan name from detection
     * @param {string} kelurahan - Kelurahan name from detection
     */
    function setWilayahFromDetection(kota, kecamatan, kelurahan) {
        console.log('🔄 Auto-filling wilayah dropdowns from detection...');
        console.log('   Kota:', kota);
        console.log('   Kecamatan:', kecamatan);
        console.log('   Kelurahan:', kelurahan);

        // Extract kota, kecamatan, kelurahan dari detected data
        if (!kota || !kecamatan) {
            console.warn('⚠️ Incomplete wilayah data (missing Kota or Kecamatan), skipping auto-fill');
            return;
        }

        // Kelurahan is optional for partial auto-fill
        if (!kelurahan) {
            console.log('ℹ️ Kelurahan missing, will only set Kota and Kecamatan');
        }

        // Step 1: Fuzzy match and set Kota dropdown
        const kotaMatched = fuzzyMatchKota(kota);

        if (kotaMatched) {
            console.log('✅ Kota matched:', kotaMatched.nama, '(ID:', kotaMatched.id, ')');

            // Set kota dropdown value
            $('#wilayah_kota_id').val(kotaMatched.id);
            $('#wilayah_kota_kabupaten').val(kotaMatched.nama);

            // Trigger change event untuk load kecamatan options
            // Add delay (300ms) untuk sequential loading
            setTimeout(function () {
                $('#wilayah_kota_id').trigger('change');

                // Step 2: After kota change loads kecamatan, set kecamatan
                setTimeout(function () {
                    setKecamatanFromDetection(kotaMatched.id, kecamatan, kelurahan);
                }, 300);
            }, 300);

        } else {
            console.warn('⚠️ Kota not matched:', kota);
            showWilayahNotification('warning', 'Kota tidak ditemukan dalam database. Silakan pilih manual.');
        }
    }

    /**
     * Set kecamatan dropdown from detected data with fuzzy matching
     * Requirements: 5.3, 5.4
     * 
     * @param {number} kotaId - Kota ID that was matched
     * @param {string} kecamatan - Kecamatan name from detection
     * @param {string} kelurahan - Kelurahan name from detection (for next step)
     */
    function setKecamatanFromDetection(kotaId, kecamatan, kelurahan) {
        console.log('🔄 Setting kecamatan from detection:', kecamatan);

        // Wait for kecamatan dropdown to be populated
        const checkKecamatanLoaded = setInterval(function () {
            const kecamatanOptions = $('#wilayah_kecamatan_id option');

            // Check if dropdown has been populated (more than just the default option)
            if (kecamatanOptions.length > 1 && !$('#wilayah_kecamatan_id').prop('disabled')) {
                clearInterval(checkKecamatanLoaded);

                // Fuzzy match kecamatan
                const kecamatanMatched = fuzzyMatchKecamatan(kecamatan);

                if (kecamatanMatched) {
                    console.log('✅ Kecamatan matched:', kecamatanMatched.nama, '(ID:', kecamatanMatched.id, ')');

                    // Set kecamatan dropdown value
                    $('#wilayah_kecamatan_id').val(kecamatanMatched.id);
                    $('#wilayah_kecamatan').val(kecamatanMatched.nama);

                    // Trigger change event untuk load kelurahan options
                    setTimeout(function () {
                        $('#wilayah_kecamatan_id').trigger('change');

                        // Step 3: After kecamatan change loads kelurahan, set kelurahan
                        setTimeout(function () {
                            setKelurahanFromDetection(kelurahan);
                        }, 300);
                    }, 300);

                } else {
                    console.warn('⚠️ Kecamatan not matched:', kecamatan);
                    showWilayahNotification('warning', 'Kecamatan tidak ditemukan. Silakan pilih manual.');
                }
            }
        }, 100); // Check every 100ms

        // Timeout after 5 seconds
        setTimeout(function () {
            clearInterval(checkKecamatanLoaded);
        }, 5000);
    }

    /**
     * Set kelurahan dropdown from detected data with fuzzy matching
     * Requirements: 5.3, 5.4
     * 
     * @param {string} kelurahan - Kelurahan name from detection
     */
    function setKelurahanFromDetection(kelurahan) {
        console.log('🔄 Setting kelurahan from detection:', kelurahan);

        // Wait for kelurahan dropdown to be populated
        const checkKelurahanLoaded = setInterval(function () {
            const kelurahanOptions = $('#wilayah_kelurahan_id option');

            // Check if dropdown has been populated (more than just the default option)
            if (kelurahanOptions.length > 1 && !$('#wilayah_kelurahan_id').prop('disabled')) {
                clearInterval(checkKelurahanLoaded);

                // Fuzzy match kelurahan
                const kelurahanMatched = fuzzyMatchKelurahan(kelurahan);

                if (kelurahanMatched) {
                    console.log('✅ Kelurahan matched:', kelurahanMatched.nama, '(ID:', kelurahanMatched.id, ')');

                    // Set kelurahan dropdown value
                    $('#wilayah_kelurahan_id').val(kelurahanMatched.id);
                    $('#wilayah_kelurahan').val(kelurahanMatched.nama);

                    // Show success notification
                    showWilayahNotification('success',
                        `Wilayah terdeteksi otomatis: ${$('#wilayah_kota_kabupaten').val()}, ${$('#wilayah_kecamatan').val()}, ${kelurahanMatched.nama}`
                    );

                } else {
                    console.warn('⚠️ Kelurahan not matched:', kelurahan);
                    showWilayahNotification('info', 'Kelurahan tidak ditemukan. Silakan pilih manual.');
                }
            }
        }, 100); // Check every 100ms

        // Timeout after 5 seconds
        setTimeout(function () {
            clearInterval(checkKelurahanLoaded);
        }, 5000);
    }

    /**
     * Fuzzy match kota name with dropdown options
     * Uses Levenshtein distance for typo tolerance
     * Requirements: 5.2, 5.3
     * 
     * @param {string} kotaName - Kota name to match
     * @returns {object|null} Matched option with id and nama, or null
     */
    function fuzzyMatchKota(kotaName) {
        if (!kotaName) return null;

        const kotaOptions = $('#wilayah_kota_id option');
        let bestMatch = null;
        let bestScore = 0;

        // Normalize input
        const normalizedInput = kotaName.toLowerCase()
            .replace(/^(kota|kabupaten)\s+/i, '')
            .trim();

        console.log('🔍 Fuzzy matching kota:', normalizedInput);

        kotaOptions.each(function () {
            const optionValue = $(this).val();
            const optionText = $(this).text().trim();
            const optionData = $(this).data('nama');

            if (!optionValue || optionValue === '') return; // Skip default option

            // Normalize option text
            const normalizedOption = optionText.toLowerCase()
                .replace(/^(kota|kabupaten)\s+/i, '')
                .trim();

            // Calculate match score
            let score = 0;

            // Exact match (highest priority)
            if (normalizedOption === normalizedInput) {
                score = 100;
            }
            // Contains match
            else if (normalizedOption.includes(normalizedInput) || normalizedInput.includes(normalizedOption)) {
                score = 90;
            }
            // Levenshtein distance match
            else {
                const distance = levenshteinDistance(normalizedInput, normalizedOption);
                const maxLen = Math.max(normalizedInput.length, normalizedOption.length);
                const similarity = 1 - (distance / maxLen);
                score = similarity * 80; // Max 80 for fuzzy match
            }

            console.log(`   Option: "${optionText}" -> Score: ${score.toFixed(1)}`);

            if (score > bestScore && score >= 70) { // Minimum 70% match
                bestScore = score;
                bestMatch = {
                    id: optionValue,
                    nama: optionData || optionText
                };
            }
        });

        if (bestMatch) {
            console.log(`✅ Best kota match: "${bestMatch.nama}" with score ${bestScore.toFixed(1)}`);
        } else {
            console.log('❌ No kota match found with sufficient score (minimum 70)');
        }

        return bestMatch;
    }

    /**
     * Fuzzy match kecamatan name with dropdown options
     * Uses Levenshtein distance for typo tolerance
     * Requirements: 5.3, 5.4
     * 
     * @param {string} kecamatanName - Kecamatan name to match
     * @returns {object|null} Matched option with id and nama, or null
     */
    function fuzzyMatchKecamatan(kecamatanName) {
        if (!kecamatanName) return null;

        const kecamatanOptions = $('#wilayah_kecamatan_id option');
        let bestMatch = null;
        let bestScore = 0;

        // Normalize input
        const normalizedInput = kecamatanName.toLowerCase()
            .replace(/^(kec\.|kecamatan)\s+/i, '')
            .trim();

        console.log('🔍 Fuzzy matching kecamatan:', normalizedInput);

        kecamatanOptions.each(function () {
            const optionValue = $(this).val();
            const optionText = $(this).text().trim();
            const optionData = $(this).data('nama');

            if (!optionValue || optionValue === '') return; // Skip default option

            // Normalize option text
            const normalizedOption = optionText.toLowerCase()
                .replace(/^(kec\.|kecamatan)\s+/i, '')
                .trim();

            // Calculate match score
            let score = 0;

            // Exact match (highest priority)
            if (normalizedOption === normalizedInput) {
                score = 100;
            }
            // Contains match
            else if (normalizedOption.includes(normalizedInput) || normalizedInput.includes(normalizedOption)) {
                score = 90;
            }
            // Levenshtein distance match
            else {
                const distance = levenshteinDistance(normalizedInput, normalizedOption);
                const maxLen = Math.max(normalizedInput.length, normalizedOption.length);
                const similarity = 1 - (distance / maxLen);
                score = similarity * 80; // Max 80 for fuzzy match
            }

            console.log(`   Option: "${optionText}" -> Score: ${score.toFixed(1)}`);

            if (score > bestScore && score >= 70) { // Minimum 70% match
                bestScore = score;
                bestMatch = {
                    id: optionValue,
                    nama: optionData || optionText
                };
            }
        });

        if (bestMatch) {
            console.log(`✅ Best kecamatan match: "${bestMatch.nama}" with score ${bestScore.toFixed(1)}`);
        } else {
            console.log('❌ No kecamatan match found with sufficient score (minimum 70)');
        }

        return bestMatch;
    }

    /**
     * Fuzzy match kelurahan name with dropdown options
     * Uses Levenshtein distance for typo tolerance
     * Requirements: 5.3, 5.4
     * 
     * @param {string} kelurahanName - Kelurahan name to match
     * @returns {object|null} Matched option with id and nama, or null
     */
    function fuzzyMatchKelurahan(kelurahanName) {
        if (!kelurahanName) return null;

        const kelurahanOptions = $('#wilayah_kelurahan_id option');
        let bestMatch = null;
        let bestScore = 0;

        // Normalize input
        const normalizedInput = kelurahanName.toLowerCase()
            .replace(/^(kel\.|kelurahan)\s+/i, '')
            .trim();

        console.log('🔍 Fuzzy matching kelurahan:', normalizedInput);

        kelurahanOptions.each(function () {
            const optionValue = $(this).val();
            const optionText = $(this).text().trim();
            const optionData = $(this).data('nama');

            if (!optionValue || optionValue === '') return; // Skip default option

            // Normalize option text
            const normalizedOption = optionText.toLowerCase()
                .replace(/^(kel\.|kelurahan)\s+/i, '')
                .trim();

            // Calculate match score
            let score = 0;

            // Exact match (highest priority)
            if (normalizedOption === normalizedInput) {
                score = 100;
            }
            // Contains match
            else if (normalizedOption.includes(normalizedInput) || normalizedInput.includes(normalizedOption)) {
                score = 90;
            }
            // Levenshtein distance match
            else {
                const distance = levenshteinDistance(normalizedInput, normalizedOption);
                const maxLen = Math.max(normalizedInput.length, normalizedOption.length);
                const similarity = 1 - (distance / maxLen);
                score = similarity * 80; // Max 80 for fuzzy match
            }

            console.log(`   Option: "${optionText}" -> Score: ${score.toFixed(1)}`);

            if (score > bestScore && score >= 70) { // Minimum 70% match
                bestScore = score;
                bestMatch = {
                    id: optionValue,
                    nama: optionData || optionText
                };
            }
        });

        if (bestMatch) {
            console.log(`✅ Best kelurahan match: "${bestMatch.nama}" with score ${bestScore.toFixed(1)}`);
        } else {
            console.log('❌ No kelurahan match found with sufficient score (minimum 70)');
        }

        return bestMatch;
    }

    /**
     * Show wilayah auto-fill notification
     * Requirements: 5.5
     * 
     * @param {string} type - Notification type (success, warning, info)
     * @param {string} message - Notification message
     */
    function showWilayahNotification(type, message) {
        // Disabled - hanya log ke console tanpa menampilkan alert
        console.log(`📢 Wilayah notification (hidden): ${type} - ${message}`);
        return; // Early return - tidak menampilkan alert
    }

    // ========================================
    // INTERACTIVE MAP FUNCTIONS (Same core, enhanced preview)
    // ========================================

    function initializeInteractiveMap() {
        if (isMapInitialized && interactiveMap) {
            console.log('Map already initialized, skipping...');
            return;
        }

        console.log('🗺️ Initializing smart address parsing map...');

        // Check if Leaflet is loaded
        if (typeof L === 'undefined') {
            console.error('❌ Leaflet library not loaded!');
            showAlert('danger', 'Library peta belum dimuat. Refresh halaman.');
            return;
        }

        // Check if map container exists
        const mapContainer = document.getElementById('interactiveMap');
        if (!mapContainer) {
            console.error('❌ Map container #interactiveMap not found!');
            showAlert('danger', 'Container peta tidak ditemukan.');
            return;
        }

        console.log('✅ Map container found, initializing Leaflet...');

        try {
            interactiveMap = L.map('interactiveMap', {
                center: MALANG_CENTER,
                zoom: 12,
                zoomControl: true,
                attributionControl: true
            });

            console.log('✅ Leaflet map object created');
        } catch (error) {
            console.error('❌ Error creating Leaflet map:', error);
            showAlert('danger', 'Gagal membuat peta: ' + error.message);
            return;
        }

        try {
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19,
                minZoom: 10
            }).addTo(interactiveMap);

            console.log('✅ Tile layer added');
        } catch (error) {
            console.error('❌ Error adding tile layer:', error);
            showAlert('warning', 'Gagal memuat tiles peta. Cek koneksi internet.');
        }

        const malangBounds = [
            [MALANG_BOUNDS.south, MALANG_BOUNDS.west],
            [MALANG_BOUNDS.north, MALANG_BOUNDS.east]
        ];

        L.rectangle(malangBounds, {
            color: '#007bff',
            weight: 2,
            fillOpacity: 0.1,
            dashArray: '5, 5'
        }).addTo(interactiveMap).bindPopup('Wilayah Malang Raya<br><small>Ketik alamat dengan nama kelurahan untuk zoom otomatis</small>');

        // Map click event - Enhanced for manual selection
        // Requirements: 2.1, 2.2, 2.3
        interactiveMap.on('click', function (e) {
            const lat = e.latlng.lat;
            const lng = e.latlng.lng;

            console.log('🖱️ Map clicked at:', lat, lng);

            // Validate coordinates in Malang bounds
            if (isWithinMalangRegion(lat, lng)) {
                // Show final marker at clicked location
                showFinalMarker(lat, lng, 'Lokasi dipilih secara manual dari peta');

                // Update coordinate display fields
                updateCoordinateFields(lat, lng);

                // Show location status
                showLocationStatus(lat, lng, true);

                // Trigger reverse geocoding untuk validation
                performReverseGeocoding(lat, lng);

                console.log('✅ Manual location selection completed');
            } else {
                showAlert('warning', 'Lokasi yang dipilih berada di luar wilayah Malang Raya. Silakan pilih lokasi dalam area yang ditandai.');
                showLocationStatus(lat, lng, false);
                console.warn('⚠️ Selected location outside Malang region bounds');
            }
        });

        L.control.scale({
            position: 'bottomright',
            imperial: false
        }).addTo(interactiveMap);

        const locationControl = L.control({ position: 'topright' });
        locationControl.onAdd = function (map) {
            const div = L.DomUtil.create('div', 'leaflet-control-custom');
            div.innerHTML = '<div style="background: white; padding: 6px 8px; border-radius: 4px; box-shadow: 0 2px 6px rgba(0,0,0,0.3); font-size: 12px;"><i class="fa fa-fw fa-bullseye text-danger"></i> <strong>Klik disini:</strong>Untuk mengaktifkan titik lokasi</div>';
            return div;
        };
        locationControl.addTo(interactiveMap);

        isMapInitialized = true;
        console.log('✅ Smart address parsing map initialized');

        // Force map to recalculate size after modal animation
        setTimeout(() => {
            if (interactiveMap) {
                console.log('🔄 Invalidating map size...');
                interactiveMap.invalidateSize();
                console.log('✅ Map size invalidated, map should be visible now');
            }
        }, 500); // Increased delay to 500ms
    }

    /**
     * Legacy function - now uses showFinalMarker for consistency
     * Kept for backward compatibility with existing code
     */
    function setMapMarker(lat, lng) {
        showFinalMarker(lat, lng, 'Lokasi toko');
    }

    /**
     * Update coordinate fields with proper formatting
     * Requirements: 3.5
     * 
     * @param {number} lat - Latitude value
     * @param {number} lng - Longitude value
     */
    function updateCoordinateFields(lat, lng) {
        // Store full precision in hidden fields
        $('#latitude').val(lat.toFixed(8));
        $('#longitude').val(lng.toFixed(8));

        // Format coordinates with 6 decimal places for display
        // Show format: "Lat: -7.966600, Lng: 112.632600"
        // Update display real-time when coordinates change
        $('#coordinate_display').val(`Lat: ${lat.toFixed(6)}, Lng: ${lng.toFixed(6)}`);

        console.log('Coordinates updated:', lat, lng);
    }

    function showLocationStatus(lat, lng, isValid) {
        const statusDiv = $('#mapStatus');
        const infoDiv = $('#selectedLocationInfo');

        // Format coordinates with labels and monospace font for better readability
        const coordDisplay = `<code style="font-family: 'Courier New', monospace;">Lat: ${lat.toFixed(6)}, Lng: ${lng.toFixed(6)}</code>`;

        if (isValid) {
            infoDiv.html(`
                <strong>Koordinat:</strong> ${coordDisplay}<br>
                <span class="text-success"><i class="fas fa-check-circle"></i> Lokasi valid dalam wilayah Malang Raya</span>
            `);
            statusDiv.removeClass('d-none').show();
            $('#btnValidateLocation').show();
        } else {
            infoDiv.html(`
                <strong>Koordinat:</strong> ${coordDisplay}<br>
                <span class="text-danger"><i class="fas fa-exclamation-triangle"></i> Lokasi di luar wilayah Malang Raya</span>
            `);
            statusDiv.removeClass('d-none').show();
            $('#btnValidateLocation').hide();
        }
    }

    function isWithinMalangRegion(lat, lng) {
        return (lat >= MALANG_BOUNDS.south && lat <= MALANG_BOUNDS.north &&
            lng >= MALANG_BOUNDS.west && lng <= MALANG_BOUNDS.east);
    }

    /**
     * Perform reverse geocoding untuk validation
     * Requirements: 2.3
     * 
     * @param {number} lat - Latitude
     * @param {number} lng - Longitude
     */
    function performReverseGeocoding(lat, lng) {
        console.log(`🔄 Performing reverse geocoding for [${lat}, ${lng}]`);

        // Show loading status
        showSearchStatus('searching', 'Memvalidasi lokasi dengan reverse geocoding...');

        $.ajax({
            url: '/toko/validate-coordinates',
            type: 'POST',
            data: {
                latitude: lat,
                longitude: lng,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            timeout: 10000, // 10 second timeout
            success: function (response) {
                if (response.status === 'success') {
                    let message = '✓ Lokasi tervalidasi';

                    if (response.address_info && response.address_info.formatted_address) {
                        message += ': ' + response.address_info.formatted_address.substring(0, 100);
                        if (response.address_info.formatted_address.length > 100) {
                            message += '...';
                        }
                    }

                    showSearchStatus('success', message);
                    console.log('✅ Reverse geocoding successful:', response);
                } else {
                    showSearchStatus('warning', response.message || 'Validasi lokasi berhasil dengan peringatan');
                    console.warn('⚠️ Reverse geocoding warning:', response);
                }
            },
            error: function (xhr, status, error) {
                console.warn('⚠️ Reverse geocoding failed:', error);

                if (status === 'timeout') {
                    showSearchStatus('info', 'Validasi lokasi timeout. Koordinat tetap tersimpan.');
                } else {
                    showSearchStatus('info', 'Validasi lokasi gagal. Koordinat tetap tersimpan.');
                }
            }
        });
    }

    function resetMap() {
        // Use clearAllMarkers() for consistent marker management
        clearAllMarkers();

        $('#latitude').val('');
        $('#longitude').val('');
        $('#coordinate_display').val('');
        $('#mapStatus').hide();
        $('#btnValidateLocation').hide();
        $('#addressSearchStatus').hide();
        $('#detectedKelurahanInfo').hide();

        if (interactiveMap) {
            interactiveMap.setView(MALANG_CENTER, 12);
        }

        console.log('🔄 Map reset completed');
    }

    function centerMapToMalang() {
        if (interactiveMap) {
            interactiveMap.setView(MALANG_CENTER, 12);
        }
    }

    // ========================================
    // DATA LOADING & DISPLAY (Same as before, enhanced table)
    // ========================================
    function loadTokoData() {
        $.ajax({
            url: '/toko/list',
            type: 'GET',
            cache: false,
            beforeSend: function () {
                $('#toko-table-body').html('<tr><td colspan="9" class="text-center"><i class="fas fa-spinner fa-spin"></i> Memuat data...</td></tr>');
            },
            success: function (response) {
                displayTokoData(response.data);
            },
            error: function () {
                $('#toko-table-body').html('<tr><td colspan="9" class="text-center text-danger">Gagal memuat data</td></tr>');
                showAlert('danger', 'Gagal memuat data toko');
            }
        });
    }

    function displayTokoData(data) {
        if (data.length === 0) {
            $('#toko-table-body').html('<tr><td colspan="9" class="text-center">Tidak ada data</td></tr>');
            return;
        }

        let tableHtml = '';
        data.forEach(function (item, index) {
            const hasCoords = item.latitude && item.longitude;
            const quality = item.geocoding_quality || 'unknown';

            let statusBadge = '';
            let koordinatInfo = '';

            if (hasCoords) {
                const qualityClass = getQualityClass(quality);
                const qualityText = getQualityText(quality);
                const qualityScore = item.geocoding_score || 'N/A';

                // Enhanced quality badge with tooltip showing quality score
                statusBadge = `<span class="badge badge-${qualityClass}" 
                    data-toggle="tooltip" 
                    data-placement="top" 
                    title="Quality Score: ${qualityScore}">${qualityText}</span>`;

                if (item.geocoding_provider === 'interactive_map') {
                    statusBadge = `<span class="badge badge-success" 
                        data-toggle="tooltip" 
                        data-placement="top" 
                        title="Quality Score: 100 (Manual Selection)">
                        <i class="fas fa-mouse-pointer"></i> Peta Interaktif
                    </span>`;
                }

                koordinatInfo = `
                    <small class="d-block">
                        <i class="fas fa-map-marker-alt text-success"></i> 
                        <strong>${parseFloat(item.latitude).toFixed(4)}, ${parseFloat(item.longitude).toFixed(4)}</strong>
                    </small>
                    <small class="text-muted">${item.geocoding_provider || 'Unknown'}</small>
                `;
            } else {
                statusBadge = `<span class="badge badge-warning" 
                    data-toggle="tooltip" 
                    data-placement="top" 
                    title="Koordinat GPS belum tersedia">
                    <i class="fas fa-map-marker"></i> Perlu GPS
                </span>`;
                koordinatInfo = '<small class="text-muted"><i class="fas fa-exclamation-triangle"></i> Belum ada koordinat</small>';
            }

            const alamatShort = item.alamat.length > 50 ? item.alamat.substring(0, 47) + '...' : item.alamat;
            const wilayahShort = `${item.wilayah_kelurahan}, ${item.wilayah_kecamatan}`;

            tableHtml += `
                <tr id="row-${item.toko_id}">
                    <td class="text-center">${index + 1}</td>
                    <td><strong class="text-primary">${item.toko_id}</strong></td>
                    <td>
                        <div class="font-weight-bold">${item.nama_toko}</div>
                        <small class="text-muted">${item.pemilik}</small>
                    </td>
                    <td>
                        <div title="${item.alamat}">${alamatShort}</div>
                        <small class="text-muted">${wilayahShort}</small>
                    </td>
                    <td><small class="text-muted">${item.wilayah_kota_kabupaten}</small></td>
                    <td><span class="text-nowrap">${item.nomer_telpon}</span></td>
                    <td>${koordinatInfo}</td>
                    <td>${statusBadge}</td>
                    <td>
                        <div class="btn-group btn-group-sm" role="group">
                            <button class="btn btn-info btn-edit" data-id="${item.toko_id}" title="Edit Toko">
                                <i class="fas fa-edit"></i>
                            </button>
                            ${hasCoords ? `
                                <button class="btn btn-success btn-detail" data-id="${item.toko_id}" title="Detail GPS">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                                <button class="btn btn-primary btn-maps" onclick="openGoogleMaps(${item.latitude}, ${item.longitude})" title="Buka di Google Maps">
                                    <i class="fas fa-external-link-alt"></i>
                                </button>
                            ` : ''}
                            <button class="btn btn-danger btn-delete" data-id="${item.toko_id}" data-name="${item.nama_toko}" title="Hapus Toko">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });

        $('#toko-table-body').html(tableHtml);

        // Initialize tooltips for quality badges
        $('[data-toggle="tooltip"]').tooltip();
    }

    // ========================================
    // QUALITY HELPERS & MODAL HANDLERS (Same as before)
    // ========================================
    function getQualityClass(quality) {
        const classes = {
            'excellent': 'success',
            'good': 'primary',
            'fair': 'warning',
            'poor': 'danger',
            'very poor': 'danger',
            'failed': 'secondary',
            'unknown': 'info'
        };
        return classes[quality] || 'info';
    }

    function getQualityText(quality) {
        const texts = {
            'excellent': 'Sangat Akurat',
            'good': 'Akurat',
            'fair': 'Cukup Akurat',
            'poor': 'Kurang Akurat',
            'very poor': 'Tidak Akurat',
            'failed': 'Gagal',
            'unknown': 'GPS Aktif'
        };
        return texts[quality] || 'GPS Aktif';
    }

    $('#btnTambah').click(function () {
        resetForm();
        $('#modalTokoLabel').html('<i class="fas fa-store"></i> Tambah Toko dengan Smart Address');
        $('#mode').val('add');
        generateTokoId();
        loadWilayahKota();
        $('#modalToko').modal('show');

        $('#modalToko').on('shown.bs.modal', function () {
            if (!isMapInitialized) {
                initializeInteractiveMap();
            } else {
                setTimeout(() => {
                    if (interactiveMap) {
                        interactiveMap.invalidateSize();
                        resetMap();
                    }
                }, 100);
            }
        });
    });

    $('#btnResetMap').click(function () {
        resetMap();
    });

    $('#btnCenterMalang').click(function () {
        centerMapToMalang();
    });

    $('#btnValidateLocation').click(function () {
        const lat = parseFloat($('#latitude').val());
        const lng = parseFloat($('#longitude').val());

        if (lat && lng) {
            validateMapCoordinates(lat, lng);
        } else {
            showAlert('warning', 'Silakan pilih lokasi pada peta terlebih dahulu');
        }
    });

    function validateMapCoordinates(lat, lng) {
        $('#btnValidateLocation').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Validasi...');

        $.ajax({
            url: '/toko/validate-coordinates',
            type: 'POST',
            data: {
                latitude: lat,
                longitude: lng,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.status === 'success') {
                    let message = response.message;
                    if (response.address_info && response.address_info.formatted_address) {
                        message += '<br><small><strong>Alamat:</strong> ' + response.address_info.formatted_address + '</small>';
                    }
                    showAlert('success', message);
                } else {
                    showAlert('warning', response.message);
                }
            },
            error: function () {
                showAlert('danger', 'Gagal melakukan validasi koordinat');
            },
            complete: function () {
                $('#btnValidateLocation').prop('disabled', false).html('<i class="fas fa-check-circle"></i> Validasi Lokasi');
            }
        });
    }

    // ========================================
    // FORM FUNCTIONS & EVENT HANDLERS
    // ========================================
    function generateTokoId() {
        $.ajax({
            url: '/toko/generate-kode',
            type: 'GET',
            success: function (response) {
                if (response.status === 'success') {
                    $('#toko_id').val(response.kode);
                }
            },
            error: function () {
                showAlert('warning', 'Gagal generate ID toko');
            }
        });
    }

    function loadWilayahKota() {
        $.ajax({
            url: '/toko/wilayah/kota',
            type: 'GET',
            beforeSend: function () {
                $('#wilayah_kota_id').html('<option value="">Memuat...</option>').prop('disabled', true);
            },
            success: function (response) {
                if (response.status === 'success') {
                    let options = '<option value="">-- Pilih Kota/Kabupaten --</option>';
                    response.data.forEach(function (item) {
                        options += `<option value="${item.id}" data-nama="${item.nama}">${item.nama}</option>`;
                    });
                    $('#wilayah_kota_id').html(options).prop('disabled', false);
                }
            },
            error: function () {
                $('#wilayah_kota_id').html('<option value="">-- Pilih Kota/Kabupaten --</option>').prop('disabled', false);
            }
        });
    }

    function loadKecamatan(kotaId) {
        $.ajax({
            url: '/toko/wilayah/kecamatan',
            type: 'GET',
            data: { kota_id: kotaId },
            beforeSend: function () {
                $('#wilayah_kecamatan_id').html('<option value="">Memuat...</option>').prop('disabled', true);
            },
            success: function (response) {
                if (response.status === 'success') {
                    let options = '<option value="">-- Pilih Kecamatan --</option>';
                    response.data.forEach(function (item) {
                        options += `<option value="${item.id}" data-nama="${item.nama}">${item.nama}</option>`;
                    });
                    $('#wilayah_kecamatan_id').html(options).prop('disabled', false);
                }
            },
            error: function () {
                $('#wilayah_kecamatan_id').html('<option value="">-- Pilih Kecamatan --</option>').prop('disabled', false);
            }
        });
    }

    function loadKelurahan(kotaId, kecamatanId) {
        $.ajax({
            url: '/toko/wilayah/kelurahan',
            type: 'GET',
            data: { kota_id: kotaId, kecamatan_id: kecamatanId },
            beforeSend: function () {
                $('#wilayah_kelurahan_id').html('<option value="">Memuat...</option>').prop('disabled', true);
            },
            success: function (response) {
                if (response.status === 'success') {
                    let options = '<option value="">-- Pilih Kelurahan --</option>';
                    response.data.forEach(function (item) {
                        options += `<option value="${item.id}" data-nama="${item.nama}">${item.nama}</option>`;
                    });
                    $('#wilayah_kelurahan_id').html(options).prop('disabled', false);
                }
            },
            error: function () {
                $('#wilayah_kelurahan_id').html('<option value="">-- Pilih Kelurahan --</option>').prop('disabled', false);
            }
        });
    }

    $(document).on('change', '#wilayah_kota_id', function () {
        const kotaId = $(this).val();
        const kotaNama = $(this).find('option:selected').data('nama') || '';

        $('#wilayah_kota_kabupaten').val(kotaNama);
        $('#wilayah_kecamatan_id').html('<option value="">-- Pilih Kecamatan --</option>');
        $('#wilayah_kelurahan_id').html('<option value="">-- Pilih Kelurahan --</option>');
        $('#wilayah_kecamatan').val('');
        $('#wilayah_kelurahan').val('');

        if (kotaId) {
            loadKecamatan(kotaId);
            $('#wilayah_kecamatan_id').prop('disabled', false);
        } else {
            $('#wilayah_kecamatan_id, #wilayah_kelurahan_id').prop('disabled', true);
        }
    });

    $(document).on('change', '#wilayah_kecamatan_id', function () {
        const kotaId = $('#wilayah_kota_id').val();
        const kecamatanId = $(this).val();
        const kecamatanNama = $(this).find('option:selected').data('nama') || '';

        $('#wilayah_kecamatan').val(kecamatanNama);
        $('#wilayah_kelurahan_id').html('<option value="">-- Pilih Kelurahan --</option>');
        $('#wilayah_kelurahan').val('');

        if (kotaId && kecamatanId) {
            loadKelurahan(kotaId, kecamatanId);
            $('#wilayah_kelurahan_id').prop('disabled', false);
        } else {
            $('#wilayah_kelurahan_id').prop('disabled', true);
        }
    });

    $(document).on('change', '#wilayah_kelurahan_id', function () {
        const kelurahanNama = $(this).find('option:selected').data('nama') || '';
        $('#wilayah_kelurahan').val(kelurahanNama);
    });

    $('#formToko').submit(function (e) {
        e.preventDefault();

        clearErrors();

        const lat = $('#latitude').val();
        const lng = $('#longitude').val();

        if (!lat || !lng) {
            showAlert('danger', 'Koordinat GPS wajib diisi! Ketik alamat dengan nama kelurahan untuk deteksi otomatis, lalu klik pada peta.');
            $('#coordinate_display').addClass('is-invalid');
            return false;
        }

        if (!isWithinMalangRegion(parseFloat(lat), parseFloat(lng))) {
            showAlert('danger', 'Koordinat berada di luar wilayah Malang Raya! Silakan pilih lokasi yang sesuai.');
            $('#coordinate_display').addClass('is-invalid');
            return false;
        }

        if (!$('#wilayah_kota_kabupaten').val()) {
            $('#wilayah_kota_id').addClass('is-invalid');
            $('#error-wilayah_kota_kabupaten').text('Kota/Kabupaten harus dipilih');
            showAlert('warning', 'Silakan lengkapi data wilayah');
            return false;
        }

        if (!$('#wilayah_kecamatan').val()) {
            $('#wilayah_kecamatan_id').addClass('is-invalid');
            $('#error-wilayah_kecamatan').text('Kecamatan harus dipilih');
            showAlert('warning', 'Silakan lengkapi data wilayah');
            return false;
        }

        if (!$('#wilayah_kelurahan').val()) {
            $('#wilayah_kelurahan_id').addClass('is-invalid');
            $('#error-wilayah_kelurahan').text('Kelurahan harus dipilih');
            showAlert('warning', 'Silakan lengkapi data wilayah');
            return false;
        }

        const mode = $('#mode').val();
        const url = mode === 'add' ? '/toko' : '/toko/' + $('#toko_id').val();
        const method = mode === 'add' ? 'POST' : 'PUT';

        $('#btnSimpan').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');

        $.ajax({
            url: url,
            type: method,
            data: $(this).serialize(),
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                $('#modalToko').modal('hide');
                loadTokoData();

                let message = response.message;
                if (response.coordinate_info) {
                    const info = response.coordinate_info;
                    message += `<br><small><strong>Lokasi:</strong> ${info.source} | Akurasi: ${info.accuracy}</small>`;
                }

                showAlert('success', message);
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    showValidationErrors(errors);

                    if (errors.latitude || errors.longitude) {
                        $('#coordinate_display').addClass('is-invalid');
                        showAlert('danger', 'Koordinat GPS tidak valid. Silakan pilih lokasi pada peta.');
                    }
                } else {
                    showAlert('danger', xhr.responseJSON?.message || 'Terjadi kesalahan saat menyimpan data');
                }
            },
            complete: function () {
                $('#btnSimpan').prop('disabled', false).html('<i class="fas fa-save"></i> Simpan Toko');
            }
        });
    });

    // Edit, delete, detail functions remain the same...
    $(document).on('click', '.btn-edit', function () {
        const id = $(this).data('id');

        resetForm();
        loadWilayahKota();

        $.ajax({
            url: '/toko/' + id + '/edit',
            type: 'GET',
            success: function (response) {
                $('#modalTokoLabel').html('<i class="fas fa-edit"></i> Edit Toko dengan Smart Address');
                $('#mode').val('edit');

                const data = response.data;
                $('#toko_id').val(data.toko_id);
                $('#nama_toko').val(data.nama_toko);
                $('#pemilik').val(data.pemilik);
                $('#alamat').val(data.alamat);
                $('#nomer_telpon').val(data.nomer_telpon);

                if (data.latitude && data.longitude) {
                    $('#latitude').val(data.latitude);
                    $('#longitude').val(data.longitude);
                    // Format with labels for better readability
                    $('#coordinate_display').val(`Lat: ${parseFloat(data.latitude).toFixed(6)}, Lng: ${parseFloat(data.longitude).toFixed(6)}`);
                }

                $('#wilayah_kota_kabupaten').val(data.wilayah_kota_kabupaten);
                $('#wilayah_kecamatan').val(data.wilayah_kecamatan);
                $('#wilayah_kelurahan').val(data.wilayah_kelurahan);

                setTimeout(() => setWilayahDropdowns(data), 100);

                $('#modalToko').modal('show');

                $('#modalToko').on('shown.bs.modal', function () {
                    if (!isMapInitialized) {
                        initializeInteractiveMap();
                    } else {
                        if (interactiveMap) {
                            interactiveMap.invalidateSize();
                        }
                    }

                    if (data.latitude && data.longitude) {
                        setTimeout(() => {
                            setMapMarker(parseFloat(data.latitude), parseFloat(data.longitude));
                            showLocationStatus(parseFloat(data.latitude), parseFloat(data.longitude), true);
                        }, 500);
                    }
                });
            },
            error: function (xhr) {
                showAlert('danger', xhr.responseJSON?.message || 'Gagal mengambil data toko');
            }
        });
    });

    function setWilayahDropdowns(data) {
        $('#wilayah_kota_id option').each(function () {
            if ($(this).text() === data.wilayah_kota_kabupaten) {
                $(this).prop('selected', true).trigger('change');

                setTimeout(() => {
                    $('#wilayah_kecamatan_id option').each(function () {
                        if ($(this).text() === data.wilayah_kecamatan) {
                            $(this).prop('selected', true).trigger('change');

                            setTimeout(() => {
                                $('#wilayah_kelurahan_id option').each(function () {
                                    if ($(this).text() === data.wilayah_kelurahan) {
                                        $(this).prop('selected', true).trigger('change');
                                    }
                                });
                            }, 150);
                        }
                    });
                }, 150);
            }
        });
    }

    $(document).on('click', '.btn-delete', function () {
        const id = $(this).data('id');
        const name = $(this).data('name');

        $('#delete-item-name').text(name);
        $('#btnDelete').data('id', id);
        $('#deleteModal').modal('show');
    });

    $('#btnDelete').click(function () {
        const id = $(this).data('id');

        $('#btnDelete').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menghapus...');

        $.ajax({
            url: '/toko/' + id,
            type: 'DELETE',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                $('#deleteModal').modal('hide');
                loadTokoData();
                showAlert('success', response.message);
            },
            error: function (xhr) {
                $('#deleteModal').modal('hide');
                showAlert('danger', xhr.responseJSON?.message || 'Gagal menghapus data toko');
            },
            complete: function () {
                $('#btnDelete').prop('disabled', false).html('<i class="fas fa-trash"></i> Hapus');
            }
        });
    });

    $(document).on('click', '.btn-detail', function () {
        const id = $(this).data('id');
        showDetailKoordinat(id);
    });

    function showDetailKoordinat(tokoId) {
        $.ajax({
            url: '/toko/' + tokoId,
            type: 'GET',
            success: function (response) {
                if (response.status === 'success') {
                    const toko = response.data;

                    if (!toko.latitude || !toko.longitude) {
                        showAlert('warning', 'Toko ini belum memiliki koordinat GPS');
                        return;
                    }

                    showDetailModal(toko);
                }
            },
            error: function () {
                showAlert('danger', 'Gagal mengambil detail koordinat');
            }
        });
    }

    function showDetailModal(toko) {
        const qualityClass = getQualityClass(toko.geocoding_quality);
        const qualityText = getQualityText(toko.geocoding_quality);

        let sourceInfo = toko.geocoding_provider || 'Unknown';
        if (toko.geocoding_provider === 'interactive_map') {
            sourceInfo = '<span class="text-success"><i class="fas fa-mouse-pointer"></i> Peta Interaktif (User Selected)</span>';
        }

        const content = `
            <div class="detail-content">
                <h6><i class="fas fa-store"></i> ${toko.nama_toko} (${toko.toko_id})</h6>
                <hr>
                <div class="row">
                    <div class="col-md-6">
                        <strong>Koordinat GPS:</strong><br>
                        <code class="h5">${toko.latitude}, ${toko.longitude}</code><br>
                        <span class="badge badge-${qualityClass}">${qualityText}</span>
                    </div>
                    <div class="col-md-6">
                        <strong>Sumber:</strong><br>${sourceInfo}<br>
                        <strong>Score:</strong> ${toko.geocoding_score || 0}/100<br>
                        <strong>Confidence:</strong> ${((toko.geocoding_confidence || 0) * 100).toFixed(1)}%
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-12">
                        <strong>Alamat:</strong><br>
                        ${toko.alamat}<br>
                        <small class="text-muted">${toko.wilayah_kelurahan}, ${toko.wilayah_kecamatan}, ${toko.wilayah_kota_kabupaten}</small>
                    </div>
                </div>
                ${toko.alamat_lengkap_geocoding ? `
                    <div class="row mt-2">
                        <div class="col-12">
                            <strong>Alamat Hasil Geocoding:</strong><br>
                            <small class="text-muted">${toko.alamat_lengkap_geocoding}</small>
                        </div>
                    </div>
                ` : ''}
                <hr>
                <div class="row">
                    <div class="col-12">
                        <button type="button" class="btn btn-primary btn-sm" onclick="openGoogleMaps(${toko.latitude}, ${toko.longitude})">
                            <i class="fas fa-external-link-alt"></i> Google Maps
                        </button>
                        <button type="button" class="btn btn-info btn-sm ml-2" onclick="copyToClipboard('${toko.latitude}, ${toko.longitude}')">
                            <i class="fas fa-copy"></i> Copy Koordinat
                        </button>
                    </div>
                </div>
            </div>
        `;

        const modalHtml = `
            <div class="modal fade" id="detailModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Detail Koordinat GPS</h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">${content}</div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('#detailModal').remove();
        $('body').append(modalHtml);
        $('#detailModal').modal('show');
    }

    // ========================================
    // UTILITY FUNCTIONS
    // ========================================

    window.openGoogleMaps = function (lat, lng) {
        const url = `https://www.google.com/maps?q=${lat},${lng}`;
        window.open(url, '_blank');
    };

    window.copyToClipboard = function (text) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(() => {
                showAlert('success', 'Koordinat berhasil disalin: ' + text);
            });
        } else {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            showAlert('success', 'Koordinat berhasil disalin: ' + text);
        }
    };

    function resetForm() {
        $('#formToko')[0].reset();
        clearErrors();

        $('#wilayah_kota_id').html('<option value="">-- Pilih Kota/Kabupaten --</option>');
        $('#wilayah_kecamatan_id').html('<option value="">-- Pilih Kecamatan --</option>').prop('disabled', true);
        $('#wilayah_kelurahan_id').html('<option value="">-- Pilih Kelurahan --</option>').prop('disabled', true);

        $('#wilayah_kota_kabupaten, #wilayah_kecamatan, #wilayah_kelurahan').val('');
        $('#latitude, #longitude, #coordinate_display').val('');
        $('#mapStatus').hide();
        $('#btnValidateLocation').hide();
        $('#addressSearchStatus').hide();

        if (searchTimeout) {
            clearTimeout(searchTimeout);
            searchTimeout = null;
        }
    }

    // ========================================
    // MARKER MANAGEMENT FUNCTIONS
    // ========================================

    // Global variable for tolerance circle
    let toleranceCircle = null;

    /**
     * Show final marker with draggable functionality and tolerance circle
     * Requirements: 2.1, 2.2, 7.1, 7.2
     * 
     * @param {number} lat - Latitude
     * @param {number} lng - Longitude
     * @param {string} label - Marker label/popup text
     */
    function showFinalMarker(lat, lng, label) {
        if (!interactiveMap) {
            console.warn('⚠️ Map not initialized, cannot show marker');
            return;
        }

        console.log(`📍 Showing final marker at [${lat}, ${lng}]`);

        // Clear previous markers and circles
        clearAllMarkers();

        // Create draggable marker with custom icon
        const markerIcon = L.divIcon({
            className: 'final-marker',
            html: `<div style="background-color: #dc3545; width: 24px; height: 24px; border-radius: 50%; border: 4px solid white; box-shadow: 0 4px 12px rgba(0,0,0,0.6);"></div>`,
            iconSize: [32, 32],
            iconAnchor: [16, 16]
        });

        currentMarker = L.marker([lat, lng], {
            icon: markerIcon,
            draggable: true,
            autoPan: true
        }).addTo(interactiveMap);

        // Bind popup with location info
        const popupContent = `
            <div style="text-align: center; min-width: 200px;">
                <strong><i class="fas fa-map-marker-alt" style="color: #dc3545;"></i> Lokasi Toko</strong><br>
                <small style="color: #666;">${label}</small><br>
                <code style="font-size: 11px; background: #f8f9fa; padding: 2px 6px; border-radius: 3px;">
                    ${lat.toFixed(6)}, ${lng.toFixed(6)}
                </code><br>
                <small style="color: #28a745; margin-top: 4px; display: block;">
                    <i class="fas fa-arrows-alt"></i> Marker dapat digeser
                </small>
            </div>
        `;
        currentMarker.bindPopup(popupContent).openPopup();

        // Add tolerance circle (50 meters radius)
        toleranceCircle = L.circle([lat, lng], {
            radius: MAX_TOLERANCE_METERS,
            color: '#28a745',
            fillColor: '#28a745',
            fillOpacity: 0.1,
            weight: 2,
            dashArray: '5, 5'
        }).addTo(interactiveMap);

        // Bind popup to circle
        toleranceCircle.bindPopup(`
            <div style="text-align: center;">
                <strong><i class="fas fa-circle-notch"></i> Radius Toleransi</strong><br>
                <span class="badge badge-success">${MAX_TOLERANCE_METERS} meter</span><br>
                <small style="color: #666; margin-top: 4px; display: block;">
                    Marker harus berada dalam radius ini
                </small>
            </div>
        `);

        // Event: Marker drag start
        currentMarker.on('dragstart', function (e) {
            console.log('🖱️ Marker drag started');
            if (toleranceCircle) {
                toleranceCircle.setStyle({ color: '#ffc107', fillColor: '#ffc107' });
            }
        });

        // Event: Marker dragging
        currentMarker.on('drag', function (e) {
            const newLatLng = e.target.getLatLng();

            // Update tolerance circle position
            if (toleranceCircle) {
                toleranceCircle.setLatLng(newLatLng);
            }

            // Update coordinate fields in real-time
            updateCoordinateFields(newLatLng.lat, newLatLng.lng);
        });

        // Event: Marker drag end
        currentMarker.on('dragend', function (e) {
            const newLatLng = e.target.getLatLng();
            const newLat = newLatLng.lat;
            const newLng = newLatLng.lng;

            console.log(`📍 Marker dragged to [${newLat}, ${newLng}]`);

            // Validate if still within Malang region
            if (!isWithinMalangRegion(newLat, newLng)) {
                showAlert('warning', 'Lokasi di luar wilayah Malang Raya! Marker dikembalikan ke posisi sebelumnya.');
                currentMarker.setLatLng([lat, lng]);
                if (toleranceCircle) {
                    toleranceCircle.setLatLng([lat, lng]);
                }
                updateCoordinateFields(lat, lng);
                return;
            }

            // Update coordinate fields
            updateCoordinateFields(newLat, newLng);

            // Update popup content
            currentMarker.setPopupContent(`
                <div style="text-align: center; min-width: 200px;">
                    <strong><i class="fas fa-map-marker-alt" style="color: #dc3545;"></i> Lokasi Toko</strong><br>
                    <small style="color: #666;">Posisi disesuaikan manual</small><br>
                    <code style="font-size: 11px; background: #f8f9fa; padding: 2px 6px; border-radius: 3px;">
                        ${newLat.toFixed(6)}, ${newLng.toFixed(6)}
                    </code><br>
                    <small style="color: #28a745; margin-top: 4px; display: block;">
                        <i class="fas fa-check-circle"></i> Posisi tersimpan
                    </small>
                </div>
            `);

            // Validate tolerance if initial position exists
            if (initialGeocodedPosition) {
                validateMarkerTolerance(newLat, newLng);
            }

            // Reset circle color
            if (toleranceCircle) {
                toleranceCircle.setStyle({ color: '#28a745', fillColor: '#28a745' });
            }

            // Show location status
            showLocationStatus(newLat, newLng, true);

            console.log('✅ Marker position updated successfully');
        });

        // Pan to marker location
        interactiveMap.panTo([lat, lng]);

        console.log('✅ Final marker and tolerance circle displayed');
    }

    /**
     * Update marker position (for street detection)
     * Requirements: 1.3
     * 
     * @param {number} lat - Latitude
     * @param {number} lng - Longitude
     */
    function updateMarkerPosition(lat, lng) {
        if (!interactiveMap) {
            console.warn('⚠️ Map not initialized, cannot update marker');
            return;
        }

        console.log(`🔄 Updating marker position to [${lat}, ${lng}]`);

        if (currentMarker) {
            // Update existing marker position
            currentMarker.setLatLng([lat, lng]);

            // Update tolerance circle position
            if (toleranceCircle) {
                toleranceCircle.setLatLng([lat, lng]);
            }

            // Update popup
            currentMarker.setPopupContent(`
                <div style="text-align: center; min-width: 200px;">
                    <strong><i class="fas fa-map-marker-alt" style="color: #dc3545;"></i> Lokasi Toko</strong><br>
                    <small style="color: #666;">Dari deteksi jalan</small><br>
                    <code style="font-size: 11px; background: #f8f9fa; padding: 2px 6px; border-radius: 3px;">
                        ${lat.toFixed(6)}, ${lng.toFixed(6)}
                    </code>
                </div>
            `).openPopup();

            // Pan to new position
            interactiveMap.panTo([lat, lng]);
        } else {
            // Create new marker if doesn't exist
            showFinalMarker(lat, lng, 'Lokasi dari deteksi jalan');
        }

        // Update coordinate fields
        updateCoordinateFields(lat, lng);

        console.log('✅ Marker position updated');
    }

    /**
     * Clear all markers and circles from map
     * Requirements: 2.2
     */
    function clearAllMarkers() {
        if (!interactiveMap) return;

        console.log('🧹 Clearing all markers and circles');

        // Remove current marker
        if (currentMarker) {
            interactiveMap.removeLayer(currentMarker);
            currentMarker = null;
        }

        // Remove preview marker
        if (previewMarker) {
            interactiveMap.removeLayer(previewMarker);
            previewMarker = null;
        }

        // Remove tolerance circle
        if (toleranceCircle) {
            interactiveMap.removeLayer(toleranceCircle);
            toleranceCircle = null;
        }

        console.log('✅ All markers and circles cleared');
    }

    // ========================================
    // TOLERANCE VALIDATION (50M ACCURACY)
    // ========================================

    /**
     * Calculate distance between two coordinates in meters using Haversine formula
     */
    function calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371000; // Earth radius in meters
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;

        const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
            Math.sin(dLon / 2) * Math.sin(dLon / 2);

        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        const distance = R * c;

        return Math.round(distance * 100) / 100;
    }

    /**
     * Show tolerance status badge
     */
    function showToleranceStatus(distanceMeters, withinTolerance) {
        const statusHtml = withinTolerance
            ? `<span class="badge badge-success"><i class="fas fa-check-circle"></i> Dalam Toleransi (${distanceMeters}m)</span>`
            : `<span class="badge badge-warning"><i class="fas fa-exclamation-triangle"></i> Melebihi Toleransi (${distanceMeters}m, maks: ${MAX_TOLERANCE_METERS}m)</span>`;

        let $toleranceStatus = $('#toleranceStatus');
        if ($toleranceStatus.length === 0) {
            $('#mapContainer').after('<div id="toleranceStatus" class="mt-2"></div>');
            $toleranceStatus = $('#toleranceStatus');
        }
        $toleranceStatus.html(statusHtml).show();
    }

    /**
     * Show tolerance warning when marker moved > 50m
     */
    function showToleranceWarning(distanceMeters) {
        Swal.fire({
            icon: 'warning',
            title: 'Marker Digeser Terlalu Jauh',
            html: `
                <p>Marker telah digeser <strong>${distanceMeters} meter</strong> dari posisi hasil geocoding.</p>
                <p>Toleransi maksimal: <strong>${MAX_TOLERANCE_METERS} meter</strong></p>
                <p>Selisih: <strong>${Math.round(distanceMeters - MAX_TOLERANCE_METERS)} meter</strong></p>
                <hr>
                <p class="text-muted">Pastikan lokasi marker sudah tepat sesuai alamat toko.</p>
            `,
            showCancelButton: true,
            confirmButtonText: 'Tetap Gunakan Posisi Ini',
            cancelButtonText: 'Kembalikan ke Posisi Geocoding',
            confirmButtonColor: '#f39c12',
            cancelButtonColor: '#3085d6'
        }).then((result) => {
            if (result.dismiss === Swal.DismissReason.cancel && initialGeocodedPosition && currentMarker) {
                currentMarker.setLatLng([initialGeocodedPosition.lat, initialGeocodedPosition.lng]);
                updateCoordinateFields(initialGeocodedPosition.lat, initialGeocodedPosition.lng);
                showToleranceStatus(0, true);
                console.log('🔄 Marker reset to initial geocoded position');
            }
        });
    }

    /**
     * Store initial geocoded position for tolerance tracking
     */
    function storeInitialGeocodedPosition(lat, lng, address) {
        initialGeocodedPosition = { lat: lat, lng: lng, address: address };
        console.log('📍 Stored initial geocoded position:', initialGeocodedPosition);
        showToleranceStatus(0, true);
    }

    /**
     * Validate marker position against tolerance
     */
    function validateMarkerTolerance(newLat, newLng) {
        if (!initialGeocodedPosition) return;

        const distance = calculateDistance(
            initialGeocodedPosition.lat,
            initialGeocodedPosition.lng,
            newLat,
            newLng
        );

        const withinTolerance = distance <= MAX_TOLERANCE_METERS;
        console.log(`📏 Distance from geocoded position: ${distance}m (tolerance: ${MAX_TOLERANCE_METERS}m)`);

        showToleranceStatus(distance, withinTolerance);

        if (!withinTolerance) {
            showToleranceWarning(distance);
        }
    }

    // ========================================
    // FORM MANAGEMENT
    // ========================================

    function clearErrors() {
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
    }

    function showValidationErrors(errors) {
        Object.keys(errors).forEach(function (field) {
            $('#' + field).addClass('is-invalid');
            $('#error-' + field).text(errors[field][0]);
        });
    }

    function showAlert(type, message) {
        // Mapping type ke SweetAlert2 icon
        const iconMap = {
            'danger': 'error',
            'warning': 'warning',
            'success': 'success',
            'info': 'info'
        };

        const titleMap = {
            'danger': 'Error',
            'warning': 'Perhatian',
            'success': 'Berhasil',
            'info': 'Informasi'
        };

        console.log(`📢 showAlert called: [${type}] ${message}`);

        // Gunakan SweetAlert2 untuk validasi (danger, warning) dan success
        if (type === 'danger' || type === 'warning' || type === 'success') {
            // Cek apakah Swal tersedia
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: iconMap[type] || 'info',
                    title: titleMap[type] || 'Notifikasi',
                    html: message,
                    confirmButtonText: 'OK',
                    confirmButtonColor: type === 'danger' ? '#dc3545' : (type === 'warning' ? '#ffc107' : '#28a745')
                });
            } else {
                // Fallback jika Swal tidak tersedia
                console.warn('SweetAlert2 not available, using native alert');
                alert(`${titleMap[type]}: ${message.replace(/<[^>]*>/g, '')}`);
            }
        } else {
            // Untuk info, hanya log ke console
            console.log(`📢 Alert (hidden): [${type}] ${message}`);
        }
    }
});