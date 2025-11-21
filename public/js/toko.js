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
     * Parse Indonesian standard address format
     * Format: Jl. [nama jalan] No. [nomor], [Kelurahan], Kec. [Kecamatan], Kota [Kota], [Provinsi] [Kode Pos]
     * 
     * @param {string} alamat - Full address string
     * @returns {object} Parsed address components
     */
    function parseIndonesianAddress(alamat) {
        if (!alamat || typeof alamat !== 'string') {
            return {
                street: '',
                kelurahan: '',
                kecamatan: '',
                kota: '',
                provinsi: '',
                postalCode: ''
            };
        }

        const result = {
            street: '',
            kelurahan: '',
            kecamatan: '',
            kota: '',
            provinsi: '',
            postalCode: ''
        };

        // Split by comma separator
        const parts = alamat.split(',').map(part => part.trim()).filter(part => part.length > 0);

        if (parts.length === 0) {
            return result;
        }

        // Process each part
        parts.forEach((part, index) => {
            const partLower = part.toLowerCase();

            // Detect street pattern (usually first part with Jl., Gang, No.)
            if (index === 0 || partLower.match(/\b(jl\.|jalan|gang|gg\.|no\.|nomor)\b/i)) {
                if (!result.street) {
                    result.street = part;
                }
            }

            // Detect kecamatan pattern: "Kec. [nama]" or "Kecamatan [nama]"
            if (partLower.match(/\b(kec\.|kecamatan)\b/i)) {
                result.kecamatan = part
                    .replace(/\bkec\.\s*/i, '')
                    .replace(/\bkecamatan\s*/i, '')
                    .trim();
            }

            // Detect kota pattern: "Kota [nama]" or "Kabupaten [nama]"
            else if (partLower.match(/\b(kota|kabupaten)\b/i)) {
                result.kota = part.trim();
            }

            // Detect provinsi pattern
            else if (partLower.match(/\b(jawa timur|jawa tengah|jawa barat|east java)\b/i)) {
                result.provinsi = part.trim();
            }

            // Detect postal code (5 digits)
            else if (partLower.match(/\b\d{5}\b/)) {
                result.postalCode = part.match(/\d{5}/)[0];
            }

            // Extract kelurahan from position 2 (after street, before kecamatan)
            // This is the standard Indonesian address format
            else if (index === 1 && !result.kelurahan && !partLower.match(/\b(kec\.|kecamatan|kota|kabupaten|rt|rw)\b/i)) {
                result.kelurahan = part;
            }

            // Fallback: if not matched yet and doesn't contain special keywords, might be kelurahan
            else if (!result.kelurahan &&
                !partLower.match(/\b(jl\.|jalan|gang|no\.|kec\.|kecamatan|kota|kabupaten|rt|rw)\b/i) &&
                index > 0) {
                result.kelurahan = part;
            }
        });

        // Normalize all components
        Object.keys(result).forEach(key => {
            if (result[key]) {
                result[key] = normalizeAddressComponent(result[key]);
            }
        });

        console.log('📍 [ADDRESS PARSER] Parsed Indonesian address:');
        console.log('   🏠 Street:', result.street || '(not detected)');
        console.log('   🏘️  Kelurahan:', result.kelurahan || '(not detected)');
        console.log('   🏙️  Kecamatan:', result.kecamatan || '(not detected)');
        console.log('   🌆 Kota:', result.kota || '(not detected)');
        console.log('   🗺️  Provinsi:', result.provinsi || '(not detected)');
        console.log('   📮 Postal Code:', result.postalCode || '(not detected)');

        return result;
    }

    /**
     * Normalize address component by cleaning text
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

        // Normalize common abbreviations
        normalized = normalized
            .replace(/\bJl\.\s*/gi, 'Jalan ')
            .replace(/\bGg\.\s*/gi, 'Gang ')
            .replace(/\bNo\.\s*/gi, 'Nomor ')
            .replace(/\bKec\.\s*/gi, 'Kecamatan ')
            .replace(/\bKel\.\s*/gi, 'Kelurahan ');

        // Remove duplicate spaces again after replacements
        normalized = normalized.replace(/\s+/g, ' ').trim();

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

            // Step 3: Lakukan geocoding untuk alamat lengkap
            setTimeout(() => {
                performEnhancedGeocoding(alamat, detectedKelurahan);
            }, 1500); // Beri waktu untuk zoom selesai
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

    function buildSmartAddress(alamat, kelurahan) {
        let fullAddress = alamat.trim();

        // Parse alamat untuk melihat komponen yang sudah ada
        const addressParts = fullAddress.split(',').map(part => part.trim());
        const addressLower = fullAddress.toLowerCase();

        console.log('Building smart address from:', fullAddress);
        console.log('Detected kelurahan:', kelurahan);

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

            // Check existing components
            addressParts.forEach(part => {
                const partLower = part.toLowerCase();

                if (partLower.includes(kelurahanName.toLowerCase())) {
                    hasKelurahan = true;
                }
                if (partLower.includes('kec.') || partLower.includes('kecamatan') ||
                    partLower.includes(kecamatanName.toLowerCase())) {
                    hasKecamatan = true;
                }
                if (partLower.includes('kota') || partLower.includes('kabupaten') ||
                    partLower.includes(kotaName.toLowerCase().replace('kota ', '').replace('kabupaten ', ''))) {
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
        const statusDiv = $('#addressSearchStatus');

        // Support multiple status types with appropriate styling
        const typeClasses = {
            'searching': 'alert-info',
            'success': 'alert-success',
            'warning': 'alert-warning',
            'info': 'alert-primary',
            'error': 'alert-danger'
        };

        // Show appropriate icon for each status
        const typeIcons = {
            'searching': 'fas fa-spinner fa-spin',
            'success': 'fas fa-check-circle',
            'warning': 'fas fa-exclamation-triangle',
            'info': 'fas fa-info-circle',
            'error': 'fas fa-times-circle'
        };

        // Display message with formatting
        statusDiv.removeClass('alert-info alert-success alert-warning alert-primary alert-danger')
            .addClass(`alert ${typeClasses[type]} alert-dismissible`)
            .html(`
                   <i class="${typeIcons[type]}"></i> ${message}
                   <button type="button" class="close" data-dismiss="alert">
                       <span>&times;</span>
                   </button>
               `).show();

        // Auto-hide for success messages (after 10s)
        // Keep visible for warnings and errors
        if (type === 'success') {
            setTimeout(() => {
                statusDiv.fadeOut();
            }, 10000);
        } else if (type === 'info') {
            // Info messages also auto-hide but after longer duration
            setTimeout(() => {
                statusDiv.fadeOut();
            }, 12000);
        }
        // Note: 'searching', 'warning', and 'error' types stay visible until dismissed
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
     * Show final marker (red) with popup
     * Requirements: 1.5, 2.1, 2.2
     * 
     * @param {number} lat - Latitude
     * @param {number} lng - Longitude
     * @param {string} popupText - Text to display in popup
     */
    function showFinalMarker(lat, lng, popupText) {
        if (!interactiveMap) {
            console.warn('⚠️ Cannot show final marker: map not initialized');
            return;
        }

        // Clear existing markers
        clearAllMarkers();

        console.log(`📍 Showing final marker at [${lat}, ${lng}]`);

        // Create red final marker with enhanced styling
        const finalIcon = L.divIcon({
            className: 'final-marker',
            html: '<div style="background-color: #dc3545; width: 24px; height: 24px; border-radius: 50%; border: 3px solid white; box-shadow: 0 4px 10px rgba(0,0,0,0.5);"></div>',
            iconSize: [30, 30],
            iconAnchor: [15, 15]
        });

        currentMarker = L.marker([lat, lng], {
            icon: finalIcon,
            draggable: true
        }).addTo(interactiveMap);

        // Bind popup with provided text
        const popupContent = `
            <div style="text-align: center;">
                <strong><i class="fas fa-store text-danger"></i> Lokasi Final Toko</strong><br>
                <small style="color: #666; margin-top: 4px; display: block;">${popupText}</small><br>
                <code style="background: #f8f9fa; padding: 2px 4px; border-radius: 3px;">${lat.toFixed(6)}, ${lng.toFixed(6)}</code><br>
                <small class="text-muted">Geser marker untuk penyesuaian halus</small>
            </div>
        `;

        currentMarker.bindPopup(popupContent).openPopup();

        // Add drag end listener for marker adjustment
        currentMarker.on('dragend', function (e) {
            const newLat = e.target.getLatLng().lat;
            const newLng = e.target.getLatLng().lng;

            console.log(`🖱️ Final marker dragged to [${newLat}, ${newLng}]`);

            if (isWithinMalangRegion(newLat, newLng)) {
                updateCoordinateFields(newLat, newLng);
                showLocationStatus(newLat, newLng, true);

                // Update popup with new coordinates
                currentMarker.setPopupContent(`
                    <div style="text-align: center;">
                        <strong><i class="fas fa-store text-danger"></i> Lokasi Final Toko</strong><br>
                        <small style="color: #666;">Lokasi disesuaikan</small><br>
                        <code style="background: #f8f9fa; padding: 2px 4px; border-radius: 3px;">${newLat.toFixed(6)}, ${newLng.toFixed(6)}</code><br>
                        <small class="text-muted">Geser marker untuk penyesuaian halus</small>
                    </div>
                `);
            } else {
                showAlert('warning', 'Lokasi tidak valid! Marker dikembalikan ke posisi sebelumnya.');
                currentMarker.setLatLng([lat, lng]);
            }
        });

        console.log('✅ Final marker displayed');
    }

    /**
     * Clear all markers from map (both preview and final)
     * Requirements: 1.5, 2.1, 2.2
     */
    function clearAllMarkers() {
        console.log('🧹 Clearing all markers from map');

        if (currentMarker && interactiveMap) {
            interactiveMap.removeLayer(currentMarker);
            currentMarker = null;
            console.log('  ✓ Final marker removed');
        }

        if (previewMarker && interactiveMap) {
            interactiveMap.removeLayer(previewMarker);
            previewMarker = null;
            console.log('  ✓ Preview marker removed');
        }
    }

    /**
     * Update detected kelurahan info display
     * Requirements: 3.4
     * 
     * @param {object} kelurahan - Kelurahan object with name, kecamatan, kota, score
     */
    function updateDetectedKelurahanInfo(kelurahan) {
        if (!kelurahan) {
            $('#detectedKelurahanInfo').hide();
            return;
        }

        console.log('📋 Updating detected kelurahan info display');

        // Parse alamat untuk mengekstrak informasi wilayah menggunakan parseIndonesianAddress()
        const alamat = $('#alamat').val();
        const parsedAddress = parseIndonesianAddress(alamat);

        console.log('Parsed address:', parsedAddress);

        // Use parsed address components with fallback to database
        const kota = parsedAddress.kota || kelurahan.data.kota;
        const kecamatan = parsedAddress.kecamatan || kelurahan.data.kecamatan;
        const kelurahanName = parsedAddress.kelurahan || kelurahan.name;

        console.log('Using - Kota:', kota, 'Kecamatan:', kecamatan, 'Kelurahan:', kelurahanName);

        // Determine confidence badge class
        let badgeClass = 'badge-success';
        let confidenceText = 'Tinggi';

        if (kelurahan.score >= 75) {
            badgeClass = 'badge-success';
            confidenceText = 'Tinggi';
        } else if (kelurahan.score >= 65) {
            badgeClass = 'badge-warning';
            confidenceText = 'Sedang';
        } else {
            badgeClass = 'badge-danger';
            confidenceText = 'Rendah';
        }

        const infoHtml = `
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <h6 class="alert-heading">
                    <i class="fas fa-map-marked-alt"></i> Kelurahan Terdeteksi
                </h6>
                <hr>
                <p class="mb-2">
                    <strong>Kelurahan:</strong> 
                    <span class="badge badge-primary">${kelurahan.name}</span>
                </p>
                <p class="mb-2">
                    <strong>Kecamatan:</strong> ${kelurahan.data.kecamatan}<br>
                    <strong>Kota:</strong> ${kelurahan.data.kota}
                </p>
                <p class="mb-0">
                    <strong>Confidence:</strong> 
                    <span class="badge ${badgeClass}">${confidenceText} (${kelurahan.score}%)</span>
                </p>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        `;

        $('#detectedKelurahanInfo').html(infoHtml).show();

        // Auto-set dropdown wilayah dengan improved matching
        setWilayahFromDetection(kota, kecamatan, kelurahanName);

        console.log('✅ Detected kelurahan info updated');
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
        const typeClasses = {
            'success': 'alert-success',
            'warning': 'alert-warning',
            'info': 'alert-info'
        };

        const typeIcons = {
            'success': 'fas fa-check-circle',
            'warning': 'fas fa-exclamation-triangle',
            'info': 'fas fa-info-circle'
        };

        const notificationHtml = `
            <div class="alert ${typeClasses[type]} alert-dismissible fade show" role="alert">
                <i class="${typeIcons[type]}"></i> ${message}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        `;

        // Show notification below detected kelurahan info
        $('#detectedKelurahanInfo').after(notificationHtml);

        // Auto-hide after 5 seconds for success notifications
        if (type === 'success') {
            setTimeout(function () {
                $('#detectedKelurahanInfo').next('.alert').fadeOut(function () {
                    $(this).remove();
                });
            }, 5000);
        }

        console.log(`📢 Wilayah notification shown: ${type} - ${message}`);
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
        const alert = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        `;

        $('#alert-container').html(alert);

        setTimeout(() => {
            $('.alert').alert('close');
        }, 15000);
    }
});