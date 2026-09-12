<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GoogleReviews
{
    /**
     * @return array{rating: float|int, total: int, source: string, live: bool, url?: string, items: list<array<string, mixed>>}
     */
    public static function get(): array
    {
        $fallback = [
            'rating' => (float) config('reviews.rating', 0),
            'total' => (int) config('reviews.total', 0),
            'source' => (string) config('reviews.source', 'Google'),
            'live' => false,
            'url' => (string) config('services.google.business_url', ''),
            'items' => array_values(config('reviews.items', [])),
        ];

        $apiKey = (string) config('services.google.places_api_key', '');

        if ($apiKey === '') {
            return $fallback;
        }

        $placeId = self::resolvePlaceId($apiKey);

        if ($placeId === '') {
            return $fallback;
        }

        try {
            return Cache::remember('google_place_reviews:'.$placeId, now()->addHours(6), function () use ($apiKey, $placeId, $fallback) {
                $response = Http::timeout(8)->get('https://maps.googleapis.com/maps/api/place/details/json', [
                    'place_id' => $placeId,
                    'fields' => 'name,rating,user_ratings_total,reviews,url',
                    'reviews_sort' => 'newest',
                    'key' => $apiKey,
                ]);

                if (! $response->successful()) {
                    return $fallback;
                }

                $payload = $response->json();

                if (($payload['status'] ?? '') !== 'OK') {
                    return $fallback;
                }

                $result = $payload['result'] ?? [];
                $reviews = collect($result['reviews'] ?? [])
                    ->take(8)
                    ->map(function (array $review): array {
                        $name = (string) ($review['author_name'] ?? 'Google reviewer');
                        $words = preg_split('/\s+/', trim($name)) ?: [];
                        $initials = collect($words)
                            ->filter()
                            ->take(2)
                            ->map(fn (string $part) => Str::upper(Str::substr($part, 0, 1)))
                            ->implode('');

                        return [
                            'initials' => $initials !== '' ? $initials : 'G',
                            'name' => $name,
                            'date' => (string) ($review['relative_time_description'] ?? ''),
                            'rating' => (int) ($review['rating'] ?? 5),
                            'text' => (string) ($review['text'] ?? ''),
                        ];
                    })
                    ->filter(fn (array $review) => $review['text'] !== '')
                    ->values()
                    ->all();

                if ($reviews === []) {
                    return $fallback;
                }

                return [
                    'rating' => (float) ($result['rating'] ?? $fallback['rating']),
                    'total' => (int) ($result['user_ratings_total'] ?? $fallback['total']),
                    'source' => 'Google',
                    'live' => true,
                    'url' => (string) ($result['url'] ?? $fallback['url']),
                    'items' => $reviews,
                ];
            });
        } catch (\Throwable) {
            return $fallback;
        }
    }

    protected static function resolvePlaceId(string $apiKey): string
    {
        $configured = trim((string) config('services.google.place_id', ''));

        if ($configured !== '') {
            return $configured;
        }

        $query = trim((string) config('services.google.place_query', 'LemonWares Technology Lagos'));

        return Cache::remember('google_place_id:'.md5($query), now()->addDays(7), function () use ($apiKey, $query) {
            $response = Http::timeout(8)->get('https://maps.googleapis.com/maps/api/place/findplacefromtext/json', [
                'input' => $query,
                'inputtype' => 'textquery',
                'fields' => 'place_id,name,formatted_address',
                'key' => $apiKey,
            ]);

            if (! $response->successful()) {
                return '';
            }

            $candidates = $response->json('candidates') ?? [];

            return (string) ($candidates[0]['place_id'] ?? '');
        });
    }
}
