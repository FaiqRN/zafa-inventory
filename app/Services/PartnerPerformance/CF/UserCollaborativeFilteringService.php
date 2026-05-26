<?php

namespace App\Services\PartnerPerformance\CF;

class UserCollaborativeFilteringService
{
	private const DEFAULT_TOP_N = 10;

	public static function calculateScores(
		array $partnerProfiles,
		array $timeSeriesVectors,
		array $kpiScoreMap,
		array $options = []
	): array {
		$topN = max(1, (int) ($options['top_n'] ?? self::DEFAULT_TOP_N));

		$similarityResult = SimilarityCollaborativeFilteringService::buildUserSimilarityMatrix(
			$partnerProfiles,
			$timeSeriesVectors,
			$options
		);

		$matrix = $similarityResult['matrix'] ?? [];
		$partnerIds = array_keys($partnerProfiles);
		$scores = [];

		foreach ($partnerIds as $targetId) {
			$neighbors = [];

			foreach ($partnerIds as $otherId) {
				if ($targetId === $otherId) {
					continue;
				}

				$sim = (float) ($matrix[$targetId][$otherId] ?? 0);
				if ($sim <= 0) {
					continue;
				}

				$neighbors[] = [
					'toko_id' => $otherId,
					'similarity' => round($sim, 8),
					'score_kpi' => round((float) ($kpiScoreMap[$otherId] ?? 0), 8),
				];
			}

			usort($neighbors, function (array $a, array $b) {
				return $b['similarity'] <=> $a['similarity'];
			});

			$neighbors = array_slice($neighbors, 0, $topN);

			$numerator = 0.0;
			$denominator = 0.0;

			foreach ($neighbors as $neighbor) {
				$sim = (float) ($neighbor['similarity'] ?? 0);
				$neighborId = (string) ($neighbor['toko_id'] ?? '');

				if (!array_key_exists($neighborId, $kpiScoreMap)) {
					continue;
				}

				$scoreKpi = (float) $kpiScoreMap[$neighborId];
				$numerator += $sim * $scoreKpi;
				$denominator += $sim;
			}

			$scoreUser = $denominator > 0 ? $numerator / $denominator : 0.0;
			$avgSimilarity = count($neighbors) > 0
				? array_sum(array_column($neighbors, 'similarity')) / count($neighbors)
				: 0.0;

			$scores[$targetId] = [
				'score' => round(self::clamp($scoreUser, 0, 1), 8),
				'score_user' => round(self::clamp($scoreUser, 0, 1), 8),
				'avg_similarity' => round(self::clamp($avgSimilarity, 0, 1), 8),
				'neighbors' => $neighbors,
			];
		}

		return [
			'matrix' => $matrix,
			'scores' => $scores,
			'weights' => $similarityResult['weights'] ?? SimilarityCollaborativeFilteringService::defaultWeights(),
			'pairs' => $similarityResult['pairs'] ?? [],
			'cache_key' => $similarityResult['cache_key'] ?? null,
		];
	}

	private static function clamp(float $value, float $min, float $max): float
	{
		return max($min, min($max, $value));
	}
}