<?php

namespace App\Services\PartnerPerformance\CF;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ItemCollaborativeFilteringService
{
	private const DEFAULT_TOP_K_ITEMS = 10;
	private const DEFAULT_CACHE_TTL_SECONDS = 1800;
	private const EPSILON = 1e-12;

	public static function calculateScores(array $partnerIds, Carbon $startDate, Carbon $endDate, array $options = []): array
	{
		$partnerIds = array_values(array_unique(array_map('strval', $partnerIds)));
		if (empty($partnerIds)) {
			return [
				'matrix' => [],
				'item_similarity' => [],
				'partner_item_scores' => [],
				'scores' => [],
				'meta' => [
					'item_count' => 0,
					'top_k_items' => max(1, (int) ($options['top_k_items'] ?? self::DEFAULT_TOP_K_ITEMS)),
				],
			];
		}

		$matrixPayload = self::buildMatrix($partnerIds, $startDate, $endDate);
		$matrix = $matrixPayload['matrix'];
		$itemIds = $matrixPayload['item_ids'];

		$topKItems = max(1, (int) ($options['top_k_items'] ?? self::DEFAULT_TOP_K_ITEMS));

		if (empty($itemIds)) {
			$emptyScores = [];
			foreach ($partnerIds as $tokoId) {
				$emptyScores[$tokoId] = [
					'score' => 0.0,
					'score_item' => 0.0,
					'raw_score' => 0.0,
					'relation_score' => 0.0,
					'diversity_factor' => 0.0,
					'balance_factor' => 0.0,
					'avg_sales_per_active_product' => 0.0,
					'normalized_avg_sales' => 0.0,
					'active_products' => 0,
				];
			}

			return [
				'matrix' => $matrix,
				'item_similarity' => [],
				'partner_item_scores' => [],
				'scores' => $emptyScores,
				'meta' => [
					'item_count' => 0,
					'top_k_items' => $topKItems,
				],
			];
		}

		$similarityPayload = self::calculateItemSimilarity(
			$matrix,
			$itemIds,
			[
				'use_cache' => (bool) ($options['use_cache'] ?? true),
				'cache_ttl_seconds' => (int) ($options['cache_ttl_seconds'] ?? self::DEFAULT_CACHE_TTL_SECONDS),
				'period_start' => $startDate->toDateString(),
				'period_end' => $endDate->toDateString(),
			]
		);

		$itemSimilarity = $similarityPayload['item_similarity'] ?? [];
		$partnerItemScores = self::calculateItemScore($matrix, $itemIds, $itemSimilarity, $topKItems);
		$rawPartnerScores = self::buildRawPartnerScores($matrix, $partnerItemScores, $itemIds);
		$normalizedScores = self::normalizeScores($rawPartnerScores);

		$scores = [];
		foreach ($partnerIds as $tokoId) {
			$raw = $rawPartnerScores[$tokoId] ?? [
				'raw_score' => 0.0,
				'relation_score' => 0.0,
				'diversity_factor' => 0.0,
				'balance_factor' => 0.0,
				'avg_sales_per_active_product' => 0.0,
				'normalized_avg_sales' => 0.0,
				'active_products' => 0,
			];

			$score = (float) ($normalizedScores[$tokoId] ?? 0.0);

			$scores[$tokoId] = [
				'score' => round(self::clamp($score, 0, 1), 8),
				'score_item' => round(self::clamp($score, 0, 1), 8),
				'raw_score' => round((float) ($raw['raw_score'] ?? 0), 8),
				'relation_score' => round((float) ($raw['relation_score'] ?? 0), 8),
				'diversity_factor' => round((float) ($raw['diversity_factor'] ?? 0), 8),
				'balance_factor' => round((float) ($raw['balance_factor'] ?? 0), 8),
				'avg_sales_per_active_product' => round((float) ($raw['avg_sales_per_active_product'] ?? 0), 8),
				'normalized_avg_sales' => round((float) ($raw['normalized_avg_sales'] ?? 0), 8),
				'active_products' => (int) ($raw['active_products'] ?? 0),
			];
		}

		return [
			'matrix' => $matrix,
			'item_similarity' => $itemSimilarity,
			'partner_item_scores' => $partnerItemScores,
			'scores' => $scores,
			'meta' => [
				'item_count' => count($itemIds),
				'top_k_items' => $topKItems,
				'similarity_cache_key' => $similarityPayload['cache_key'] ?? null,
			],
		];
	}

