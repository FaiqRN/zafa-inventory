<?php

namespace App\Services\PartnerPerformance;

use Illuminate\Support\Collection;

class ContentBasedFilteringService
{
	private const DEFAULT_WEIGHT = 0.125;

	public static function calculateScores(Collection $rows, array $kpiOrder, array $requestedWeights = []): array
	{
		$weights = self::normalizeWeights($kpiOrder, $requestedWeights);

		$scores = [];
		foreach ($rows as $row) {
			$tokoId = $row['toko_id'] ?? null;
			if ($tokoId === null) {
				continue;
			}

			$score = 0.0;
			$components = [];

			foreach ($kpiOrder as $kpiKey) {
				$weight = (float) ($weights[$kpiKey] ?? 0);
				$value = self::clamp((float) ($row['normalized_kpi'][$kpiKey] ?? 0), 0, 1);
				$weighted = $weight * $value;
				$score += $weighted;

				$components[$kpiKey] = [
					'weight' => round($weight, 8),
					'value' => round($value, 8),
					'weighted' => round($weighted, 8),
				];
			}

			$scores[$tokoId] = [
				'score' => round(self::clamp($score, 0, 1), 8),
				'components' => $components,
			];
		}

		return [
			'weights' => $weights,
			'weight_sum' => round(array_sum($weights), 8),
			'scores' => $scores,
		];
	}

	public static function defaultWeights(array $kpiOrder): array
	{
		if (count($kpiOrder) === 0) {
			return [];
		}

		$weights = [];
		$equalWeight = 1 / count($kpiOrder);

		foreach ($kpiOrder as $kpiKey) {
			$weights[$kpiKey] = $equalWeight;
		}

		return $weights;
	}

	private static function normalizeWeights(array $kpiOrder, array $requestedWeights): array
	{
		$weights = [];

		foreach ($kpiOrder as $kpiKey) {
			$value = array_key_exists($kpiKey, $requestedWeights)
				? (float) $requestedWeights[$kpiKey]
				: self::DEFAULT_WEIGHT;

			$weights[$kpiKey] = max(0.0, $value);
		}

		$sum = array_sum($weights);

		if ($sum <= 0) {
			$count = count($kpiOrder);
			if ($count === 0) {
				return [];
			}

			$equalWeight = 1 / $count;
			foreach ($kpiOrder as $kpiKey) {
				$weights[$kpiKey] = $equalWeight;
			}

			return $weights;
		}

		foreach ($kpiOrder as $kpiKey) {
			$weights[$kpiKey] = $weights[$kpiKey] / $sum;
		}

		return $weights;
	}

	private static function clamp(float $value, float $min, float $max): float
	{
		return max($min, min($max, $value));
	}
}