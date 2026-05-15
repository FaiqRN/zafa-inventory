<?php

namespace App\Services\PartnerPerformance;

class HybridRecommendationService
{
	public static function calculateCfScore(float $scoreUser, float $scoreItem, float $beta): float
	{
		$scoreUser = self::clamp($scoreUser, 0, 1);
		$scoreItem = self::clamp($scoreItem, 0, 1);
		$beta = self::clamp($beta, 0, 1);

		return self::clamp(($beta * $scoreUser) + ((1 - $beta) * $scoreItem), 0, 1);
	}

	public static function calculateHybridScore(float $scoreCbf, float $scoreCf, float $alpha): float
	{
		$scoreCbf = self::clamp($scoreCbf, 0, 1);
		$scoreCf = self::clamp($scoreCf, 0, 1);
		$alpha = self::clamp($alpha, 0, 1);

		return self::clamp(($alpha * $scoreCbf) + ((1 - $alpha) * $scoreCf), 0, 1);
	}

	public static function buildScoreBreakdown(
		float $scoreCbf,
		float $scoreUser,
		float $scoreItem,
		float $alpha,
		float $beta
	): array {
		$scoreCbf = self::clamp($scoreCbf, 0, 1);
		$scoreUser = self::clamp($scoreUser, 0, 1);
		$scoreItem = self::clamp($scoreItem, 0, 1);
		$alpha = self::clamp($alpha, 0, 1);
		$beta = self::clamp($beta, 0, 1);

		$cfScore = self::calculateCfScore($scoreUser, $scoreItem, $beta);
		$hybridScore = self::calculateHybridScore($scoreCbf, $cfScore, $alpha);

		$cbfContribution = $alpha * $scoreCbf;
		$cfContribution = (1 - $alpha) * $cfScore;
		$userContribution = (1 - $alpha) * $beta * $scoreUser;
		$itemContribution = (1 - $alpha) * (1 - $beta) * $scoreItem;

		return [
			'cbf_score' => round($scoreCbf, 8),
			'cf_user_score' => round($scoreUser, 8),
			'cf_item_score' => round($scoreItem, 8),
			'cf_score' => round($cfScore, 8),
			'hybrid_score' => round($hybridScore, 8),
			'weights' => [
				'alpha' => round($alpha, 8),
				'cf_weight' => round(1 - $alpha, 8),
				'beta' => round($beta, 8),
				'item_weight' => round(1 - $beta, 8),
			],
			'contributions' => [
				'cbf' => round($cbfContribution, 8),
				'cf_total' => round($cfContribution, 8),
				'cf_user' => round($userContribution, 8),
				'cf_item' => round($itemContribution, 8),
			],
		];
	}

	public static function rankByHybridScore(array $partners): array
	{
		usort($partners, function (array $a, array $b) {
			$compareHybrid = ((float) ($b['hybrid_score'] ?? 0)) <=> ((float) ($a['hybrid_score'] ?? 0));
			if ($compareHybrid !== 0) {
				return $compareHybrid;
			}

			$compareCbf = ((float) ($b['cbf_score'] ?? 0)) <=> ((float) ($a['cbf_score'] ?? 0));
			if ($compareCbf !== 0) {
				return $compareCbf;
			}

			$compareCf = ((float) ($b['cf_score'] ?? 0)) <=> ((float) ($a['cf_score'] ?? 0));
			if ($compareCf !== 0) {
				return $compareCf;
			}

			return strcmp((string) ($a['toko_id'] ?? ''), (string) ($b['toko_id'] ?? ''));
		});

		foreach ($partners as $index => &$partner) {
			$partner['rank'] = $index + 1;
		}
		unset($partner);

		return $partners;
	}

	private static function clamp(float $value, float $min, float $max): float
	{
		return max($min, min($max, $value));
	}
}