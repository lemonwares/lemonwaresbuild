<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExchangeRate
{
    public static function usdToNgn(): float
    {
        $manual = SiteSettings::manualRate();
        if ($manual !== null) {
            return $manual;
        }

        $fallback = (float) config('site.currency.usd_to_ngn', 7800);

        $cached = Cache::get('fx.usd_ngn');
        if (is_numeric($cached) && (float) $cached > 0) {
            return (float) $cached;
        }

        // Only a real live rate is cached; after a failure, wait 5 minutes before asking the API again.
        if (Cache::has('fx.usd_ngn.failed')) {
            return $fallback;
        }

        try {
            $response = Http::timeout(8)
                ->acceptJson()
                ->get('https://open.er-api.com/v6/latest/USD');

            $rate = $response->successful() ? (float) data_get($response->json(), 'rates.NGN', 0) : 0.0;
        } catch (\Throwable $exception) {
            Log::warning('USD/NGN rate fetch failed', ['error' => $exception->getMessage()]);
            $rate = 0.0;
        }

        if ($rate <= 0) {
            Cache::put('fx.usd_ngn.failed', true, now()->addMinutes(5));

            return $fallback;
        }

        $rate = round($rate, 2);
        Cache::put('fx.usd_ngn', $rate, now()->addHour());

        return $rate;
    }

    /**
     * Drops the cached live rate so the next request fetches a fresh one.
     */
    public static function refresh(): float
    {
        Cache::forget('fx.usd_ngn');
        Cache::forget('fx.usd_ngn.failed');

        return self::usdToNgn();
    }
}
