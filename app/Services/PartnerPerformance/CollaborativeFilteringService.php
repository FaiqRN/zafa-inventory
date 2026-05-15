<?php

namespace App\Services\PartnerPerformance;

use App\Models\Toko;
use App\Services\PartnerPerformance\CF\ItemCollaborativeFilteringService;
use App\Services\PartnerPerformance\CF\UserCollaborativeFilteringService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CollaborativeFilteringService
{
	public static function calculateUserBased(
		Collection $partners,
		array $timeSeriesVectors,
		array $kpiScoreMap,
		array $options = []
	): array {
		$partnerProfiles = self::buildPartnerProfiles($partners);

		return UserCollaborativeFilteringService::calculateScores(
			$partnerProfiles,
			$timeSeriesVectors,
			$kpiScoreMap,
			$options
		);
	}

	public static function calculateItemBased(
		array $partnerIds,
		Carbon $startDate,
		Carbon $endDate,
		array $options = []
	): array {
		return ItemCollaborativeFilteringService::calculateScores($partnerIds, $startDate, $endDate, $options);
	}

	private static function buildPartnerProfiles(Collection $partners): array
	{
		$profiles = [];

		foreach ($partners as $partner) {
			$tokoId = (string) ($partner->{Toko::FIELD_TOKO_ID} ?? '');
			if ($tokoId === '') {
				continue;
			}

			$latitude = $partner->{Toko::FIELD_LATITUDE} ?? null;
			$longitude = $partner->{Toko::FIELD_LONGITUDE} ?? null;

			$profiles[$tokoId] = [
				'toko_id' => $tokoId,
				'district' => (string) ($partner->{Toko::FIELD_WILAYAH_KECAMATAN} ?? ''),
				'latitude' => is_numeric($latitude) ? (float) $latitude : null,
				'longitude' => is_numeric($longitude) ? (float) $longitude : null,
			];
		}

		ksort($profiles);

		return $profiles;
	}
}