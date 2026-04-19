<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OverpassService
{
    private string $baseUrl = 'https://overpass-api.de/api/interpreter';

    public function getBoundary(string $osmType, int $osmId): ?array
    {
        $cacheKey = "overpass_boundary_{$osmType}_{$osmId}";

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($osmType, $osmId) {
            try {
                $query = $this->buildBoundaryQuery($osmType, $osmId);

                $response = Http::withHeaders([
                    'User-Agent' => config('app.name') . ' / ' . config('app.url'),
                ])
                ->timeout(15)
                ->withBody($query, 'text/plain')
                ->post($this->baseUrl);

                if ($response->failed()) {
                    Log::warning('Overpass request gagal', ['osm_id' => $osmId, 'status' => $response->status()]);
                    return null;
                }

                return $this->parseGeoJson($response->json());

            } catch (\Exception $e) {
                Log::error('Overpass error: ' . $e->getMessage());
                return null;
            }
        });
    }

    private function buildBoundaryQuery(string $osmType, int $osmId): string
    {
        $type = match($osmType) {
            'relation' => 'rel',
            'way'      => 'way',
            'node'     => 'node',
            default    => 'rel',
        };

        return "[out:json][timeout:15];
({$type}({$osmId}););
out geom;";
    }


    private function parseGeoJson(array $data): ?array
    {
        $elements = $data['elements'] ?? [];
        if (empty($elements)) return null;

        $element = $elements[0];
        $type    = $element['type'] ?? '';

        if ($type === 'relation') {
            return $this->parseRelation($element);
        }

        if ($type === 'way') {
            return $this->parseWay($element);
        }

        return null;
    }

    private function parseWay(array $element): array
    {
        $coords = array_map(
            fn($node) => [$node['lat'], $node['lon']],
            $element['geometry'] ?? []
        );

        return [
            'type'        => 'polygon',
            'coordinates' => [$coords],
        ];
    }

    private function parseRelation(array $element): array
    {
        $members = $element['members'] ?? [];
        $rings   = [];

        foreach ($members as $member) {
            if (($member['role'] ?? '') === 'outer' && isset($member['geometry'])) {
                $coords = array_map(
                    fn($node) => [$node['lat'], $node['lon']],
                    $member['geometry']
                );
                if (count($coords) >= 3) {
                    $rings[] = $coords;
                }
            }
        }

        if (empty($rings)) return ['type' => 'none', 'coordinates' => []];

        return [
            'type'        => count($rings) === 1 ? 'polygon' : 'multipolygon',
            'coordinates' => $rings,
        ];
    }
}
