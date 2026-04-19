<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class NominatimService
{
    private string $baseUrl = 'https://nominatim.openstreetmap.org';

    private function headers(): array
    {
        return [
            'User-Agent'      => config('app.name') . ' / ' . config('app.url'),
            'Accept-Language' => 'id,en;q=0.9',
        ];
    }

    public function search(string $query, int $limit = 6): array
    {
        $cacheKey = 'nominatim_search_' . md5($query . $limit);

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($query, $limit) {
            try {
                $response = Http::withHeaders($this->headers())
                    ->timeout(8)
                    ->get("{$this->baseUrl}/search", [
                        'format'         => 'json',
                        'q'              => $query,
                        'limit'          => $limit,
                        'addressdetails' => 1,
                        'extratags'      => 1,
                        'namedetails'    => 1,
                    ]);

                if ($response->failed()) {
                    Log::warning('Nominatim search gagal', ['query' => $query, 'status' => $response->status()]);
                    return [];
                }

                return array_map([$this, 'formatPlace'], $response->json());

            } catch (\Exception $e) {
                Log::error('Nominatim search error: ' . $e->getMessage());
                return [];
            }
        });
    }

    public function reverse(float $lat, float $lon): ?array
    {
        $cacheKey = 'nominatim_reverse_' . md5("{$lat}_{$lon}");

        return Cache::remember($cacheKey, now()->addMinutes(60), function () use ($lat, $lon) {
            try {
                $response = Http::withHeaders($this->headers())
                    ->timeout(8)
                    ->get("{$this->baseUrl}/reverse", [
                        'format'         => 'json',
                        'lat'            => $lat,
                        'lon'            => $lon,
                        'addressdetails' => 1,
                        'extratags'      => 1,
                        'namedetails'    => 1,
                    ]);

                if ($response->failed() || isset($response->json()['error'])) {
                    return null;
                }

                return $this->formatPlace($response->json());

            } catch (\Exception $e) {
                Log::error('Nominatim reverse error: ' . $e->getMessage());
                return null;
            }
        });
    }

    private function formatPlace(array $raw): array
    {
        $addr  = $raw['address']   ?? [];
        $extra = $raw['extratags'] ?? [];
        $names = $raw['namedetails'] ?? [];

        $name = $names['name']
            ?? $names['name:id']
            ?? $names['name:en']
            ?? explode(',', $raw['display_name'])[0];

        return [
            'osm_id'       => $raw['osm_id']   ?? null,
            'osm_type'     => $raw['osm_type']  ?? null,
            'name'         => trim($name),
            'display_name' => $raw['display_name'] ?? '',
            'type'         => $raw['type']  ?? $raw['class'] ?? 'lokasi',
            'class'        => $raw['class'] ?? null,
            'lat'          => (float) ($raw['lat'] ?? 0),
            'lon'          => (float) ($raw['lon'] ?? 0),
            'address'      => [
                'road'         => $addr['road'] ?? null,
                'neighbourhood'=> $addr['neighbourhood'] ?? $addr['hamlet'] ?? null,
                'suburb'       => $addr['suburb'] ?? $addr['village'] ?? null,
                'city'         => $addr['city'] ?? $addr['town'] ?? $addr['county'] ?? null,
                'state'        => $addr['state'] ?? null,
                'postcode'     => $addr['postcode'] ?? null,
                'country'      => $addr['country'] ?? null,
            ],
            'extra' => [
                'phone'         => $extra['phone'] ?? $extra['contact:phone'] ?? null,
                'website'       => $extra['website'] ?? $extra['url'] ?? null,
                'opening_hours' => $extra['opening_hours'] ?? null,
                'amenity'       => $addr['amenity'] ?? null,
            ],
        ];
    }
}
