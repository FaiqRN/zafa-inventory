<?php

namespace App\Services\PartnerPerformance\CF;

use Illuminate\Support\Facades\Cache;

class SimilarityCollaborativeFilteringService
{
	private const DEFAULT_WEIGHTS = [
		'location' => 0.4,
		'district' => 0.2,
		'pattern' => 0.4,
	];

	private const DEFAULT_CACHE_TTL_SECONDS = 1800;

	public static function buildUserSimilarityMatrix(array $partnerProfiles, array $timeSeriesVectors, array $options = []): array
	{
		$weights = self::normalizeWeights(
			is_array($options['similarity_weights'] ?? null) ? $options['similarity_weights'] : []
		);

		if (empty($partnerProfiles)) {
			return [
				'matrix' => [],
				'weights' => $weights,
				'pairs' => [],
			];
		}

		$useCache = (bool) ($options['use_cache'] ?? true);
		$cacheTtl = max(1, (int) ($options['cache_ttl_seconds'] ?? self::DEFAULT_CACHE_TTL_SECONDS));

		if (!$useCache) {
			return self::calculateSimilarityMatrix($partnerProfiles, $timeSeriesVectors, $weights);
		}

		$cacheKey = self::buildCacheKey($partnerProfiles, $timeSeriesVectors, $weights, $options);

		$result = Cache::remember($cacheKey, $cacheTtl, function () use ($partnerProfiles, $timeSeriesVectors, $weights) {
			return self::calculateSimilarityMatrix($partnerProfiles, $timeSeriesVectors, $weights);
		});

		if (!is_array($result['pairs'] ?? null)) {
			$result['pairs'] = [];
		}

		$result['cache_key'] = $cacheKey;

		return $result;
	}

	public static function defaultWeights(): array
	{
		return self::DEFAULT_WEIGHTS;
	}

	public static function calculateUserSimilarity(
		array $profileA,
		array $profileB,
		array $vectorA = [],
		array $vectorB = [],
		array $weights = []
	): array {
		$resolvedWeights = self::normalizeWeights($weights);

		return self::calculateUserSimilarityWithNormalizedWeights(
			$profileA,
			$profileB,
			$vectorA,
			$vectorB,
			$resolvedWeights
		);
	}

	public static function calculateItemSimilarity(array $vectorA, array $vectorB): float
	{
		return self::cosineSimilarity($vectorA, $vectorB);
	}

	public static function cosineVectorSimilarity(array $a, array $b): float
	{
		return self::cosineSimilarity($a, $b);
	}

	private static function calculateSimilarityMatrix(array $partnerProfiles, array $timeSeriesVectors, array $weights): array
	{
		$matrix = [];
		$pairs = [];
		$partnerIds = array_keys($partnerProfiles);
		sort($partnerIds, SORT_NATURAL);

		foreach ($partnerIds as $partnerId) {
			$matrix[$partnerId][$partnerId] = 1.0;
		}

		$count = count($partnerIds);
		for ($i = 0; $i < $count; $i++) {
			$idA = $partnerIds[$i];

			for ($j = $i + 1; $j < $count; $j++) {
				$idB = $partnerIds[$j];

				$similarity = self::calculateUserSimilarityWithNormalizedWeights(
					$partnerProfiles[$idA] ?? [],
					$partnerProfiles[$idB] ?? [],
					$timeSeriesVectors[$idA] ?? [],
					$timeSeriesVectors[$idB] ?? [],
					$weights
				);

				$score = (float) ($similarity['score'] ?? 0.0);
				$components = is_array($similarity['components'] ?? null)
					? $similarity['components']
					: [];

				$matrix[$idA][$idB] = $score;
				$matrix[$idB][$idA] = $score;

				$pairs[] = [
					'toko_id_a' => $idA,
					'toko_id_b' => $idB,
					'sim_total' => round(self::clamp($score, 0, 1), 8),
					'sim_location' => round(self::clamp((float) ($components['location'] ?? 0), 0, 1), 8),
					'sim_district' => round(self::clamp((float) ($components['district'] ?? 0), 0, 1), 8),
					'sim_pattern' => round(self::clamp((float) ($components['pattern'] ?? 0), 0, 1), 8),
					'components' => $components,
					'weights' => $weights,
				];
			}
		}

		return [
			'matrix' => $matrix,
			'weights' => $weights,
			'pairs' => $pairs,
		];
	}