	public static function buildMatrix(array $partnerIds, Carbon $startDate, Carbon $endDate): array
	{
		$salesRows = DB::table('retur')
			->select('toko_id', 'barang_id', DB::raw('COALESCE(SUM(total_terjual), 0) as sold_qty'))
			->whereIn('toko_id', $partnerIds)
			->whereBetween('tanggal_retur', [$startDate->toDateString(), $endDate->toDateString()])
			->groupBy('toko_id', 'barang_id')
			->get();

		$shipmentItemIds = DB::table('pengiriman')
			->whereIn('toko_id', $partnerIds)
			->whereBetween('tanggal_pengiriman', [$startDate->toDateString(), $endDate->toDateString()])
			->pluck('barang_id')
			->map(function ($id) {
				return (string) $id;
			})
			->values()
			->all();

		$salesItemIds = $salesRows->pluck('barang_id')
			->map(function ($id) {
				return (string) $id;
			})
			->values()
			->all();

		$itemIds = array_values(array_unique(array_filter(array_merge($shipmentItemIds, $salesItemIds), function ($id) {
			return $id !== '';
		})));
		sort($itemIds, SORT_NATURAL);

		$matrix = [];
		foreach ($partnerIds as $tokoId) {
			$matrix[$tokoId] = [];
			foreach ($itemIds as $itemId) {
				$matrix[$tokoId][$itemId] = 0.0;
			}
		}

		foreach ($salesRows as $row) {
			$tokoId = (string) ($row->toko_id ?? '');
			$itemId = (string) ($row->barang_id ?? '');
			if ($tokoId === '' || $itemId === '') {
				continue;
			}

			if (!isset($matrix[$tokoId]) || !array_key_exists($itemId, $matrix[$tokoId])) {
				continue;
			}

			$matrix[$tokoId][$itemId] = (float) ($row->sold_qty ?? 0);
		}

		return [
			'matrix' => $matrix,
			'item_ids' => $itemIds,
		];
	}

	public static function calculateItemSimilarity(array $matrix, array $itemIds, array $options = []): array
	{
		if (empty($itemIds)) {
			return [
				'item_similarity' => [],
			];
		}

		$useCache = (bool) ($options['use_cache'] ?? true);
		$cacheTtl = max(1, (int) ($options['cache_ttl_seconds'] ?? self::DEFAULT_CACHE_TTL_SECONDS));

		if (!$useCache) {
			return self::buildItemSimilarity($matrix, $itemIds);
		}

		$cacheKey = self::buildSimilarityCacheKey($matrix, $itemIds, $options);

		$result = Cache::remember($cacheKey, $cacheTtl, function () use ($matrix, $itemIds) {
			return self::buildItemSimilarity($matrix, $itemIds);
		});

		$result['cache_key'] = $cacheKey;

		return $result;
	}

	public static function calculateItemScore(array $matrix, array $itemIds, array $itemSimilarity, int $topKItems): array
	{
		$rawPredictions = [];
		$maxRawPrediction = 0.0;

		foreach ($matrix as $tokoId => $row) {
			foreach ($itemIds as $targetItemId) {
				$neighbors = [];

				foreach ($itemIds as $otherItemId) {
					if ($targetItemId === $otherItemId) {
						continue;
					}

					$sim = (float) ($itemSimilarity[$targetItemId][$otherItemId] ?? 0);
					if ($sim <= 0) {
						continue;
					}

					$neighbors[] = [
						'barang_id' => $otherItemId,
						'similarity' => $sim,
					];
				}

				usort($neighbors, function (array $a, array $b) {
					return $b['similarity'] <=> $a['similarity'];
				});

				$neighbors = array_slice($neighbors, 0, max(1, $topKItems));

				$numerator = 0.0;
				$denominator = 0.0;

				foreach ($neighbors as $neighbor) {
					$sim = (float) ($neighbor['similarity'] ?? 0);
					$neighborItemId = (string) ($neighbor['barang_id'] ?? '');
					$value = (float) ($row[$neighborItemId] ?? 0);

					$numerator += $sim * $value;
					$denominator += abs($sim);
				}

				$predicted = $denominator > 0 ? $numerator / $denominator : 0.0;
				$predicted = max(0.0, $predicted);

				$rawPredictions[$tokoId][$targetItemId] = $predicted;
				$maxRawPrediction = max($maxRawPrediction, $predicted);
			}
		}

		$partnerItemScores = [];
		foreach ($rawPredictions as $tokoId => $row) {
			foreach ($row as $itemId => $rawValue) {
				$normalized = $maxRawPrediction > self::EPSILON ? $rawValue / $maxRawPrediction : 0.0;
				$partnerItemScores[$tokoId][$itemId] = round(self::clamp($normalized, 0, 1), 8);
			}
		}

		return $partnerItemScores;
	}

