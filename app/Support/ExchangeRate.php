<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExchangeRate
{
    public static function usdToNgn(): float
    {
        $fallback = (float) config('site.currency.usd_to_ngn', 7800);

        try {
            return (float) Cache::remember('fx.usd_ngn', now()->addHour(), function () use ($fallback) {
                $response = Http::timeout(8)
                    ->acceptJson()
                    ->get('https://open.er-api.com/v6/latest/USD');

                if (! $response->successful()) {
                    return $fallback;
                }

                $rate = (float) data_get($response->json(), 'rates.NGN', 0);

                if ($rate <= 0) {
                    return $fallback;
                }

                return round($rate, 2);
            });
        } catch (\Throwable $exception) {
            Log::warning('USD/NGN rate fetch failed', ['error' => $exception->getMessage()]);

            return $fallback;
        }
    }
}