	private static function buildCacheKey(array $partnerProfiles, array $timeSeriesVectors, array $weights, array $options): string
	{
		$profiles = $partnerProfiles;
		$vectors = $timeSeriesVectors;

		ksort($profiles);
		ksort($vectors);

		$payload = [
			'version' => 1,
			'period_start' => (string) ($options['period_start'] ?? ''),
			'period_end' => (string) ($options['period_end'] ?? ''),
			'weights' => $weights,
			'profiles' => $profiles,
			'vectors' => $vectors,
		];

		return 'partner-performance:user-cf:similarity:' . md5(json_encode($payload));
	}

	private static function normalizeWeights(array $weights): array
	{
		$resolved = [];

		foreach (self::DEFAULT_WEIGHTS as $key => $defaultValue) {
			$value = array_key_exists($key, $weights) ? (float) $weights[$key] : $defaultValue;
			$resolved[$key] = max(0.0, $value);
		}

		$sum = array_sum($resolved);
		if ($sum <= 0) {
			$resolved = self::DEFAULT_WEIGHTS;
			$sum = array_sum($resolved);
		}

		$normalized = [];
		$keys = array_keys($resolved);
		$lastKey = end($keys);
		$runningTotal = 0.0;

		foreach ($resolved as $key => $value) {
			if ($key === $lastKey) {
				$normalizedValue = 1.0 - $runningTotal;
			} else {
				$normalizedValue = $value / $sum;
				$runningTotal += $normalizedValue;
			}

			$normalized[$key] = round(self::clamp($normalizedValue, 0, 1), 8);
		}

		return $normalized;
	}

	private static function calculateUserSimilarityWithNormalizedWeights(
		array $profileA,
		array $profileB,
		array $vectorA,
		array $vectorB,
		array $weights
	): array {
		$simLocation = self::locationSimilarity(
			$profileA['latitude'] ?? null,
			$profileA['longitude'] ?? null,
			$profileB['latitude'] ?? null,
			$profileB['longitude'] ?? null
		);

		$simDistrict = self::districtSimilarity(
			$profileA['district'] ?? null,
			$profileB['district'] ?? null
		);

		$simPattern = self::cosineSimilarity($vectorA, $vectorB);

		$combined = ($weights['location'] * $simLocation)
			+ ($weights['district'] * $simDistrict)
			+ ($weights['pattern'] * $simPattern);

		return [
			'score' => round(self::clamp($combined, 0, 1), 8),
			'components' => [
				'location' => round(self::clamp($simLocation, 0, 1), 8),
				'district' => round(self::clamp($simDistrict, 0, 1), 8),
				'pattern' => round(self::clamp($simPattern, 0, 1), 8),
			],
			'weights' => $weights,
		];
	}

	private static function locationSimilarity($latA, $lonA, $latB, $lonB): float
	{
		if (!is_numeric($latA) || !is_numeric($lonA) || !is_numeric($latB) || !is_numeric($lonB)) {
			return 0.0;
		}

		$distanceKm = self::haversineDistanceKm((float) $latA, (float) $lonA, (float) $latB, (float) $lonB);

		return self::clamp(1 / (1 + $distanceKm), 0, 1);
	}

	private static function districtSimilarity($districtA, $districtB): float
	{
		$a = trim(strtolower((string) ($districtA ?? '')));
		$b = trim(strtolower((string) ($districtB ?? '')));

		if ($a === '' || $b === '') {
			return 0.0;
		}

		return $a === $b ? 1.0 : 0.0;
	}

	private static function cosineSimilarity(array $a, array $b): float
	{
		$length = min(count($a), count($b));
		if ($length === 0) {
			return 0.0;
		}

		$dot = 0.0;
		$normA = 0.0;
		$normB = 0.0;

		for ($i = 0; $i < $length; $i++) {
			$x = (float) ($a[$i] ?? 0);
			$y = (float) ($b[$i] ?? 0);

			$dot += $x * $y;
			$normA += $x * $x;
			$normB += $y * $y;
		}

		if ($normA <= 0 || $normB <= 0) {
			return 0.0;
		}

		$value = $dot / (sqrt($normA) * sqrt($normB));

		return self::clamp($value, 0, 1);
	}

	private static function haversineDistanceKm(float $latA, float $lonA, float $latB, float $lonB): float
	{
		$earthRadiusKm = 6371;

		$dLat = deg2rad($latB - $latA);
		$dLon = deg2rad($lonB - $lonA);

		$sinLat = sin($dLat / 2);
		$sinLon = sin($dLon / 2);

		$a = ($sinLat * $sinLat)
			+ (cos(deg2rad($latA)) * cos(deg2rad($latB)) * $sinLon * $sinLon);

		$c = 2 * atan2(sqrt($a), sqrt(1 - $a));

		return $earthRadiusKm * $c;
	}

	private static function clamp(float $value, float $min, float $max): float
	{
		return max($min, min($max, $value));
	}
}