	private static function buildRawPartnerScores(array $matrix, array $partnerItemScores, array $itemIds): array
	{
		$rawRows = [];
		$avgSalesValues = [];

		foreach ($matrix as $tokoId => $row) {
			$activeValues = array_values(array_filter($row, function ($value) {
				return (float) $value > 0;
			}));

			$activeProducts = count($activeValues);
			$totalProducts = max(1, count($itemIds));

			$avgSales = $activeProducts > 0 ? array_sum($activeValues) / $activeProducts : 0.0;
			$diversity = $activeProducts / $totalProducts;

			$balance = 0.0;
			if ($activeProducts === 1) {
				$balance = 1.0;
			} elseif ($activeProducts > 1) {
				$mean = $avgSales;
				$variance = 0.0;

				foreach ($activeValues as $value) {
					$variance += ($value - $mean) * ($value - $mean);
				}

				$variance = $variance / $activeProducts;
				$stdDev = sqrt($variance);
				$cv = $mean > self::EPSILON ? $stdDev / $mean : 0.0;
				$balance = 1 / (1 + $cv);
			}

			$relationValues = [];
			foreach ($itemIds as $itemId) {
				if (((float) ($row[$itemId] ?? 0)) <= 0) {
					continue;
				}

				$relationValues[] = (float) ($partnerItemScores[$tokoId][$itemId] ?? 0);
			}

			$relationScore = count($relationValues) > 0
				? array_sum($relationValues) / count($relationValues)
				: 0.0;

			$rawRows[$tokoId] = [
				'avg_sales_per_active_product' => $avgSales,
				'diversity_factor' => self::clamp($diversity, 0, 1),
				'balance_factor' => self::clamp($balance, 0, 1),
				'relation_score' => self::clamp($relationScore, 0, 1),
				'active_products' => $activeProducts,
				'normalized_avg_sales' => 0.0,
				'raw_score' => 0.0,
			];

			$avgSalesValues[$tokoId] = $avgSales;
		}

		$normalizedAvgSales = self::normalizeMap($avgSalesValues);

		foreach ($rawRows as $tokoId => $row) {
			$avgSalesNorm = (float) ($normalizedAvgSales[$tokoId] ?? 0.0);

			$rawScore = (
				(0.4 * $avgSalesNorm)
				+ (0.2 * (float) ($row['diversity_factor'] ?? 0))
				+ (0.2 * (float) ($row['balance_factor'] ?? 0))
				+ (0.2 * (float) ($row['relation_score'] ?? 0))
			);

			$rawRows[$tokoId]['normalized_avg_sales'] = self::clamp($avgSalesNorm, 0, 1);
			$rawRows[$tokoId]['raw_score'] = self::clamp($rawScore, 0, 1);
		}

		return $rawRows;
	}

	private static function normalizeScores(array $rawPartnerScores): array
	{
		$source = [];

		foreach ($rawPartnerScores as $tokoId => $row) {
			$source[$tokoId] = (float) ($row['raw_score'] ?? 0);
		}

		return self::normalizeMap($source);
	}

	private static function normalizeMap(array $values): array
	{
		if (empty($values)) {
			return [];
		}

		$min = min($values);
		$max = max($values);

		$normalized = [];
		foreach ($values as $key => $value) {
			if (abs($max - $min) < self::EPSILON) {
				$normalized[$key] = (float) $value > 0 ? 1.0 : 0.0;
				continue;
			}

			$normalized[$key] = self::clamp(((float) $value - $min) / ($max - $min), 0, 1);
		}

		return $normalized;
	}

	private static function buildItemSimilarity(array $matrix, array $itemIds): array
	{
		$partnerIds = array_keys($matrix);
		sort($partnerIds, SORT_NATURAL);

		$itemVectors = [];
		foreach ($itemIds as $itemId) {
			$vector = [];
			foreach ($partnerIds as $partnerId) {
				$vector[] = (float) ($matrix[$partnerId][$itemId] ?? 0);
			}
			$itemVectors[$itemId] = $vector;
		}

		$itemSimilarity = [];
		foreach ($itemIds as $itemA) {
			foreach ($itemIds as $itemB) {
				if ($itemA === $itemB) {
					$itemSimilarity[$itemA][$itemB] = 1.0;
					continue;
				}

				$sim = SimilarityCollaborativeFilteringService::calculateItemSimilarity(
					$itemVectors[$itemA] ?? [],
					$itemVectors[$itemB] ?? []
				);

				$itemSimilarity[$itemA][$itemB] = round(self::clamp($sim, 0, 1), 8);
			}
		}

		return [
			'item_similarity' => $itemSimilarity,
		];
	}

	private static function buildSimilarityCacheKey(array $matrix, array $itemIds, array $options): string
	{
		$stableMatrix = $matrix;
		ksort($stableMatrix);
		foreach ($stableMatrix as &$row) {
			ksort($row);
		}
		unset($row);

		$payload = [
			'version' => 1,
			'period_start' => (string) ($options['period_start'] ?? ''),
			'period_end' => (string) ($options['period_end'] ?? ''),
			'item_ids' => $itemIds,
			'matrix' => $stableMatrix,
		];

		return 'partner-performance:item-cf:similarity:' . md5(json_encode($payload));
	}

	private static function clamp(float $value, float $min, float $max): float
	{
		return max($min, min($max, $value));
	}
}