<?php

namespace App\Services\PartnerPerformance;

use App\Models\PartnerCfScore;
use App\Models\PartnerCfSimilarity;
use App\Models\PartnerKpiScore;
use App\Models\PartnerPerformanceScore;
use App\Models\Toko;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KpiService
{
	private const KPI_KEYS = [
		'sales',
		'return_rate',
		'freq',
		'consistency',
		'efficiency',
	];

	private const COST_KPI_KEYS = [
		'return_rate',
	];

	private const DEFAULT_ALPHA = 0.5;
	private const DEFAULT_BETA = 0.5;
	private const DEFAULT_MONTH_WINDOW = 6;
	private const DEFAULT_NEIGHBOR_LIMIT = 5;
	private const CATEGORY_QUARTILE_A = 0.25;
	private const CATEGORY_QUARTILE_B = 0.50;
	private const CATEGORY_QUARTILE_C = 0.75;

	public static function calculate(array $options = []): array
	{
		try {
			[$startDate, $endDate] = self::resolvePeriod(
				$options['start_date'] ?? null,
				$options['end_date'] ?? null,
				(int) ($options['months'] ?? self::DEFAULT_MONTH_WINDOW)
			);

			$alpha = self::normalizeWeight($options['alpha'] ?? self::DEFAULT_ALPHA);
			$beta = self::normalizeWeight($options['beta'] ?? self::DEFAULT_BETA);
			$neighborLimit = max(1, (int) ($options['neighbor_limit'] ?? self::DEFAULT_NEIGHBOR_LIMIT));

			$partners = Toko::query()
				->where(Toko::FIELD_IS_ACTIVE, true)
				->select([
					Toko::FIELD_TOKO_ID,
					Toko::FIELD_NAMA_TOKO,
					Toko::FIELD_WILAYAH_KECAMATAN,
					Toko::FIELD_LATITUDE,
					Toko::FIELD_LONGITUDE,
				])
				->get();

			if ($partners->isEmpty()) {
				return self::emptyPayload($startDate, $endDate, $alpha, $beta);
			}

			$partnerIds = $partners->pluck(Toko::FIELD_TOKO_ID)->values()->all();
			$periodMeta = self::buildPeriodMeta($startDate, $endDate, $alpha, $beta);
			$periodMeta['total_active_partners'] = $partners->count();
			$periodMeta['store_similarity'] = (bool) ($options['store_similarity'] ?? true);
			$aggregate = self::aggregateMetrics($partnerIds, $startDate, $endDate);

			$rawRows = self::buildRawRows($partners, $aggregate, $periodMeta);
			$rawRows = self::filterOperationalRows($rawRows);

			if ($rawRows->isEmpty()) {
				$periodMeta['total_operational_partners'] = 0;
				$periodMeta['no_operational_data'] = true;

				return self::emptyPayload($startDate, $endDate, $alpha, $beta, $periodMeta);
			}

			$periodMeta['total_operational_partners'] = $rawRows->count();
			$periodMeta['no_operational_data'] = false;
			$normalization = self::normalizeRows($rawRows);

			$kpiVectors = self::buildVectorMap($normalization['normalized_rows'], self::KPI_KEYS);
			$qualityMap = self::buildQualityMap($kpiVectors);

			$cbfResult = ContentBasedFilteringService::calculateScores(
				$normalization['normalized_rows'],
				self::KPI_KEYS,
				is_array($options['cbf_weights'] ?? null) ? $options['cbf_weights'] : []
			);
			$cbfScores = $cbfResult['scores'] ?? [];
			$periodMeta['cbf_weights'] = $cbfResult['weights'] ?? ContentBasedFilteringService::defaultWeights(self::KPI_KEYS);
			$periodMeta['cbf_weight_sum'] = (float) ($cbfResult['weight_sum'] ?? 1.0);

			[$cbfMatrix, $kpiSimilarityScores] = self::scoreBySimilarity($kpiVectors, $qualityMap, $neighborLimit);

			$timeSeriesVectors = self::buildUserInteractionVectors($partnerIds, $startDate, $endDate);
			$userCfResult = CollaborativeFilteringService::calculateUserBased(
				$partners,
				$timeSeriesVectors,
				self::buildKpiScoreMap($cbfScores),
				[
					'top_n' => $neighborLimit,
					'similarity_weights' => is_array($options['user_similarity_weights'] ?? null)
						? $options['user_similarity_weights']
						: [],
					'cache_ttl_seconds' => (int) ($options['similarity_cache_ttl'] ?? 1800),
					'period_start' => $startDate->toDateString(),
					'period_end' => $endDate->toDateString(),
				]
			);
			$userMatrix = $userCfResult['matrix'] ?? [];
			$userScores = $userCfResult['scores'] ?? [];
			$userSimilarityPairs = $userCfResult['pairs'] ?? [];
			$periodMeta['user_similarity_weights'] = $userCfResult['weights'] ?? [];
			$periodMeta['user_similarity_cache_key'] = $userCfResult['cache_key'] ?? null;

			$itemCfResult = CollaborativeFilteringService::calculateItemBased(
				$partnerIds,
				$startDate,
				$endDate,
				[
					'top_k_items' => max(3, $neighborLimit),
					'use_cache' => true,
					'cache_ttl_seconds' => (int) ($options['similarity_cache_ttl'] ?? 1800),
					'period_start' => $startDate->toDateString(),
					'period_end' => $endDate->toDateString(),
				]
			);
			$itemMatrix = $itemCfResult['item_similarity'] ?? [];
			$itemScores = $itemCfResult['scores'] ?? [];
			$periodMeta['item_cf_meta'] = $itemCfResult['meta'] ?? [];

			$topProducts = self::buildTopProductMap($partnerIds, $startDate, $endDate);

			$finalPartners = self::composeFinalPartners(
				$normalization['normalized_rows'],
				$qualityMap,
				$cbfScores,
				$kpiSimilarityScores,
				$userScores,
				$itemScores,
				$kpiVectors,
				$timeSeriesVectors,
				$alpha,
				$beta,
				$periodMeta['user_similarity_weights'] ?? [],
				$periodMeta['user_similarity_cache_key'] ?? null,
				$periodMeta['item_cf_meta'] ?? []
			);

			$rankedPartners = collect(HybridRecommendationService::rankByHybridScore($finalPartners));
			$rankedPartners = self::assignRelativeCategories($rankedPartners);

			$rankedPartners = self::decorateTopNeighbors($rankedPartners, $topProducts);

			$payload = [
				'meta' => $periodMeta,
				'kpi_order' => self::KPI_KEYS,
				'normalization' => $normalization['minmax'],
				'similarity_matrices' => [
					'cbf' => $cbfMatrix,
					'cf_user' => $userMatrix,
					'cf_item' => $itemMatrix,
				],
				'similarity_pairs' => $userSimilarityPairs,
				'partners' => $rankedPartners->values()->all(),
			];

			$payload['frontend_rows'] = self::buildFrontendRows($rankedPartners);

			if (!empty($options['store'])) {
				$payload['stored_count'] = self::storePartnerScores($payload);
			}

			return $payload;
		} catch (\Throwable $e) {
			Log::error('KpiService calculate error: ' . $e->getMessage());

			return [
				'meta' => [
					'period_start' => null,
					'period_end' => null,
					'total_months' => 0,
					'total_days' => 0,
					'alpha' => self::DEFAULT_ALPHA,
					'beta' => self::DEFAULT_BETA,
					'total_active_partners' => 0,
					'total_operational_partners' => 0,
					'no_operational_data' => true,
					'cbf_weights' => ContentBasedFilteringService::defaultWeights(self::KPI_KEYS),
					'cbf_weight_sum' => 1.0,
					'user_similarity_weights' => [],
					'user_similarity_cache_key' => null,
					'item_cf_meta' => [],
					'category_method' => 'relative_rank_quartile',
					'store_similarity' => true,
					'generated_at' => now()->toDateTimeString(),
				],
				'kpi_order' => self::KPI_KEYS,
				'normalization' => [],
				'similarity_matrices' => [
					'cbf' => [],
					'cf_user' => [],
					'cf_item' => [],
				],
				'similarity_pairs' => [],
				'partners' => [],
				'frontend_rows' => [],
				'error' => 'Gagal menghitung KPI',
			];
		}
	}

	private static function resolvePeriod($startDate, $endDate, int $months): array
	{
		$end = $endDate
			? Carbon::parse($endDate)->endOfDay()
			: Carbon::now()->endOfDay();

		$start = $startDate
			? Carbon::parse($startDate)->startOfDay()
			: (clone $end)->subMonths(max(1, $months))->startOfDay();

		if ($start->greaterThan($end)) {
			[$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
		}

		return [$start, $end];
	}

	private static function buildPeriodMeta(Carbon $startDate, Carbon $endDate, float $alpha, float $beta): array
	{
		return [
			'period_start' => $startDate->toDateString(),
			'period_end' => $endDate->toDateString(),
			'total_months' => max(1, $startDate->diffInMonths($endDate) + 1),
			'total_days' => max(1, $startDate->diffInDays($endDate) + 1),
			'alpha' => $alpha,
			'beta' => $beta,
			'category_method' => 'relative_rank_quartile',
			'generated_at' => now()->toDateTimeString(),
		];
	}

	private static function aggregateMetrics(array $partnerIds, Carbon $startDate, Carbon $endDate): array
	{
		$shipments = DB::table('pengiriman')
			->select(
				'toko_id',
				DB::raw("COALESCE(SUM(CASE WHEN status = 'terkirim' THEN jumlah_kirim ELSE 0 END), 0) as shipped_qty"),
				DB::raw('COALESCE(SUM(jumlah_kirim), 0) as shipped_qty_total'),
				DB::raw("COUNT(DISTINCT CASE WHEN status = 'terkirim' THEN nomer_pengiriman END) as shipment_done"),
				DB::raw('COUNT(DISTINCT nomer_pengiriman) as shipment_total'),
				DB::raw("COUNT(DISTINCT CASE WHEN status = 'terkirim' THEN DATE_FORMAT(tanggal_pengiriman, '%Y-%m') END) as active_months")
			)
			->whereIn('toko_id', $partnerIds)
			->whereBetween('tanggal_pengiriman', [$startDate->toDateString(), $endDate->toDateString()])
			->groupBy('toko_id')
			->get()
			->keyBy('toko_id');

		$returns = DB::table('retur')
			->select(
				'toko_id',
				DB::raw('COALESCE(SUM(total_terjual), 0) as sold_qty'),
				DB::raw('COALESCE(SUM(jumlah_retur), 0) as returned_qty'),
				DB::raw('COALESCE(SUM(hasil), 0) as revenue'),
				DB::raw('COUNT(DISTINCT nomer_pengiriman) as transaction_count')
			)
			->whereIn('toko_id', $partnerIds)
			->whereBetween('tanggal_retur', [$startDate->toDateString(), $endDate->toDateString()])
			->groupBy('toko_id')
			->get()
			->keyBy('toko_id');

		$activitySub = DB::table('pengiriman')
			->select('toko_id', DB::raw('DATE(tanggal_pengiriman) as activity_date'))
			->whereIn('toko_id', $partnerIds)
			->whereBetween('tanggal_pengiriman', [$startDate->toDateString(), $endDate->toDateString()])
			->unionAll(
				DB::table('retur')
					->select('toko_id', DB::raw('DATE(tanggal_retur) as activity_date'))
					->whereIn('toko_id', $partnerIds)
					->whereBetween('tanggal_retur', [$startDate->toDateString(), $endDate->toDateString()])
			);

		$activeDays = DB::query()
			->fromSub($activitySub, 'activity_rows')
			->select('toko_id', DB::raw('COUNT(DISTINCT activity_date) as active_days'))
			->groupBy('toko_id')
			->get()
			->keyBy('toko_id');

		$avgReturnDays = DB::table('retur')
			->join('pengiriman', 'retur.pengiriman_id', '=', 'pengiriman.pengiriman_id')
			->select(
				'retur.toko_id',
				DB::raw('AVG(CASE WHEN DATEDIFF(retur.tanggal_retur, pengiriman.tanggal_pengiriman) BETWEEN 1 AND 90 THEN DATEDIFF(retur.tanggal_retur, pengiriman.tanggal_pengiriman) END) as avg_return_days')
			)
			->whereIn('retur.toko_id', $partnerIds)
			->whereBetween('retur.tanggal_retur', [$startDate->toDateString(), $endDate->toDateString()])
			->groupBy('retur.toko_id')
			->get()
			->keyBy('toko_id');

		return [
			'shipments' => $shipments,
			'returns' => $returns,
			'active_days' => $activeDays,
			'avg_return_days' => $avgReturnDays,
		];
	}

	private static function buildRawRows(Collection $partners, array $aggregate, array $periodMeta): Collection
	{
		$totalMonths = max(1, (int) ($periodMeta['total_months'] ?? 1));

		return $partners->map(function ($partner) use ($aggregate, $totalMonths) {
			$tokoId = $partner->{Toko::FIELD_TOKO_ID};
			$shipment = $aggregate['shipments']->get($tokoId);
			$return = $aggregate['returns']->get($tokoId);
			$activity = $aggregate['active_days']->get($tokoId);
			$avgReturn = $aggregate['avg_return_days']->get($tokoId);

			$soldQty = (float) ($return->sold_qty ?? 0);
			$returnedQty = (float) ($return->returned_qty ?? 0);
			$revenue = (float) ($return->revenue ?? 0);
			$transactionCount = (int) ($return->transaction_count ?? 0);

			$shipmentDone = (int) ($shipment->shipment_done ?? 0);
			$shipmentTotal = (int) ($shipment->shipment_total ?? 0);
			$activeMonths = (int) ($shipment->active_months ?? 0);
			$activeDays = (int) ($activity->active_days ?? 0);
			$shippedQty = (float) ($shipment->shipped_qty ?? 0);
			$shippedQtyTotal = (float) ($shipment->shipped_qty_total ?? 0);

			$hasOperationalData = ($shipmentDone > 0)
				|| ($shipmentTotal > 0)
				|| ($shippedQty > 0)
				|| ($shippedQtyTotal > 0)
				|| ($soldQty > 0)
				|| ($returnedQty > 0)
				|| ($transactionCount > 0);

			$raw = [
				'sales' => round($soldQty, 6),
				'return_rate' => round(self::safeDivide($returnedQty, $shippedQty), 8),
				'freq' => (float) $shipmentDone,
				'consistency' => round(self::safeDivide($activeMonths, $totalMonths), 8),
				'efficiency' => round(self::safeDivide($soldQty, $shippedQty), 8),
			];

			$sellThroughRate = self::safeDivide($soldQty, $shippedQty) * 100;

			return [
				'toko_id' => $tokoId,
				'nama_toko' => $partner->{Toko::FIELD_NAMA_TOKO},
				'wilayah' => $partner->{Toko::FIELD_WILAYAH_KECAMATAN} ?? '-',
				'has_operational_data' => $hasOperationalData,
				'raw_kpi' => $raw,
				'support_metrics' => [
					'sold_qty' => $soldQty,
					'returned_qty' => $returnedQty,
					'revenue' => $revenue,
					'transaction_count' => $transactionCount,
					'shipment_done' => $shipmentDone,
					'shipment_total' => $shipmentTotal,
					'active_months' => $activeMonths,
					'active_days' => $activeDays,
					'shipped_qty' => $shippedQty,
					'shipped_qty_total' => $shippedQtyTotal,
					'avg_return_days' => round((float) ($avgReturn->avg_return_days ?? 14), 2),
					'sell_through_rate' => round($sellThroughRate, 2),
				],
			];
		});
	}

	private static function filterOperationalRows(Collection $rows): Collection
	{
		return $rows->filter(function (array $row) {
			return (bool) ($row['has_operational_data'] ?? false);
		})->values();
	}

	private static function normalizeRows(Collection $rows): array
	{
		$minmax = [];

		foreach (self::KPI_KEYS as $kpiKey) {
			$values = $rows->pluck('raw_kpi.' . $kpiKey)->map(function ($value) {
				return (float) $value;
			});

			$min = (float) ($values->min() ?? 0);
			$max = (float) ($values->max() ?? 0);

			$minmax[$kpiKey] = [
				'min' => $min,
				'max' => $max,
				'type' => in_array($kpiKey, self::COST_KPI_KEYS, true) ? 'cost' : 'benefit',
			];
		}

		$normalizedRows = $rows->map(function (array $row) use ($minmax) {
			$normalized = [];

			foreach (self::KPI_KEYS as $kpiKey) {
				$value = (float) ($row['raw_kpi'][$kpiKey] ?? 0);
				$min = (float) $minmax[$kpiKey]['min'];
				$max = (float) $minmax[$kpiKey]['max'];

				if (abs($max - $min) < 1e-12) {
					$score = 1.0;
				} else {
					if (in_array($kpiKey, self::COST_KPI_KEYS, true)) {
						$score = ($max - $value) / ($max - $min);
					} else {
						$score = ($value - $min) / ($max - $min);
					}
				}

				$normalized[$kpiKey] = round(self::clamp($score, 0, 1), 8);
			}

			$row['normalized_kpi'] = $normalized;

			return $row;
		});

		return [
			'minmax' => $minmax,
			'normalized_rows' => $normalizedRows,
		];
	}

	private static function buildVectorMap(Collection $rows, array $orderedKeys): array
	{
		$vectors = [];

		foreach ($rows as $row) {
			$tokoId = $row['toko_id'];
			$values = [];

			foreach ($orderedKeys as $key) {
				$values[] = (float) ($row['normalized_kpi'][$key] ?? 0);
			}

			$vectors[$tokoId] = $values;
		}

		return $vectors;
	}

	private static function buildQualityMap(array $vectorMap): array
	{
		$qualityMap = [];

		foreach ($vectorMap as $tokoId => $vector) {
			if (count($vector) === 0) {
				$qualityMap[$tokoId] = 0.0;
				continue;
			}

			$qualityMap[$tokoId] = round(array_sum($vector) / count($vector), 8);
		}

		return $qualityMap;
	}

	private static function buildKpiScoreMap(array $cbfScores): array
	{
		$map = [];

		foreach ($cbfScores as $tokoId => $row) {
			$map[$tokoId] = (float) ($row['score'] ?? 0);
		}

		return $map;
	}

	private static function buildUserInteractionVectors(array $partnerIds, Carbon $startDate, Carbon $endDate): array
	{
		$months = [];
		$cursor = $startDate->copy()->startOfMonth();
		$endCursor = $endDate->copy()->startOfMonth();

		while ($cursor->lessThanOrEqualTo($endCursor)) {
			$months[] = $cursor->format('Y-m');
			$cursor->addMonth();
		}

		if (empty($months)) {
			$months[] = $startDate->format('Y-m');
		}

		$monthlyRows = DB::table('retur')
			->select(
				'toko_id',
				DB::raw("DATE_FORMAT(tanggal_retur, '%Y-%m') as ym"),
				DB::raw('COALESCE(SUM(total_terjual), 0) as sold_qty')
			)
			->whereIn('toko_id', $partnerIds)
			->whereBetween('tanggal_retur', [$startDate->toDateString(), $endDate->toDateString()])
			->groupBy('toko_id', 'ym')
			->get();

		$map = [];
		foreach ($monthlyRows as $row) {
			$map[$row->toko_id][$row->ym] = (float) $row->sold_qty;
		}

		$vectors = [];
		foreach ($partnerIds as $tokoId) {
			$vector = [];

			foreach ($months as $month) {
				$vector[] = (float) ($map[$tokoId][$month] ?? 0);
			}

			$vectors[$tokoId] = $vector;
		}

		return $vectors;
	}

	private static function buildItemPreferenceVectors(array $partnerIds, Carbon $startDate, Carbon $endDate): array
	{
		$itemRows = DB::table('retur')
			->select('toko_id', 'barang_id', DB::raw('COALESCE(SUM(total_terjual), 0) as sold_qty'))
			->whereIn('toko_id', $partnerIds)
			->whereBetween('tanggal_retur', [$startDate->toDateString(), $endDate->toDateString()])
			->groupBy('toko_id', 'barang_id')
			->get();

		$itemIds = $itemRows->pluck('barang_id')->unique()->values()->all();
		if (empty($itemIds)) {
			$itemIds = ['__no_item__'];
		}

		$itemMap = [];
		foreach ($itemRows as $row) {
			$itemMap[$row->toko_id][$row->barang_id] = (float) $row->sold_qty;
		}

		$vectors = [];
		foreach ($partnerIds as $tokoId) {
			$vector = [];

			foreach ($itemIds as $itemId) {
				$vector[] = (float) ($itemMap[$tokoId][$itemId] ?? 0);
			}

			$vectors[$tokoId] = $vector;
		}

		return $vectors;
	}

	private static function scoreBySimilarity(array $vectorMap, array $qualityMap, int $neighborLimit): array
	{
		$matrix = [];
		$scores = [];

		$ids = array_keys($vectorMap);
		foreach ($ids as $idA) {
			foreach ($ids as $idB) {
				if ($idA === $idB) {
					$matrix[$idA][$idB] = 1.0;
					continue;
				}

				$matrix[$idA][$idB] = round(
					self::cosineSimilarity($vectorMap[$idA] ?? [], $vectorMap[$idB] ?? []),
					8
				);
			}
		}

		foreach ($ids as $targetId) {
			$sumWeighted = 0.0;
			$sumWeight = 0.0;
			$neighbors = [];

			foreach ($ids as $otherId) {
				if ($targetId === $otherId) {
					continue;
				}

				$sim = (float) ($matrix[$targetId][$otherId] ?? 0);
				$sumWeighted += $sim * (float) ($qualityMap[$otherId] ?? 0);
				$sumWeight += $sim;

				$neighbors[] = [
					'toko_id' => $otherId,
					'similarity' => round($sim, 8),
				];
			}

			usort($neighbors, function (array $a, array $b) {
				return $b['similarity'] <=> $a['similarity'];
			});

			$score = $sumWeight > 0 ? $sumWeighted / $sumWeight : 0.0;
			$avgSimilarity = count($neighbors) > 0
				? array_sum(array_column($neighbors, 'similarity')) / count($neighbors)
				: 0.0;

			$scores[$targetId] = [
				'score' => round(self::clamp($score, 0, 1), 8),
				'avg_similarity' => round(self::clamp($avgSimilarity, 0, 1), 8),
				'neighbors' => array_slice($neighbors, 0, $neighborLimit),
			];
		}

		return [$matrix, $scores];
	}

	private static function composeFinalPartners(
		Collection $rows,
		array $qualityMap,
		array $cbfScores,
		array $kpiSimilarityScores,
		array $userScores,
		array $itemScores,
		array $kpiVectors,
		array $timeSeriesVectors,
		float $alpha,
		float $beta,
		array $userSimilarityWeights,
		?string $userSimilarityCacheKey,
		array $itemCfMeta
	): array {
		return $rows->map(function (array $row) use ($qualityMap, $cbfScores, $kpiSimilarityScores, $userScores, $itemScores, $kpiVectors, $timeSeriesVectors, $alpha, $beta, $userSimilarityWeights, $userSimilarityCacheKey, $itemCfMeta) {
			$tokoId = $row['toko_id'];

			$cbfScore = (float) ($cbfScores[$tokoId]['score'] ?? 0);
			$cfUserScore = (float) ($userScores[$tokoId]['score'] ?? 0);
			$cfItemScore = (float) ($itemScores[$tokoId]['score'] ?? 0);
			$scoreBreakdown = HybridRecommendationService::buildScoreBreakdown(
				$cbfScore,
				$cfUserScore,
				$cfItemScore,
				$alpha,
				$beta
			);
			$cfScore = (float) ($scoreBreakdown['cf_score'] ?? 0);
			$hybridScore = (float) ($scoreBreakdown['hybrid_score'] ?? 0);
			$contributions = $scoreBreakdown['contributions'] ?? [];
			$userNeighbors = $userScores[$tokoId]['neighbors'] ?? [];
			$kpiFallbackNeighbors = $kpiSimilarityScores[$tokoId]['neighbors'] ?? [];
			$hasUserNeighbors = !empty($userNeighbors);
			$neighborSource = $hasUserNeighbors ? 'cf_user' : 'kpi_fallback';

			// KPI similarity is fallback metadata for neighbor insight, not a hybrid score input.
			$avgSimilarity = $hasUserNeighbors
				? (float) ($userScores[$tokoId]['avg_similarity'] ?? 0)
				: (float) ($kpiSimilarityScores[$tokoId]['avg_similarity'] ?? ($userScores[$tokoId]['avg_similarity'] ?? 0));

			$support = $row['support_metrics'] ?? [];

			return [
				'toko_id' => $tokoId,
				'nama_toko' => $row['nama_toko'],
				'wilayah' => $row['wilayah'],
				'raw_kpi' => $row['raw_kpi'],
				'normalized_kpi' => $row['normalized_kpi'],
				'kpi_vector' => $kpiVectors[$tokoId] ?? [],
				'time_series_vector' => array_map('floatval', $timeSeriesVectors[$tokoId] ?? []),
				'cbf_components' => $cbfScores[$tokoId]['components'] ?? [],
				'quality_score' => round((float) ($qualityMap[$tokoId] ?? 0), 8),
				'performance_score' => round(((float) ($qualityMap[$tokoId] ?? 0)) * 100, 2),
				'sell_through_rate' => round((float) ($support['sell_through_rate'] ?? 0), 2),
				'cbf_score' => (float) ($scoreBreakdown['cbf_score'] ?? round($cbfScore, 8)),
				'cf_user_score' => (float) ($scoreBreakdown['cf_user_score'] ?? round($cfUserScore, 8)),
				'cf_user_avg_similarity' => round((float) ($userScores[$tokoId]['avg_similarity'] ?? 0), 8),
				'cf_user_neighbor_count' => count($userScores[$tokoId]['neighbors'] ?? []),
				'cf_user_top_neighbors' => $userNeighbors,
				'kpi_fallback_neighbors' => $kpiFallbackNeighbors,
				'neighbor_source' => $neighborSource,
				'cf_item_score' => (float) ($scoreBreakdown['cf_item_score'] ?? round($cfItemScore, 8)),
				'cf_item_raw_score' => round((float) ($itemScores[$tokoId]['raw_score'] ?? 0), 8),
				'cf_item_relation_score' => round((float) ($itemScores[$tokoId]['relation_score'] ?? 0), 8),
				'cf_item_diversity_factor' => round((float) ($itemScores[$tokoId]['diversity_factor'] ?? 0), 8),
				'cf_item_balance_factor' => round((float) ($itemScores[$tokoId]['balance_factor'] ?? 0), 8),
				'cf_item_avg_sales_norm' => round((float) ($itemScores[$tokoId]['normalized_avg_sales'] ?? 0), 8),
				'cf_item_active_products' => (int) ($itemScores[$tokoId]['active_products'] ?? 0),
				'cf_item_total_products' => (int) ($itemCfMeta['item_count'] ?? 0),
				'cf_score' => (float) ($scoreBreakdown['cf_score'] ?? round($cfScore, 8)),
				'hybrid_score' => (float) ($scoreBreakdown['hybrid_score'] ?? round($hybridScore, 8)),
				'alpha' => round(self::clamp($alpha, 0, 1), 8),
				'beta' => round(self::clamp($beta, 0, 1), 8),
				'contributions' => [
					'cbf' => round((float) ($contributions['cbf'] ?? 0), 8),
					'cf' => round((float) ($contributions['cf_total'] ?? 0), 8),
					'cf_user' => round((float) ($contributions['cf_user'] ?? 0), 8),
					'cf_item' => round((float) ($contributions['cf_item'] ?? 0), 8),
				],
				'similarity_weights' => $userSimilarityWeights,
				'similarity_cache_key' => $userSimilarityCacheKey,
				'item_cf_meta' => $itemCfMeta,
				'category' => 'D',
				'avg_similarity' => round(self::clamp($avgSimilarity, 0, 1), 8),
				'support_metrics' => $support,
			];
		})->values()->all();
	}

	private static function assignRelativeCategories(Collection $partners): Collection
	{
		$totalPartners = $partners->count();

		if ($totalPartners <= 0) {
			return $partners;
		}

		return $partners->map(function (array $partner) use ($totalPartners) {
			$rank = (int) ($partner['rank'] ?? 0);
			$partner['category'] = self::resolveCategoryByRank($rank, $totalPartners);

			return $partner;
		});
	}

	private static function decorateTopNeighbors(Collection $partners, array $topProducts): Collection
	{
		$partnerMap = $partners->keyBy('toko_id');

		return $partners->map(function (array $partner) use ($partnerMap, $topProducts) {
			$sourceId = $partner['toko_id'];
			$neighborSource = (string) ($partner['neighbor_source'] ?? 'cf_user');

			$partner['cf_user_top_neighbors'] = self::decorateNeighborList(
				is_array($partner['cf_user_top_neighbors'] ?? null) ? $partner['cf_user_top_neighbors'] : [],
				$partnerMap,
				$topProducts,
				$sourceId
			);
			$partner['kpi_fallback_neighbors'] = self::decorateNeighborList(
				is_array($partner['kpi_fallback_neighbors'] ?? null) ? $partner['kpi_fallback_neighbors'] : [],
				$partnerMap,
				$topProducts,
				$sourceId
			);
			$partner['neighbor_source'] = $neighborSource === 'kpi_fallback' ? 'kpi_fallback' : 'cf_user';
			$partner['display_neighbors'] = $partner['neighbor_source'] === 'kpi_fallback'
				? $partner['kpi_fallback_neighbors']
				: $partner['cf_user_top_neighbors'];

			return $partner;
		});
	}

	private static function decorateNeighborList(array $neighbors, Collection $partnerMap, array $topProducts, string $sourceId): array
	{
		return collect($neighbors)->map(function (array $neighbor) use ($partnerMap, $topProducts, $sourceId) {
				$neighborId = $neighbor['toko_id'] ?? null;
				$target = $neighborId ? $partnerMap->get($neighborId) : null;
				$similarity = (float) ($neighbor['similarity'] ?? 0);
				$scoreKpi = (float) ($neighbor['score_kpi'] ?? 0);

				return [
					'toko_id' => $neighborId,
					'nama_toko' => $target['nama_toko'] ?? (string) $neighborId,
					'similarity' => round($similarity, 8),
					'score_kpi' => round(self::clamp($scoreKpi, 0, 1), 8),
					'similarity_pct' => (int) round($similarity * 100),
					'category' => $target['category'] ?? 'D',
					'rank' => $target['rank'] ?? null,
					'reason' => self::buildSimilarityReason($similarity),
					'products' => self::buildProductInsights($sourceId, (string) $neighborId, $topProducts),
				];
		})->values()->all();
	}

	private static function buildTopProductMap(array $partnerIds, Carbon $startDate, Carbon $endDate): array
	{
		$rows = DB::table('retur')
			->leftJoin('barang', 'retur.barang_id', '=', 'barang.barang_id')
			->select(
				'retur.toko_id',
				'retur.barang_id',
				DB::raw('COALESCE(barang.nama_barang, retur.barang_id) as nama_barang'),
				DB::raw('COALESCE(SUM(retur.total_terjual), 0) as sold_qty')
			)
			->whereIn('retur.toko_id', $partnerIds)
			->whereBetween('retur.tanggal_retur', [$startDate->toDateString(), $endDate->toDateString()])
			->groupBy('retur.toko_id', 'retur.barang_id', 'barang.nama_barang')
			->get();

		$map = [];
		foreach ($rows as $row) {
			$map[$row->toko_id][] = [
				'barang_id' => $row->barang_id,
				'nama' => $row->nama_barang,
				'qty' => (float) $row->sold_qty,
			];
		}

		foreach ($map as $tokoId => $items) {
			usort($items, function (array $a, array $b) {
				return $b['qty'] <=> $a['qty'];
			});

			$map[$tokoId] = array_slice($items, 0, 3);
		}

		return $map;
	}

	private static function buildProductInsights(string $sourceId, string $neighborId, array $topProducts): array
	{
		$sourceProducts = $topProducts[$sourceId] ?? [];
		$neighborProducts = $topProducts[$neighborId] ?? [];

		if (empty($sourceProducts) && empty($neighborProducts)) {
			return [
				[
					'nama' => 'Pola KPI Serupa',
					'stat' => 'Kemiripan dihitung dari vektor KPI yang saling mendekati.',
				],
			];
		}

		$sourceByName = [];
		foreach ($sourceProducts as $item) {
			$sourceByName[$item['nama']] = $item;
		}

		$insights = [];
		foreach ($neighborProducts as $item) {
			if (!isset($sourceByName[$item['nama']])) {
				continue;
			}

			$sourceQty = (float) $sourceByName[$item['nama']]['qty'];
			$neighborQty = (float) $item['qty'];
			$insights[] = [
				'nama' => $item['nama'],
				'stat' => 'Volume terjual serupa: ' . number_format($sourceQty, 0, ',', '.') . ' vs ' . number_format($neighborQty, 0, ',', '.') . ' unit.',
			];

			if (count($insights) >= 2) {
				break;
			}
		}

		if (empty($insights)) {
			$sourceTop = $sourceProducts[0] ?? null;
			$neighborTop = $neighborProducts[0] ?? null;

			if ($sourceTop) {
				$insights[] = [
					'nama' => $sourceTop['nama'],
					'stat' => 'Produk dominan toko asal: ' . number_format((float) $sourceTop['qty'], 0, ',', '.') . ' unit.',
				];
			}

			if ($neighborTop) {
				$insights[] = [
					'nama' => $neighborTop['nama'],
					'stat' => 'Produk dominan toko pembanding: ' . number_format((float) $neighborTop['qty'], 0, ',', '.') . ' unit.',
				];
			}
		}

		return array_slice($insights, 0, 2);
	}

	private static function buildSimilarityReason(float $similarity): string
	{
		if ($similarity >= 0.9) {
			return 'Profil KPI sangat mirip dan pola transaksi berada pada level yang hampir identik.';
		}

		if ($similarity >= 0.75) {
			return 'Profil KPI cukup dekat, terutama pada konsistensi dan efisiensi distribusi.';
		}

		if ($similarity >= 0.6) {
			return 'Kemiripan moderat, ada beberapa KPI yang searah namun masih berbeda pada volume transaksi.';
		}

		return 'Kemiripan relatif rendah, tetapi masih relevan sebagai pembanding performa.';
	}

	private static function buildFrontendRows(Collection $partners): array
	{
		return $partners->map(function (array $row) {
			$raw = $row['raw_kpi'] ?? [];
			$norm = $row['normalized_kpi'] ?? [];
			$neighborSource = (string) ($row['neighbor_source'] ?? 'cf_user');
			$neighborSource = $neighborSource === 'kpi_fallback' ? 'kpi_fallback' : 'cf_user';
			$displayNeighbors = is_array($row['display_neighbors'] ?? null)
				? $row['display_neighbors']
				: ($neighborSource === 'kpi_fallback'
					? (is_array($row['kpi_fallback_neighbors'] ?? null) ? $row['kpi_fallback_neighbors'] : [])
					: (is_array($row['cf_user_top_neighbors'] ?? null) ? $row['cf_user_top_neighbors'] : []));

			$similarRows = collect($displayNeighbors)->map(function (array $neighbor) {
				return [
					'nama' => $neighbor['nama_toko'] ?? '-',
					'pct' => (int) ($neighbor['similarity_pct'] ?? 0),
					'kat' => $neighbor['category'] ?? 'D',
					'reason' => $neighbor['reason'] ?? 'Kemiripan berdasarkan vektor KPI.',
					'produk' => array_map(function (array $item) {
						return [
							'nama' => $item['nama'] ?? '-',
							'stat' => $item['stat'] ?? '-',
						];
					}, $neighbor['products'] ?? []),
				];
			})->values()->all();

			return [
				'id' => $row['toko_id'],
				'nama' => $row['nama_toko'],
				'wil' => $row['wilayah'] ?? '-',
				'kat' => $row['category'],
				'performance' => round((float) ($row['sell_through_rate'] ?? 0), 2),
				'hybrid' => round((float) ($row['hybrid_score'] ?? 0), 6),
				'cbf' => round((float) ($row['cbf_score'] ?? 0), 6),
				'cf' => round((float) ($row['cf_score'] ?? 0), 6),
				'user' => round((float) ($row['cf_user_score'] ?? 0), 6),
				'item' => round((float) ($row['cf_item_score'] ?? 0), 6),
				'similar_source' => $neighborSource,
				'rank' => (int) ($row['rank'] ?? 0),
				'kpi' => [
					'total_sales' => [
						'raw' => number_format((float) ($raw['sales'] ?? 0), 0, ',', '.') . ' unit',
						'pct' => (int) round(((float) ($norm['sales'] ?? 0)) * 100),
					],
					'return_rate' => [
						'raw' => number_format(((float) ($raw['return_rate'] ?? 0)) * 100, 2, '.', '') . '%',
						'pct' => (int) round(((float) ($norm['return_rate'] ?? 0)) * 100),
					],
					'trans_freq' => [
						'raw' => number_format((float) ($raw['freq'] ?? 0), 0, ',', '.') . ' transaksi',
						'pct' => (int) round(((float) ($norm['freq'] ?? 0)) * 100),
					],
					'sales_eff' => [
						'raw' => number_format(((float) ($raw['efficiency'] ?? 0)) * 100, 2, '.', '') . '%',
						'pct' => (int) round(((float) ($norm['efficiency'] ?? 0)) * 100),
					],
					'konsistensi' => [
						'raw' => number_format(((float) ($raw['consistency'] ?? 0)) * 100, 2, '.', '') . '%',
						'pct' => (int) round(((float) ($norm['consistency'] ?? 0)) * 100),
					],
				],
				'similar' => $similarRows,
			];
		})->values()->all();
	}

	private static function storePartnerScores(array $payload): int
	{
		$meta = $payload['meta'] ?? [];
		$periodStart = $meta['period_start'] ?? null;
		$periodEnd = $meta['period_end'] ?? null;

		if (empty($periodStart) || empty($periodEnd)) {
			return 0;
		}

		$defaultCbfWeights = ContentBasedFilteringService::defaultWeights(self::KPI_KEYS);
		$cbfWeights = is_array($meta['cbf_weights'] ?? null) ? $meta['cbf_weights'] : $defaultCbfWeights;
		$userSimilarityWeights = is_array($meta['user_similarity_weights'] ?? null) ? $meta['user_similarity_weights'] : [];
		$itemCfMeta = is_array($meta['item_cf_meta'] ?? null) ? $meta['item_cf_meta'] : [];
		$generatedAt = $meta['generated_at'] ?? now()->toDateTimeString();
		$storeSimilarity = (bool) ($meta['store_similarity'] ?? true);
		$similarityPairs = is_array($payload['similarity_pairs'] ?? null) ? $payload['similarity_pairs'] : [];

		return (int) DB::transaction(function () use (
			$payload,
			$periodStart,
			$periodEnd,
			$cbfWeights,
			$userSimilarityWeights,
			$itemCfMeta,
			$generatedAt,
			$meta,
			$storeSimilarity,
			$similarityPairs
		) {
			$stored = 0;

			foreach ($payload['partners'] ?? [] as $partner) {
				$tokoId = (string) ($partner['toko_id'] ?? '');
				if ($tokoId === '') {
					continue;
				}

				$raw = is_array($partner['raw_kpi'] ?? null) ? $partner['raw_kpi'] : [];
				$normalized = is_array($partner['normalized_kpi'] ?? null) ? $partner['normalized_kpi'] : [];
				$supportMetrics = is_array($partner['support_metrics'] ?? null) ? $partner['support_metrics'] : [];
				$contributions = is_array($partner['contributions'] ?? null) ? $partner['contributions'] : [];

				$kpiScore = PartnerKpiScore::query()->updateOrCreate(
					[
						PartnerKpiScore::FIELD_TOKO_ID => $tokoId,
						PartnerKpiScore::FIELD_PERIOD_START => $periodStart,
						PartnerKpiScore::FIELD_PERIOD_END => $periodEnd,
					],
					[
						PartnerKpiScore::FIELD_KPI_RAW_SALES => (int) round((float) ($raw['sales'] ?? 0)),
						PartnerKpiScore::FIELD_KPI_RAW_RETURN_RATE => round((float) ($raw['return_rate'] ?? 0), 6),
						PartnerKpiScore::FIELD_KPI_RAW_FREQ => (int) round((float) ($raw['freq'] ?? 0)),
						PartnerKpiScore::FIELD_KPI_RAW_CONSISTENCY => round((float) ($raw['consistency'] ?? 0), 6),
						PartnerKpiScore::FIELD_KPI_RAW_EFFICIENCY => round((float) ($raw['efficiency'] ?? 0), 6),
						PartnerKpiScore::FIELD_KPI_NORM_SALES => round((float) ($normalized['sales'] ?? 0), 8),
						PartnerKpiScore::FIELD_KPI_NORM_RETURN_RATE => round((float) ($normalized['return_rate'] ?? 0), 8),
						PartnerKpiScore::FIELD_KPI_NORM_FREQ => round((float) ($normalized['freq'] ?? 0), 8),
						PartnerKpiScore::FIELD_KPI_NORM_CONSISTENCY => round((float) ($normalized['consistency'] ?? 0), 8),
						PartnerKpiScore::FIELD_KPI_NORM_EFFICIENCY => round((float) ($normalized['efficiency'] ?? 0), 8),
						PartnerKpiScore::FIELD_CBF_SCORE => round((float) ($partner['cbf_score'] ?? 0), 8),
						PartnerKpiScore::FIELD_CBF_WEIGHTS => $cbfWeights,
						PartnerKpiScore::FIELD_KPI_VECTOR => array_map('floatval', $partner['kpi_vector'] ?? []),
						PartnerKpiScore::FIELD_TIME_SERIES_VECTOR => array_map('floatval', $partner['time_series_vector'] ?? []),
						PartnerKpiScore::FIELD_CALCULATION_META => [
							'support_metrics' => $supportMetrics,
							'generated_at' => $generatedAt,
						],
					]
				);

				$cfNeighbors = self::normalizeNeighborsForStorage(
					is_array($partner['cf_user_top_neighbors'] ?? null)
						? $partner['cf_user_top_neighbors']
						: []
				);

				$cfScore = PartnerCfScore::query()->updateOrCreate(
					[
						PartnerCfScore::FIELD_TOKO_ID => $tokoId,
						PartnerCfScore::FIELD_PERIOD_START => $periodStart,
						PartnerCfScore::FIELD_PERIOD_END => $periodEnd,
					],
					[
						PartnerCfScore::FIELD_CF_USER_SCORE => round((float) ($partner['cf_user_score'] ?? 0), 8),
						PartnerCfScore::FIELD_CF_USER_AVG_SIMILARITY => round((float) ($partner['cf_user_avg_similarity'] ?? 0), 8),
						PartnerCfScore::FIELD_CF_USER_NEIGHBOR_COUNT => (int) ($partner['cf_user_neighbor_count'] ?? count($cfNeighbors)),
						PartnerCfScore::FIELD_CF_USER_TOP_NEIGHBORS => $cfNeighbors,
						PartnerCfScore::FIELD_CF_ITEM_SCORE => round((float) ($partner['cf_item_score'] ?? 0), 8),
						PartnerCfScore::FIELD_CF_ITEM_RAW_SCORE => round((float) ($partner['cf_item_raw_score'] ?? 0), 8),
						PartnerCfScore::FIELD_CF_ITEM_RELATION_SCORE => round((float) ($partner['cf_item_relation_score'] ?? 0), 8),
						PartnerCfScore::FIELD_CF_ITEM_DIVERSITY_FACTOR => round((float) ($partner['cf_item_diversity_factor'] ?? 0), 8),
						PartnerCfScore::FIELD_CF_ITEM_BALANCE_FACTOR => round((float) ($partner['cf_item_balance_factor'] ?? 0), 8),
						PartnerCfScore::FIELD_CF_ITEM_AVG_SALES_NORM => round((float) ($partner['cf_item_avg_sales_norm'] ?? 0), 8),
						PartnerCfScore::FIELD_CF_ITEM_ACTIVE_PRODUCTS => (int) ($partner['cf_item_active_products'] ?? 0),
						PartnerCfScore::FIELD_CF_ITEM_TOTAL_PRODUCTS => (int) ($partner['cf_item_total_products'] ?? ($itemCfMeta['item_count'] ?? 0)),
						PartnerCfScore::FIELD_CF_SCORE => round((float) ($partner['cf_score'] ?? 0), 8),
						PartnerCfScore::FIELD_CF_BETA => round((float) ($partner['beta'] ?? ($meta['beta'] ?? self::DEFAULT_BETA)), 6),
						PartnerCfScore::FIELD_SIMILARITY_WEIGHTS => is_array($partner['similarity_weights'] ?? null)
							? $partner['similarity_weights']
							: $userSimilarityWeights,
						PartnerCfScore::FIELD_SIMILARITY_CACHE_KEY => $partner['similarity_cache_key'] ?? ($meta['user_similarity_cache_key'] ?? null),
						PartnerCfScore::FIELD_CALCULATION_META => [
							'item_count' => (int) ($itemCfMeta['item_count'] ?? 0),
							'top_k_items' => (int) ($itemCfMeta['top_k_items'] ?? 0),
							'item_similarity_cache_key' => $itemCfMeta['similarity_cache_key'] ?? null,
							'generated_at' => $generatedAt,
						],
					]
				);

				PartnerPerformanceScore::query()->updateOrCreate(
				[
					PartnerPerformanceScore::FIELD_TOKO_ID => $tokoId,
					PartnerPerformanceScore::FIELD_PERIOD_START => $periodStart,
					PartnerPerformanceScore::FIELD_PERIOD_END => $periodEnd,
				],
				[
					PartnerPerformanceScore::FIELD_KPI_SCORE_ID => $kpiScore->getAttribute(PartnerKpiScore::FIELD_ID),
					PartnerPerformanceScore::FIELD_CF_SCORE_ID => $cfScore->getAttribute(PartnerCfScore::FIELD_ID),
					PartnerPerformanceScore::FIELD_CBF_SCORE => round((float) ($partner['cbf_score'] ?? 0), 8),
					PartnerPerformanceScore::FIELD_CF_USER_SCORE => round((float) ($partner['cf_user_score'] ?? 0), 8),
					PartnerPerformanceScore::FIELD_CF_ITEM_SCORE => round((float) ($partner['cf_item_score'] ?? 0), 8),
					PartnerPerformanceScore::FIELD_CF_SCORE => round((float) ($partner['cf_score'] ?? 0), 8),
					PartnerPerformanceScore::FIELD_HYBRID_SCORE => round((float) ($partner['hybrid_score'] ?? 0), 8),
					PartnerPerformanceScore::FIELD_HYBRID_ALPHA => round((float) ($partner['alpha'] ?? ($meta['alpha'] ?? self::DEFAULT_ALPHA)), 6),
					PartnerPerformanceScore::FIELD_HYBRID_BETA => round((float) ($partner['beta'] ?? ($meta['beta'] ?? self::DEFAULT_BETA)), 6),
					PartnerPerformanceScore::FIELD_CONTRIBUTION_CBF => round((float) ($contributions['cbf'] ?? 0), 8),
					PartnerPerformanceScore::FIELD_CONTRIBUTION_CF => round((float) ($contributions['cf'] ?? 0), 8),
					PartnerPerformanceScore::FIELD_CONTRIBUTION_CF_USER => round((float) ($contributions['cf_user'] ?? 0), 8),
					PartnerPerformanceScore::FIELD_CONTRIBUTION_CF_ITEM => round((float) ($contributions['cf_item'] ?? 0), 8),
					PartnerPerformanceScore::FIELD_CATEGORY => (string) ($partner['category'] ?? 'D'),
					PartnerPerformanceScore::FIELD_RANK => (int) ($partner['rank'] ?? 0),
				]
			);

				$stored++;
			}

			if ($storeSimilarity) {
				self::storeUserSimilarityPairs($periodStart, $periodEnd, $similarityPairs, $userSimilarityWeights);
			}

			return $stored;
		});
	}

	private static function normalizeNeighborsForStorage(array $neighbors): array
	{
		$normalized = [];

		foreach ($neighbors as $neighbor) {
			if (!is_array($neighbor)) {
				continue;
			}

			$tokoId = (string) ($neighbor['toko_id'] ?? '');
			if ($tokoId === '') {
				continue;
			}

			$similarity = array_key_exists('similarity', $neighbor)
				? (float) $neighbor['similarity']
				: ((float) ($neighbor['similarity_pct'] ?? 0) / 100);

			$normalized[] = [
				'toko_id' => $tokoId,
				'similarity' => round(self::clamp($similarity, 0, 1), 8),
				'score_kpi' => round(self::clamp((float) ($neighbor['score_kpi'] ?? 0), 0, 1), 8),
			];
		}

		return $normalized;
	}

	private static function storeUserSimilarityPairs(string $periodStart, string $periodEnd, array $pairs, array $defaultWeights): void
	{
		if (empty($pairs)) {
			return;
		}

		DB::table(PartnerCfSimilarity::TABLE)
			->where(PartnerCfSimilarity::FIELD_PERIOD_START, $periodStart)
			->where(PartnerCfSimilarity::FIELD_PERIOD_END, $periodEnd)
			->delete();

		$rows = [];
		$now = now();

		foreach ($pairs as $pair) {
			if (!is_array($pair)) {
				continue;
			}

			$tokoIdA = (string) ($pair['toko_id_a'] ?? '');
			$tokoIdB = (string) ($pair['toko_id_b'] ?? '');

			if ($tokoIdA === '' || $tokoIdB === '' || $tokoIdA === $tokoIdB) {
				continue;
			}

			if (strcmp($tokoIdA, $tokoIdB) > 0) {
				[$tokoIdA, $tokoIdB] = [$tokoIdB, $tokoIdA];
			}

			$key = $tokoIdA . '|' . $tokoIdB;
			if (array_key_exists($key, $rows)) {
				continue;
			}

			$weights = is_array($pair['weights'] ?? null) ? $pair['weights'] : $defaultWeights;
			$components = is_array($pair['components'] ?? null) ? $pair['components'] : [];

			$rows[$key] = [
				PartnerCfSimilarity::FIELD_PERIOD_START => $periodStart,
				PartnerCfSimilarity::FIELD_PERIOD_END => $periodEnd,
				PartnerCfSimilarity::FIELD_TOKO_ID_A => $tokoIdA,
				PartnerCfSimilarity::FIELD_TOKO_ID_B => $tokoIdB,
				PartnerCfSimilarity::FIELD_SIM_TOTAL => round(self::clamp((float) ($pair['sim_total'] ?? ($pair['score'] ?? 0)), 0, 1), 8),
				PartnerCfSimilarity::FIELD_SIM_LOCATION => round(self::clamp((float) ($pair['sim_location'] ?? ($components['location'] ?? 0)), 0, 1), 8),
				PartnerCfSimilarity::FIELD_SIM_DISTRICT => round(self::clamp((float) ($pair['sim_district'] ?? ($components['district'] ?? 0)), 0, 1), 8),
				PartnerCfSimilarity::FIELD_SIM_PATTERN => round(self::clamp((float) ($pair['sim_pattern'] ?? ($components['pattern'] ?? 0)), 0, 1), 8),
				PartnerCfSimilarity::FIELD_WEIGHTS_USED => json_encode($weights),
				'created_at' => $now,
				'updated_at' => $now,
			];
		}

		if (!empty($rows)) {
			DB::table(PartnerCfSimilarity::TABLE)->insert(array_values($rows));
		}
	}

	private static function emptyPayload(
		Carbon $startDate,
		Carbon $endDate,
		float $alpha,
		float $beta,
		array $additionalMeta = []
	): array
	{
		$meta = self::buildPeriodMeta($startDate, $endDate, $alpha, $beta);
		$meta['total_active_partners'] = 0;
		$meta['total_operational_partners'] = 0;
		$meta['no_operational_data'] = true;
		$meta['cbf_weights'] = ContentBasedFilteringService::defaultWeights(self::KPI_KEYS);
		$meta['cbf_weight_sum'] = 1.0;
		$meta['user_similarity_weights'] = [];
		$meta['user_similarity_cache_key'] = null;
		$meta['item_cf_meta'] = [];
		$meta['store_similarity'] = true;
		$meta = array_merge($meta, $additionalMeta);

		return [
			'meta' => $meta,
			'kpi_order' => self::KPI_KEYS,
			'normalization' => [],
			'similarity_matrices' => [
				'cbf' => [],
				'cf_user' => [],
				'cf_item' => [],
			],
			'similarity_pairs' => [],
			'partners' => [],
			'frontend_rows' => [],
		];
	}

	private static function resolveCategoryByRank(int $rank, int $totalPartners): string
	{
		if ($totalPartners <= 0) {
			return 'D';
		}

		$rank = max(1, min($rank, $totalPartners));

		$limitA = max(1, (int) ceil($totalPartners * self::CATEGORY_QUARTILE_A));
		$limitB = (int) ceil($totalPartners * self::CATEGORY_QUARTILE_B);
		$limitC = (int) ceil($totalPartners * self::CATEGORY_QUARTILE_C);

		if ($totalPartners > 1) {
			$limitB = max($limitB, $limitA + 1);
		}

		if ($totalPartners > 2) {
			$limitC = max($limitC, $limitB + 1);
		}

		$limitB = min($limitB, $totalPartners);
		$limitC = min($limitC, $totalPartners);

		if ($rank <= $limitA) {
			return 'A';
		}

		if ($rank <= $limitB) {
			return 'B';
		}

		if ($rank <= $limitC) {
			return 'C';
		}

		return 'D';
	}

	private static function normalizeWeight($value): float
	{
		return self::clamp((float) $value, 0, 1);
	}

	private static function safeDivide(float $numerator, float $denominator): float
	{
		if ($denominator <= 0) {
			return 0.0;
		}

		return $numerator / $denominator;
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

		return $dot / (sqrt($normA) * sqrt($normB));
	}

	private static function clamp(float $value, float $min, float $max): float
	{
		return max($min, min($max, $value));
	}
}